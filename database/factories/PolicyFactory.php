<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Enums\FamilyRelationship;
use App\Enums\PolicyInscriptionType;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Models\Agent;
use App\Models\Contact;
use App\Models\InsuranceCompany;
use App\Models\Policy;
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

        // Get current date
        $now = Carbon::now();

        // Calculate the current year and next year
        $currentYear = $now->year;
        $year = $now->month == 12 ? $currentYear + 1 : $currentYear;

        // Create random month and add years as needed
        $month = $this->faker->numberBetween(1, 12);

        // Default policy type is Health if not overridden by state
        $policyType = PolicyType::Health;

        // Create a Contact for the policy
        $contact = Contact::factory()->create();

        // Generate realistic household structure
        $householdMembers = $this->faker->numberBetween(1, 6);
        $totalApplicants = $this->faker->numberBetween(1, $householdMembers);
        $totalApplicantsWithMedicaid = $this->faker->boolean(30) ? $this->faker->numberBetween(0, $householdMembers - $totalApplicants) : 0;

        // Generate effective and expiration dates with the correct year
        $effectiveDate = Carbon::create($year, $month, 15);
        $expirationDate = Carbon::create($year, 12, 31);

        // Generate premium amount between $100 and $1000
        $premiumAmount = round($this->faker->randomFloat(2, 100, 1000), 2);

        return [
            'user_id' => $this->faker->randomElement(User::pluck('id')->toArray()),
            'contact_id' => $contact->id,
            'insurance_company_id' => $this->faker->randomElement(InsuranceCompany::pluck('id')->toArray()),
            'policy_type' => $policyType,
            'agent_id' => $this->faker->randomElement(Agent::pluck('id')->toArray()),
            'policy_zipcode' => $contact->zip_code,
            'policy_us_county' => $contact->county,
            'policy_city' => $contact->city,
            'policy_us_state' => $contact->state_province,
            'policy_plan' => $this->faker->randomElement(['Bronze', 'Silver', 'Gold', 'Platinum']),
            'policy_inscription_type' => $this->faker->randomElement(PolicyInscriptionType::cases()),
            'policy_total_cost' => $premiumAmount * 12,
            'policy_total_subsidy' => round($this->faker->randomFloat(2, 0, $premiumAmount * 12 * 0.7), 2),
            'premium_amount' => $premiumAmount,
            'estimated_household_income' => $this->faker->randomFloat(2, 25000, 100000),
            'recurring_payment' => $this->faker->boolean(80),
            'effective_date' => $effectiveDate,
            'expiration_date' => $expirationDate,
            'policy_year' => $year,
            'first_payment_date' => $effectiveDate,
            'preferred_payment_day' => $this->faker->numberBetween(1, 28),
            'initial_paid' => $this->faker->boolean(70),
            'autopay' => $this->faker->boolean(60),
            'requires_aca' => $this->faker->boolean(50),
            'aca' => $this->faker->boolean(40),
            'document_status' => $this->faker->randomElement(DocumentStatus::cases()),
            'observations' => $this->faker->optional(0.7)->paragraph(),
            'client_notified' => $this->faker->boolean(50),
            'life_offered' => $this->faker->boolean(30),
            'contact_is_applicant' => $this->faker->boolean(90),
            'total_family_members' => $householdMembers,
            'total_applicants' => $totalApplicants,
            'total_applicants_with_medicaid' => $totalApplicantsWithMedicaid,
            'start_date' => $effectiveDate,
            'end_date' => $expirationDate,
            'status' => $this->faker->randomElement(PolicyStatus::cases()),
            'is_initial_verification_complete' => $this->faker->boolean(50),
            'notes' => $this->faker->optional(0.5)->paragraph(),
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
            'effective_date' => $date,
        ]);
    }

    public function createdOnMonth(int $month): static
    {
        $date = Carbon::parse("2025-$month-01");

        return $this->state([
            'created_at' => $date->startOfMonth(),
            'updated_at' => $date->startOfMonth(),
            'effective_date' => $date->startOfMonth(),
            'policy_year' => $date->year,
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
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Policy $policy) {
            // Get the contact from the policy
            $contact = $policy->contact;

            // First record: the policy contact with relationship 'self' and is_covered_by_policy = true
            $age = Carbon::now()->diffInYears($contact->date_of_birth);
            $isSelfEmployed = $this->faker->boolean(30);  // 30% chance of being self-employed

            $policy->policyApplicants()->create([
                'contact_id' => $contact->id,
                'relationship_with_policy_owner' => FamilyRelationship::Self,
                'is_covered_by_policy' => true,
                'medicaid_client' => false,
                'sort_order' => 0,
                'is_self_employed' => $isSelfEmployed,
                'self_employed_profession' => $isSelfEmployed ? $this->faker->jobTitle() : null,
            ]);

            // Total covered applicants (excluding the policy owner)
            $totalCoveredApplicants = $policy->total_applicants - 1;

            // Create additional covered applicants
            for ($i = 0; $i < $totalCoveredApplicants; $i++) {
                // Create a new contact for each applicant
                $applicantContact = Contact::factory()->create();

                // Determine age-appropriate relationship
                $age = Carbon::now()->diffInYears($applicantContact->date_of_birth);
                $relationship = $this->determineRelationship($age);

                $isSelfEmployed = $age >= 18 ? $this->faker->boolean(30) : false;

                $policy->policyApplicants()->create([
                    'contact_id' => $applicantContact->id,
                    'relationship_with_policy_owner' => $relationship,
                    'is_covered_by_policy' => true,
                    'medicaid_client' => false,
                    'sort_order' => $i + 1,
                    'is_self_employed' => $isSelfEmployed,
                    'self_employed_profession' => $isSelfEmployed ? $this->faker->jobTitle() : null,
                ]);
            }

            // Create medicaid applicants (not covered by policy)
            $totalMedicaidApplicants = $policy->total_applicants_with_medicaid;

            for ($i = 0; $i < $totalMedicaidApplicants; $i++) {
                // Create a new contact for each medicaid applicant
                $medicaidContact = Contact::factory()->create();

                // Determine age-appropriate relationship
                $age = Carbon::now()->diffInYears($medicaidContact->date_of_birth);
                $relationship = $this->determineRelationship($age);

                // Medicaid applicants typically have lower or no income
                $isSelfEmployed = false;
                $income = $age >= 18 ? $this->calculateYearlyIncome($isSelfEmployed, true) : 0;
                // No need to track total income as we're not updating the policy's income

                $policy->policyApplicants()->create([
                    'contact_id' => $medicaidContact->id,
                    'relationship_with_policy_owner' => $relationship,
                    'is_covered_by_policy' => false,
                    'medicaid_client' => true,
                    'sort_order' => $i + $totalCoveredApplicants + 1,
                    'is_self_employed' => $isSelfEmployed,
                    'yearly_income' => $income,
                    'income_per_hour' => $income > 0 ? $this->faker->randomFloat(2, 7.5, 15) : null,
                    'hours_per_week' => $income > 0 ? $this->faker->numberBetween(5, 20) : null,
                    'weeks_per_year' => $income > 0 ? $this->faker->numberBetween(30, 45) : null,
                ]);
            }

        });
    }

    /**
     * Determine relationship based on age
     */
    private function determineRelationship($age): FamilyRelationship
    {
        if ($age >= 18 && $age <= 80) {
            // Adult - could be spouse, parent, sibling
            return $this->faker->randomElement([
                FamilyRelationship::Spouse,
                FamilyRelationship::Father,
                FamilyRelationship::Concubine,
            ]);
        } elseif ($age < 18) {
            // Child
            return FamilyRelationship::Son;
        } else {
            // Fallback for other ages
            return $this->faker->randomElement([
                FamilyRelationship::Spouse,
                FamilyRelationship::Father,
                FamilyRelationship::Son,
                FamilyRelationship::Stepson,
                FamilyRelationship::Other,
            ]);
        }
    }

    /**
     * Calculate yearly income for an applicant
     *
     * @param  bool  $isSelfEmployed  Whether the applicant is self-employed
     * @param  bool  $isLowIncome  Whether to generate a low income (for Medicaid applicants)
     * @return float The calculated yearly income
     */
    private function calculateYearlyIncome(bool $isSelfEmployed, bool $isLowIncome = false): float
    {
        // No income 10% of the time for regular applicants, 40% for low income
        $noIncomeChance = $isLowIncome ? 40 : 10;
        if ($this->faker->boolean($noIncomeChance)) {
            return 0;
        }

        if ($isSelfEmployed) {
            // Self employed income ranges
            if ($isLowIncome) {
                return round($this->faker->randomFloat(2, 5000, 20000), 2);
            }

            return round($this->faker->randomFloat(2, 25000, 120000), 2);
        } else {
            // Hourly wage calculation
            $hourlyRate = $isLowIncome
                ? $this->faker->randomFloat(2, 7.25, 15) // Minimum wage to low wage
                : $this->faker->randomFloat(2, 15, 40); // Average to higher wage

            $hoursPerWeek = $isLowIncome
                ? $this->faker->numberBetween(10, 30) // Part-time likely
                : $this->faker->numberBetween(20, 40); // Part to full-time

            $weeksPerYear = $isLowIncome
                ? $this->faker->numberBetween(30, 48) // Less consistent work
                : $this->faker->numberBetween(48, 52); // More consistent work

            return round($hourlyRate * $hoursPerWeek * $weeksPerYear, 2);
        }
    }
}
