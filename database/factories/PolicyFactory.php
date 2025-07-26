<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Enums\PolicyInscriptionType;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Enums\QuoteStatus;
use App\Enums\UsState;
use App\Models\Contact;
use App\Models\InsuranceCompany;
use App\Models\Policy;
use App\Models\Quote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Policy>
 */
class PolicyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Set faker locale to Spanish Venezuela
        $this->faker->locale('es_VE');

        $quote = Quote::inRandomOrder()->first() ?? Quote::factory()->create();

        // Get or create a user
        $user = $quote->user;

        // Get or create a contact
        $contact = $quote->contact;

        // Get or create an insurance company
        $insuranceCompany = InsuranceCompany::inRandomOrder()->first() ?? InsuranceCompany::factory()->create();

        // Default policy type is Health if not overridden by state
        $policyType = PolicyType::Health;

        // Generate a random US state
        $state = $this->faker->randomElement(UsState::cases());

        // Generate effective and expiration dates
        $effectiveDate = Carbon::now()->addDays(rand(1, 30));
        $expirationDate = Carbon::parse($effectiveDate)->addYear();

        // Generate premium amount between $100 and $1000
        $premiumAmount = round($this->faker->randomFloat(2, 100, 1000), 2);

        // Generate coverage amount between $10,000 and $1,000,000
        $coverageAmount = round($this->faker->randomFloat(2, 10000, 1000000), 2);

        // Generate estimated household income
        $estimatedHouseholdIncome = round($this->faker->randomFloat(2, 24000, 120000), 2);

        return [
            'user_id' => $user->id,
            'contact_id' => $contact->id,
            'insurance_company_id' => $insuranceCompany->id,
            'policy_type' => $policyType,
            'agent_id' => $quote->agent_id,
            'quote_id' => $quote->id,
            'policy_zipcode' => $quote->contact->zip_code,
            'policy_us_county' => $quote->contact->county,
            'policy_city' => $quote->contact->city,
            'policy_us_state' => $quote->contact->state_province,
            'policy_plan' => $this->faker->randomElement(['Bronze', 'Silver', 'Gold', 'Platinum']),
            'policy_inscription_type' => $this->faker->randomElement(PolicyInscriptionType::cases()),
            'policy_total_cost' => $premiumAmount * 12,
            'policy_total_subsidy' => round($this->faker->randomFloat(2, 0, $premiumAmount * 12 * 0.7), 2),
            'premium_amount' => $premiumAmount,
            'coverage_amount' => $coverageAmount,
            'recurring_payment' => $this->faker->boolean(80),
            'effective_date' => $effectiveDate,
            'expiration_date' => $expirationDate,
            'first_payment_date' => $effectiveDate,
            'preferred_payment_day' => $this->faker->numberBetween(1, 28),
            'initial_paid' => $this->faker->boolean(70),
            'autopay' => $this->faker->boolean(60),
            'requires_aca' => $this->faker->boolean(50),
            'aca' => $this->faker->boolean(40),
            'document_status' => DocumentStatus::Pending,
            'next_document_expiration_date' => Carbon::now()->addMonths(rand(1, 12)),
            'observations' => $this->faker->optional(0.7)->paragraph(),
            'client_notified' => $this->faker->boolean(50),
            'life_offered' => $this->faker->boolean(30),
            'contact_is_applicant' => $this->faker->boolean(90),
            'total_family_members' => $quote->total_family_members,
            'total_applicants' => $quote->total_applicants,
            'total_applicants_with_medicaid' => $quote->total_medicaid,
            'estimated_household_income' => $quote->estimated_household_income,
            'preferred_doctor' => $this->faker->optional(0.6)->name(),
            'prescription_drugs' => $this->faker->optional(0.5)->words(rand(1, 5)),
            'emergency_contact' => $this->faker->optional(0.7)->name(),
            'emergency_contact_phone' => $this->faker->optional(0.7)->phoneNumber(),
            'emergency_contact_relationship' => $this->faker->optional(0.7)->randomElement(['Spouse', 'Parent', 'Child', 'Sibling', 'Friend']),
            'start_date' => $effectiveDate,
            'end_date' => $expirationDate,
            'status' => PolicyStatus::Pending,
            'status_changed_date' => Carbon::now(),
            'status_changed_by' => $user->id,
            'is_initial_verification_complete' => $this->faker->boolean(30),
            'initial_verification_date' => $this->faker->optional(0.3)->dateTimeBetween('-1 month', 'now'),
            'initial_verification_performed_by' => $this->faker->optional(0.3)->randomElement([$user->id, null]),
            'notes' => $this->faker->optional(0.6)->paragraph(),
        ];
    }

    /**
     * Set the policy's created_at and updated_at timestamps
     */
    public function createdAt(string $date): static
    {
        $date = Carbon::parse($date);

        return $this->state([
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }

    /**
     * Set the policy type to Health
     */
    public function withHealthPolicy(): static
    {
        return $this->state([
            'policy_type' => PolicyType::Health,
        ]);
    }

    /**
     * Set the policy type to Life
     */
    public function withLifePolicy(): static
    {
        return $this->state([
            'policy_type' => PolicyType::Life,
        ]);
    }

    /**
     * Set the policy type to Dental
     */
    public function withDentalPolicy(): static
    {
        return $this->state([
            'policy_type' => PolicyType::Dental,
        ]);
    }

    /**
     * Set the policy type to Vision
     */
    public function withVisionPolicy(): static
    {
        return $this->state([
            'policy_type' => PolicyType::Vision,
        ]);
    }

    /**
     * Set the policy type to Accident
     */
    public function withAccidentPolicy(): static
    {
        return $this->state([
            'policy_type' => PolicyType::Accident,
        ]);
    }

    /**
     * Set the policy status to Active
     */
    public function active(): static
    {
        return $this->state([
            'status' => PolicyStatus::Active,
        ]);
    }

    /**
     * Create a policy from a converted quote
     */
    public function fromQuote(?Quote $quote = null): static
    {
        // If no quote is provided, create one with converted status
        if (! $quote) {
            $quote = Quote::factory()->create([
                'status' => QuoteStatus::Converted,
            ]);
        } else {
            // Update the provided quote to converted status if it's not already
            if ($quote->status !== QuoteStatus::Converted) {
                $quote->update(['status' => QuoteStatus::Converted]);
                $quote->refresh();
            }
        }

        // Extract policy type from quote's policy_types array
        $policyType = null;
        if (! empty($quote->policy_types) && is_array($quote->policy_types)) {
            $policyTypeValue = $quote->policy_types[0] ?? null;
            if ($policyTypeValue) {
                // Convert string value to PolicyType enum
                foreach (PolicyType::cases() as $case) {
                    if ($case->value === $policyTypeValue) {
                        $policyType = $case;
                        break;
                    }
                }
            }
        }

        // If no policy type found in quote, default to Health
        if (! $policyType) {
            $policyType = PolicyType::Health;
        }

        // Map quote data to policy
        return $this->state([
            'quote_id' => $quote->id,
            'contact_id' => $quote->contact_id,
            'user_id' => $quote->user_id,
            'insurance_company_id' => $quote->insurance_company_id,
            'policy_type' => $policyType,
            'quote_policy_types' => $quote->policy_types,
            'policy_zipcode' => $quote->zipcode,
            'policy_us_county' => $quote->county,
            'policy_city' => $quote->city,
            'policy_us_state' => $quote->state_province,
            'estimated_household_income' => $quote->estimated_household_income,
            'total_family_members' => $quote->total_family_members,
            'total_applicants' => $quote->total_applicants,
            'status' => PolicyStatus::Created,
        ]);
    }
}
