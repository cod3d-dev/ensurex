<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\Gender;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Enums\QuoteStatus;
use App\Models\Contact;
use App\Models\Policy;
use App\Models\Quote;
use Carbon\Carbon;
use Faker\Factory as FakerFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuoteConversionService
{
    /**
     * Convert a quote to a policy
     *
     * @param  Quote  $quote  The quote to convert
     * @param  int|null  $userId  Optional user ID to use for the policy (useful for CLI)
     * @param  array|null  $dateOptions  Optional date options for policy creation
     * @return Policy The newly created policy
     */
    public function convertQuoteToPolicy(Quote $quote, ?int $userId = null, ?array $dateOptions = null)
    {
        // Determine the main policy type from the quote's policy_type array
        $quotePolicyTypesData = $quote->policy_types ?? [];

        // Priority list for policy types (in order of priority)
        $policyPriorityOrder = [
            PolicyType::Health->value,
            PolicyType::Dental->value,
            PolicyType::Vision->value,
            PolicyType::Accident->value,
            PolicyType::Life->value,
        ];

        $mainPolicyValue = null;
        if (is_array($quotePolicyTypesData) && ! empty($quotePolicyTypesData)) {
            // If only one policy type is selected, use it
            if (count($quotePolicyTypesData) === 1) {
                $mainPolicyValue = $quotePolicyTypesData[0];
            } else {
                // Find the highest priority policy type that exists in the selected types
                foreach ($policyPriorityOrder as $policyType) {
                    if (in_array($policyType, $quotePolicyTypesData)) {
                        $mainPolicyValue = $policyType;
                        break;
                    }
                }
            }

            // If no match found (shouldn't happen if quote has valid policy types)
            if ($mainPolicyValue === null) {
                $mainPolicyValue = $quotePolicyTypesData[0];
            }
        }

        $finalPolicyType = PolicyType::Life; // Default to Life as a fallback
        if ($mainPolicyValue !== null) {
            try {
                $finalPolicyType = PolicyType::from($mainPolicyValue);
            } catch (\ValueError $e) {
                Log::warning("Quote to Policy: Could not create PolicyType from '{$mainPolicyValue}'. Defaulting to Life. Quote ID: {$quote->id}");
            }
        } elseif (is_array($quotePolicyTypesData) && ! empty($quotePolicyTypesData) && isset($quotePolicyTypesData[0])) {
            try {
                $finalPolicyType = PolicyType::from(strval($quotePolicyTypesData[0]));
            } catch (\ValueError $e) {
                Log::warning("Quote to Policy: Could not create PolicyType from first item '{$quotePolicyTypesData[0]}'. Defaulting to Life. Quote ID: {$quote->id}");
            }
        }

        // Prepare policy data
        $policyData = [
            'contact_id' => $quote->contact_id,
            'user_id' => $quote->user_id,
            'insurance_company_id' => $quote->insurance_company_id,
            'policy_type' => $finalPolicyType,
            'quote_policy_types' => $quote->policy_types,
            'agent_id' => $quote->agent_id,
            'quote_id' => $quote->id,
            'policy_total_cost' => $quote->premium_amount,
            'premium_amount' => $quote->premium_amount,
            'coverage_amount' => $quote->coverage_amount,
            'main_applicant' => $quote->main_applicant,
            'additional_applicants' => $quote->additional_applicants,
            'total_family_members' => $quote->total_family_members,
            'total_applicants' => $quote->total_applicants,
            'total_applicants_with_medicaid' => $this->countApplicantsWithMedicaid($quote),
            'estimated_household_income' => $quote->estimated_household_income,
            'preferred_doctor' => $quote->preferred_doctor,
            'prescription_drugs' => $quote->prescription_drugs ?? null,
            'contact_information' => $quote->contact_information ?? null,
            'status' => PolicyStatus::Draft,
            'document_status' => DocumentStatus::ToAdd,
            'policy_year' => $quote->year,
            'policy_zipcode' => $quote->contact->zip_code,
            'policy_us_county' => $quote->contact->county,
            'policy_city' => $quote->contact->city,
            'policy_us_state' => $quote->contact->state_province,
            'effective_date' => $this->calculateEffectiveDate($quote, $dateOptions),
        ];

        // Set custom creation date if provided
        if ($dateOptions && isset($dateOptions['date'])) {
            $policyData['created_at'] = $dateOptions['date'];
            $policyData['updated_at'] = $dateOptions['date'];
        }
        
        // Create the policy
        $policy = Policy::create($policyData);

        // Update the quote status to Converted and add policy reference
        $quote->update([
            'status' => QuoteStatus::Converted->value,
            'policy_id' => $policy->id,
        ]);

        // Fill the applicants data
        $this->createPolicyApplicants($quote, $policy);

        return $policy;
    }

    /**
     * Count applicants with Medicaid coverage
     */
    private function countApplicantsWithMedicaid(Quote $quote): int
    {
        $count = 0;

        if (is_array($quote->applicants)) {
            foreach ($quote->applicants as $applicant) {
                if (isset($applicant['is_eligible_for_coverage']) && $applicant['is_eligible_for_coverage'] === true) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Calculate the effective date for the policy
     * 
     * @param Quote $quote The quote being converted
     * @param array|null $dateOptions Optional date options
     * @return string The effective date in Y-m-d format
     */
    private function calculateEffectiveDate(Quote $quote, ?array $dateOptions = null): string
    {
        // If custom date is provided, use it
        if ($dateOptions && isset($dateOptions['date'])) {
            return $dateOptions['date']->format('Y-m-d');
        }
        
        $currentYear = (int) date('Y');
        $quoteYear = (int) $quote->year;

        if ($quoteYear === $currentYear) {
            // First day of the next month if quote year is current year
            return Carbon::now()->addMonth()->startOfMonth()->format('Y-m-d');
        } else {
            // First day of the year if quote year is next year
            return Carbon::createFromDate($quoteYear, 1, 1)->format('Y-m-d');
        }
    }

    /**
     * Create policy applicants from quote applicants
     */
    private function createPolicyApplicants(Quote $quote, Policy $policy): void
    {
        if (is_array($quote->applicants) && count($quote->applicants) > 0) {
            $sortOrder = 1;
            $faker = FakerFactory::create('es_VE');

            foreach ($quote->applicants as $applicant) {
                // For the first applicant, use the contact_id from the quote
                $contactId = null;
                
                if ($sortOrder === 1) {
                    $contactId = $quote->contact_id;
                } else {
                    // Create a new contact for this applicant if they don't have a contact_id
                    // Determine gender for name generation
                    $gender = isset($applicant['gender']) ? $applicant['gender'] : null;
                    $genderEnum = null;
                    
                    if ($gender) {
                        try {
                            $genderEnum = Gender::from($gender);
                        } catch (\ValueError $e) {
                            $genderEnum = $faker->randomElement(Gender::cases());
                        }
                    } else {
                        $genderEnum = $faker->randomElement(Gender::cases());
                    }
                    
                    // Generate a name based on gender if not provided
                    $fullName = $applicant['full_name'] ?? null;
                    if (empty($fullName)) {
                        $fakerGender = $genderEnum === Gender::Male ? 'male' : 'female';
                        $fullName = $faker->name($fakerGender);
                    }
                    
                    // Create a new contact for this applicant
                    $contact = Contact::create([
                        'full_name' => $fullName,
                        'gender' => $genderEnum,
                        'date_of_birth' => $applicant['date_of_birth'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    $contactId = $contact->id;
                }

                // Create the policy applicant record
                DB::table('policy_applicants')->insert([
                    'policy_id' => $policy->id,
                    'sort_order' => $sortOrder,
                    'contact_id' => $contactId,
                    'relationship_with_policy_owner' => $applicant['relationship'] ?? null,
                    'is_covered_by_policy' => $applicant['is_covered'] ?? true,
                    'medicaid_client' => $applicant['is_eligible_for_coverage'] ?? false,
                    'employer_1_name' => $applicant['employeer_name'] ?? null,
                    'employer_1_role' => $applicant['employement_role'] ?? null,
                    'employer_1_phone' => $applicant['employeer_phone'] ?? null,
                    'income_per_hour' => $applicant['income_per_hour'] ?? null,
                    'hours_per_week' => $applicant['hours_per_week'] ?? null,
                    'income_per_extra_hour' => $applicant['income_per_extra_hour'] ?? null,
                    'extra_hours_per_week' => $applicant['extra_hours_per_week'] ?? null,
                    'weeks_per_year' => $applicant['weeks_per_year'] ?? null,
                    'yearly_income' => $applicant['yearly_income'] ?? null,
                    'is_self_employed' => $applicant['is_self_employed'] ?? false,
                    'self_employed_yearly_income' => $applicant['self_employed_yearly_income'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $sortOrder++;
            }
        }
    }
}
