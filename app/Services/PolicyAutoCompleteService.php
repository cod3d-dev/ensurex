<?php

namespace App\Services;

use App\Enums\PolicyInscriptionType;
use App\Enums\PolicyStatus;
use App\Models\InsuranceCompany;
use App\Models\Policy;
use DB;
use Exception;
use Illuminate\Support\Facades\Log;

class PolicyAutoCompleteService
{
    /**
     * Auto-complete a policy with random data and finalize it
     * 
     * @param Policy $policy The policy to auto-complete
     * @return Policy The completed policy
     * @throws Exception If completion fails
     */
    public function completePolicy(Policy $policy)
    {
        if ($policy->status !== PolicyStatus::Draft) {
            return $policy; // Already completed or in another state
        }

        // Begin transaction for all the operations
        DB::beginTransaction();

        try {
            // 1. Fill in required fields with random data
            $this->fillRandomData($policy);
            
            // 2. Mark all required pages as completed
            $this->markPagesAsCompleted($policy);

            // 3. Execute the "Create Policies" action logic that updates status to Created
            $this->executeCreatePoliciesAction($policy);
            
            DB::commit();
            
            return $policy->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to auto-complete policy: ' . $e->getMessage(), [
                'policy_id' => $policy->id,
                'exception' => $e,
            ]);
            
            throw new Exception('Failed to auto-complete policy: ' . $e->getMessage());
        }
    }
    
    /**
     * Fill required fields with random data
     */
    protected function fillRandomData(Policy $policy)
    {
        // Get a random insurance company
        $company = InsuranceCompany::inRandomOrder()->first();
        
        // If no insurance company exists, create a default one
        if (!$company) {
            $company = InsuranceCompany::create([
                'name' => 'Default Insurance Co.',
                'address' => '123 Insurance St.',
                'city' => 'Insurance City',
                'state' => 'CA',
                'zip_code' => '90210',
                'phone' => '(555) 123-4567',
                'email' => 'contact@defaultinsurance.com',
                'website' => 'https://defaultinsurance.com',
                'status' => 'active',
            ]);
        }
        
        // Get random inscription type
        $inscriptionTypes = array_column(PolicyInscriptionType::cases(), 'value');
        $randomInscriptionType = $inscriptionTypes[array_rand($inscriptionTypes)];
        
        // Get random policy plan
        $policyPlans = ['Premium', 'Gold', 'Silver', 'Bronze'];
        $randomPlan = $policyPlans[array_rand($policyPlans)];
        
        // Update the policy with random data
        $policy->insurance_company_id = $company->id;
        $policy->policy_inscription_type = $randomInscriptionType;
        $policy->policy_plan = $randomPlan;
        
        // Save the changes
        $policy->save();
    }
    
    /**
     * Mark all required pages as completed
     */
    protected function markPagesAsCompleted(Policy $policy)
    {
        // Set all required pages as completed
        $requiredPages = [
            'edit_policy', 
            'edit_policy_contact',
            'edit_policy_applicants', 
            'edit_policy_applicants_data',
            'edit_policy_income',
            'edit_policy_payments'
        ];
        
        $policy->completed_pages = $requiredPages;
        $policy->save();
    }
    
    /**
     * Execute the "Create Policies" action logic
     */
    protected function executeCreatePoliciesAction(Policy $policy)
    {
        // Update the policy status to Created
        $policy->status = PolicyStatus::Created;
        $policy->save();
        
        // Get the policy types selected for creation
        $selectedPolicyTypes = $policy->quote_policy_types ?? [];
        
        if (empty($selectedPolicyTypes)) {
            return; // No additional policies to create
        }
        
        // Create new policies for each selected type (except the current one)
        foreach ($selectedPolicyTypes as $policyTypeValue) {
            // Skip if this is the current policy's type
            $policyType = \App\Enums\PolicyType::from($policyTypeValue);
            if ($policyType === $policy->policy_type) {
                continue;
            }
            
            // Create a new policy with the same data but different type
            $newPolicy = $policy->replicate(['id']);
            $newPolicy->policy_type = $policyType;
            $newPolicy->status = PolicyStatus::Created;
            $newPolicy->policy_number = null;
            
            // Set specific fields for Life policies
            if ($policyType->value === 'life') {
                $newPolicy->total_family_members = 1;
                $newPolicy->total_applicants = 1;
                $newPolicy->total_applicants_with_medicaid = 0;
            }
            
            // Force the model to generate a new code
            $newPolicy->code = null;
            $newPolicy->save();
            
            // Copy policy applicants based on policy type
            if ($policyType->value === 'life') {
                // For Life policies, only copy the first applicant (policy owner)
                $firstApplicant = $policy->policyApplicants->first();
                if ($firstApplicant) {
                    $newApplicant = $firstApplicant->replicate(['id']);
                    $newApplicant->policy_id = $newPolicy->id;
                    $newApplicant->save();
                }
            } else {
                // For other policy types, copy all applicants
                foreach ($policy->policyApplicants as $applicant) {
                    $newApplicant = $applicant->replicate(['id']);
                    $newApplicant->policy_id = $newPolicy->id;
                    $newApplicant->save();
                }
            }
            
            // Copy contact information if it exists separately from the main contact
            if (method_exists($policy, 'policyContacts') && $policy->policyContacts()->exists()) {
                foreach ($policy->policyContacts as $contact) {
                    $newContact = $contact->replicate(['id']);
                    $newContact->policy_id = $newPolicy->id;
                    $newContact->save();
                }
            }
            
            // Copy policy documents that should be shared across all policy types
            if (method_exists($policy, 'documents') && $policy->documents()->exists()) {
                foreach ($policy->documents as $document) {
                    $newDocument = $document->replicate(['id']);
                    $newDocument->policy_id = $newPolicy->id;
                    $newDocument->save();
                }
            }
        }
    }
}
