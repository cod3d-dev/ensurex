<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Enums\DocumentStatus;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Enums\QuoteStatus;
use App\Filament\Resources\PolicyResource;
use App\Filament\Resources\QuoteResource;
use App\Models\Policy;
use App\Models\Quote;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewQuote extends ViewRecord
{
    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar')
                ->color('warning'),
            Actions\Action::make('convert_to_policy')
                ->label('Crear Poliza')
                ->icon('heroicon-o-document-duplicate')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Crear Poliza')
                ->modalDescription('Se creara una poliza a partir de esta cotización.')
                ->modalSubmitActionLabel('Crear y Editar')
                ->action(function (Quote $record, array $data) {
                    // Determine the main policy type from the quote's policy_type array
                    $quotePolicyTypesData = $data['policy_type'] ?? []; // Assuming $data['policy_type'] is the array from the quote

                    // Priority list for policy types. These should be the string values
                    // that PolicyType::from() can accept (e.g., 'Life', 'Vision').
                    $policyPriorityOrder = ['Life', 'Vision', 'Dental', 'Accident'];

                    $mainPolicyValue = null;
                    if (is_array($quotePolicyTypesData) && ! empty($quotePolicyTypesData)) {
                        foreach ($policyPriorityOrder as $priority) {
                            // Assuming $quotePolicyTypesData contains strings like 'Life', 'Vision', etc.
                            if (in_array($priority, array_map('strval', $quotePolicyTypesData))) {
                                $mainPolicyValue = $priority;
                                break; // Found the highest priority type
                            }
                        }
                    }

                    $finalPolicyType = PolicyType::Life; // Default to Life as a fallback
                    if ($mainPolicyValue !== null) {
                        try {
                            $finalPolicyType = PolicyType::from($mainPolicyValue);
                        } catch (\ValueError $e) {
                            \Illuminate\Support\Facades\Log::warning("Quote to Policy: Could not create PolicyType from '{$mainPolicyValue}'. Defaulting to Life. Quote ID: {$record->id}");
                        }
                    } elseif (is_array($quotePolicyTypesData) && ! empty($quotePolicyTypesData) && isset($quotePolicyTypesData[0])) {
                        // Fallback: If no priority match, but there are types, try the first one from the quote.
                        try {
                            $finalPolicyType = PolicyType::from(strval($quotePolicyTypesData[0]));
                        } catch (\ValueError $e) {
                            \Illuminate\Support\Facades\Log::warning("Quote to Policy: Could not create PolicyType from first item '{$quotePolicyTypesData[0]}'. Defaulting to Life. Quote ID: {$record->id}");
                        }
                    }
                    // $finalPolicyType is now the main PolicyType enum instance

                    // Import required classes
                    $policy = Policy::create([
                        'contact_id' => $record->contact_id,
                        'user_id' => auth()->id(),
                        'insurance_company_id' => $record->insurance_company_id,
                        'policy_type' => $finalPolicyType,
                        'quote_policy_types' => $record->policy_types, // Persist the original array of policy types
                        'agent_id' => $record->agent_id,
                        'quote_id' => $record->id,
                        'policy_total_cost' => $record->premium_amount,
                        'premium_amount' => $record->premium_amount,
                        'coverage_amount' => $record->coverage_amount,
                        'main_applicant' => $record->main_applicant,
                        'additional_applicants' => $record->additional_applicants,
                        'total_family_members' => $record->total_family_members,
                        'total_applicants' => $record->total_applicants,
                        'total_applicants_with_medicaid' => (function () use ($record) {
                            $count = 0;

                            // Check additional applicants
                            if (is_array($record->applicants)) {
                                foreach ($record->applicants as $applicant) {
                                    if (isset($applicant['is_eligible_for_coverage']) && $applicant['is_eligible_for_coverage'] === true) {
                                        $count++;
                                    }
                                }
                            }

                            // dd($count);

                            return $count;
                        })(),
                        'estimated_household_income' => $record->estimated_household_income,
                        'preferred_doctor' => $record->preferred_doctor,
                        'prescription_drugs' => $record->prescription_drugs ?? null,
                        'contact_information' => $record->contact_information ?? null,
                        'status' => PolicyStatus::Draft,
                        'document_status' => DocumentStatus::ToAdd,
                        'policy_year' => $record->year,
                        'policy_zipcode' => $record->contact->zip_code,
                        'policy_us_county' => $record->contact->county,
                        'policy_city' => $record->contact->city,
                        'policy_us_state' => $record->contact->state_province,
                        'effective_date' => (function () use ($record) {
                            $currentYear = (int) date('Y');
                            $quoteYear = (int) $record->year;

                            if ($quoteYear === $currentYear) {
                                // First day of the next month if quote year is current year
                                return Carbon::now()->addMonth()->startOfMonth()->format('Y-m-d');
                            } else {
                                // First day of the year if quote year is next year
                                return Carbon::createFromDate($quoteYear, 1, 1)->format('Y-m-d');
                            }
                        })(),

                    ]);

                    // Update the quote status to Converted and add policy reference
                    $record->update([
                        'status' => QuoteStatus::Converted->value,
                        'policy_id' => $policy->id,
                    ]);

                    // Fill the applicants data from quote applicants to policy_applicants pivot table
                    if (is_array($record->applicants) && count($record->applicants) > 0) {
                        $sortOrder = 1;

                        foreach ($record->applicants as $key => $applicant) {
                            // For the first applicant, use the contact_id from the quote
                            $contactId = ($sortOrder === 1) ? $record->contact_id : null;

                            // Create the policy applicant record
                            \DB::table('policy_applicants')->insert([
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

                    // Redirect to edit the policy
                    $this->redirect(PolicyResource::getUrl('edit', ['record' => $policy->id]));
                }),
        ];
    }
}
