<?php

namespace Database\Factories;

use App\Enums\FamilyRelationship;
use App\Enums\Gender;
use App\Enums\PolicyType;
use App\Enums\QuoteStatus;
use App\Models\Agent;
use App\Models\Contact;
use App\Models\User;
use App\ValueObjects\Applicant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quote>
 */
class QuoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $faker;

    /**
     * Create a new factory instance.
     */
    public function definition(): array
    {
        $this->faker = \Faker\Factory::create('es_VE');
        // Get essential models
        $contact = Contact::factory()->create();
        $user = User::inRandomOrder()->first();
        $agent = Agent::inRandomOrder()->first();

        // Generate 0-3 additional applicants
        $totalMeicaidMembers = $this->faker->numberBetween(0, 2);
        $totalFamilyMembers = $this->faker->numberBetween($totalMeicaidMembers + 1, 5);
        $totalApplicants = $this->faker->numberBetween(1, $totalFamilyMembers - $totalMeicaidMembers);

        // Generate contact information
        $contactInformation = $this->generateContactInformation($contact);

        // Create applicants array - ensuring the contact is the first applicant with relationship "self"
        $applicants = $this->createApplicantsArray($contact, $totalApplicants, $totalMeicaidMembers);

        // Generate random dates
        $startDate = $this->faker->dateTimeBetween('-1 month', '+1 month');
        $endDate = (clone $startDate)->modify('+1 year');
        $validUntil = (clone $startDate)->modify('+30 days');

        // Created date according to year
        $year = Carbon::now()->subYears(rand(0, 1))->year;

        // If it's the current year, make sure the date is before today
        if ($year === Carbon::now()->year) {
            $month = rand(1, Carbon::now()->month);
            $maxDay = $month === Carbon::now()->month ? Carbon::now()->day - 1 : 28;
            $day = $maxDay > 0 ? rand(1, $maxDay) : 1;
        } else {
            $month = rand(1, 12);
            $day = rand(1, 28);
        }

        $createdDate = Carbon::create($year, $month, $day, 0, 0, 0);
        $status = $this->faker->randomElement(QuoteStatus::cases());

        if (in_array($status->value, [
            QuoteStatus::Rejected->value,
            QuoteStatus::Converted->value,
            QuoteStatus::Sent->value,
            QuoteStatus::Accepted->value,
        ])) {
            $updatedDate = $createdDate->copy()->addDays(rand(3, 45));
        } else {
            $updatedDate = $createdDate->copy();
        }

        // Calculate household income from applicants
        $estimatedHouseholdIncome = 0;
        foreach ($applicants as $applicant) {
            $estimatedHouseholdIncome += $applicant['yearly_income'] ?? 0;
        }

        // If no income was calculated, generate a random value
        if ($estimatedHouseholdIncome <= 0) {
            $estimatedHouseholdIncome = $this->faker->randomFloat(2, 20000, 150000);
        }

        // Generate random policy types (1-3 types) - only if not already set
        $policyTypes = [];
        if (! isset($this->states['policy_types'])) {
            $policyTypesCases = PolicyType::cases();
            $policyTypesCount = $this->faker->numberBetween(1, 3);

            for ($i = 0; $i < $policyTypesCount; $i++) {
                $policyType = $this->faker->randomElement($policyTypesCases);
                $policyTypes[$policyType->value] = $policyType->value;
            }
        }

        // Calculate Kynect FPL threshold
        $kynectFplThreshold = null;
        try {
            $kynectFpl = \App\Models\KynectFPL::threshold(2024, $totalFamilyMembers);
            $kynectFplThreshold = $kynectFpl * 12;
        } catch (\Exception $e) {
            // Fallback if KynectFPL model doesn't exist
            $kynectFplThreshold = $totalFamilyMembers * 15000;
        }

        $data = [
            'contact_id' => $contact->id,
            'contact_information' => $contactInformation,
            'user_id' => $user->id,
            'policy_id' => null,
            'agent_id' => $agent?->id,
            'year' => Carbon::now()->subYears(rand(0, 1))->year,
            'state_province' => $contactInformation['state'] ?? null,

            // Applicants Information
            'applicants' => $applicants,
            'total_family_members' => $totalFamilyMembers,
            'total_applicants' => $totalApplicants,
            'total_medicaid' => $totalMeicaidMembers,

            // Additional Information
            'estimated_household_income' => round($estimatedHouseholdIncome, 2),
            'preferred_doctor' => $this->faker->optional()->name(),
            'prescription_drugs' => $this->generatePrescriptionDrugs(),

            // Quote Status and Dates
            'status' => $status,
            'notes' => $this->faker->optional()->paragraph(),
        ];

        // Add policy_types only if they were generated (not overridden by state)
        if (! isset($this->states['policy_types'])) {
            $data['policy_types'] = array_values($policyTypes);
        }

        return $data;
    }

    /**
     * Create the applicants array with the contact as the first applicant (relationship: self)
     */
    private function createApplicantsArray(Contact $contact, int $totalApplicants, int $totalMeicaidMembers): array
    {
        $applicants = [];

        // Add main contact as the first applicant with relationship "self"
        $applicants[] = [
            'relationship' => FamilyRelationship::Self->value,
            'full_name' => $contact->full_name,
            'date_of_birth' => $contact->date_of_birth,
            'age' => Carbon::parse($contact->date_of_birth)->age ?? $this->faker->numberBetween(18, 80),
            'is_covered' => true,
            'gender' => $contact->gender ?? $this->faker->randomElement(Gender::cases())->value,
            'is_self_employed' => $isSelfEmployed = $this->faker->boolean(30),
            'employeer_name' => $isSelfEmployed ? null : $this->faker->company(),
            'employement_role' => $isSelfEmployed ? null : $this->faker->jobTitle(),
            'employeer_phone' => $isSelfEmployed ? null : $this->faker->phoneNumber(),
            'income_per_hour' => $isSelfEmployed ? null : $this->faker->randomFloat(2, 10, 30),
            'hours_per_week' => $isSelfEmployed ? null : $this->faker->numberBetween(10, 40),
            'income_per_extra_hour' => $isSelfEmployed ? null : $this->faker->optional(0.6)->randomFloat(2, 15, 45),
            'extra_hours_per_week' => $isSelfEmployed ? null : $this->faker->optional(0.6)->numberBetween(1, 10),
            'weeks_per_year' => $isSelfEmployed ? null : $this->faker->numberBetween(40, 52),
            'yearly_income' => $this->calculateYearlyIncome($isSelfEmployed),
            'self_employed_yearly_income' => $isSelfEmployed ? $this->faker->randomFloat(2, 20000, 60000) : null,
        ];

        // Add additional applicants
        for ($i = 0; $i < $totalApplicants - 1; $i++) {
            $gender = $this->faker->randomElement(Gender::cases())->value;
            $age = $this->faker->numberBetween(1, 80);
            $relationship = $this->determineRelationship($age);
            $isSelfEmployed = $age >= 18 ? $this->faker->boolean(30) : false;

            $applicants[] = [
                'relationship' => $relationship,
                'full_name' => $this->faker->name($gender === 'male' ? 'male' : 'female'),
                'date_of_birth' => Carbon::now()->subYears($age)->subDays($this->faker->numberBetween(0, 364))->format('Y-m-d'),
                'age' => $age,
                'is_covered' => true,
                'gender' => $gender,
                'is_self_employed' => $isSelfEmployed,
                'employeer_name' => ($age >= 18 && ! $isSelfEmployed) ? $this->faker->optional(0.7)->company() : null,
                'employement_role' => ($age >= 18 && ! $isSelfEmployed) ? $this->faker->optional(0.7)->jobTitle() : null,
                'employeer_phone' => ($age >= 18 && ! $isSelfEmployed) ? $this->faker->optional(0.7)->phoneNumber() : null,
                'income_per_hour' => ($age >= 18 && ! $isSelfEmployed) ? $this->faker->optional(0.7)->randomFloat(2, 10, 30) : null,
                'hours_per_week' => ($age >= 18 && ! $isSelfEmployed) ? $this->faker->optional(0.7)->numberBetween(10, 40) : null,
                'income_per_extra_hour' => ($age >= 18 && ! $isSelfEmployed) ? $this->faker->optional(0.4)->randomFloat(2, 15, 45) : null,
                'extra_hours_per_week' => ($age >= 18 && ! $isSelfEmployed) ? $this->faker->optional(0.4)->numberBetween(1, 10) : null,
                'weeks_per_year' => ($age >= 18 && ! $isSelfEmployed) ? $this->faker->optional(0.7)->numberBetween(40, 52) : null,
                'yearly_income' => ($age >= 18) ? $this->calculateYearlyIncome($isSelfEmployed) : null,
                'self_employed_yearly_income' => ($age >= 18 && $isSelfEmployed) ? $this->faker->randomFloat(2, 20000, 60000) : null,
            ];
        }

        for ($i = 0; $i < $totalMeicaidMembers; $i++) {
            $gender = $this->faker->randomElement(Gender::cases())->value;
            $age = $this->faker->numberBetween(1, 80);
            $relationship = $this->determineRelationship($age);
            $isSelfEmployed = $age >= 18 ? $this->faker->boolean(30) : false;

            $applicants[] = [
                'relationship' => $relationship,
                'full_name' => $this->faker->name($gender === 'male' ? 'male' : 'female'),
                'date_of_birth' => Carbon::now()->subYears($age)->subDays($this->faker->numberBetween(0, 364))->format('Y-m-d'),
                'age' => $age,
                'is_covered' => false,
                'is_eligible_for_coverage' => true,
                'gender' => $gender,
                'is_self_employed' => $isSelfEmployed,
                'employeer_name' => ($age >= 18 && ! $isSelfEmployed) ? $this->faker->optional(0.7)->company() : null,
                'employement_role' => ($age >= 18 && ! $isSelfEmployed) ? $this->faker->optional(0.7)->jobTitle() : null,
                'employeer_phone' => ($age >= 18 && ! $isSelfEmployed) ? $this->faker->optional(0.7)->phoneNumber() : null,
                'income_per_hour' => ($age >= 18 && ! $isSelfEmployed) ? $this->faker->optional(0.7)->randomFloat(2, 10, 30) : null,
                'hours_per_week' => ($age >= 18 && ! $isSelfEmployed) ? $this->faker->optional(0.7)->numberBetween(10, 40) : null,
                'income_per_extra_hour' => ($age >= 18 && ! $isSelfEmployed) ? $this->faker->optional(0.4)->randomFloat(2, 15, 45) : null,
                'extra_hours_per_week' => ($age >= 18 && ! $isSelfEmployed) ? $this->faker->optional(0.4)->numberBetween(1, 10) : null,
                'weeks_per_year' => ($age >= 18 && ! $isSelfEmployed) ? $this->faker->optional(0.7)->numberBetween(40, 52) : null,
                'yearly_income' => ($age >= 18) ? $this->calculateYearlyIncome($isSelfEmployed) : null,
                'self_employed_yearly_income' => ($age >= 18 && $isSelfEmployed) ? $this->faker->randomFloat(2, 20000, 60000) : null,
            ];
        }

        return $applicants;
    }

    /**
     * Calculate yearly income based on employment type and parameters
     */
    private function calculateYearlyIncome(bool $isSelfEmployed): float
    {
        if ($isSelfEmployed) {
            return $this->faker->randomFloat(2, 20000, 60000);
        }

        $incomePerHour = $this->faker->randomFloat(2, 10, 30);
        $hoursPerWeek = $this->faker->numberBetween(10, 40);
        $weeksPerYear = $this->faker->numberBetween(40, 52);

        // Calculate base yearly income
        $yearlyIncome = $incomePerHour * $hoursPerWeek * $weeksPerYear;

        // Add extra hours if applicable (60% chance)
        $hasExtraHours = $this->faker->boolean(60);
        if ($hasExtraHours) {
            $incomePerExtraHour = $this->faker->randomFloat(2, $incomePerHour, $incomePerHour * 2);
            $extraHoursPerWeek = $this->faker->numberBetween(1, 10);
            $yearlyIncome += $incomePerExtraHour * $extraHoursPerWeek * $weeksPerYear;
        }

        return $yearlyIncome;
    }

    /**
     * Determine relationship based on age
     */
    private function determineRelationship(int $age): string
    {
        if ($age >= 18 && $age <= 80) {
            // Adult - could be spouse, parent, sibling
            return $this->faker->randomElement([
                FamilyRelationship::Spouse->value,
                FamilyRelationship::Father->value,
                FamilyRelationship::Son->value,
            ]);
        } elseif ($age < 18) {
            // Child
            return FamilyRelationship::Son->value;
        } else {
            // Fallback for other ages
            return $this->faker->randomElement(FamilyRelationship::cases())->value;
        }
    }

    /**
     * Generate contact information array
     */
    private function generateContactInformation(Contact $contact): array
    {
        return [
            'date_of_birth' => $contact->date_of_birth,
            'gender' => $contact->gender,
            'email' => $contact->email_address,
            'phone' => $contact->phone,
            'phone2' => $contact->phone2,
            'state' => $contact->state_province,
            'zip_code' => $contact->zip_code,
            'city' => $contact->city,
            'county' => $contact->county,
            'preferred_language' => $contact->preferred_language ?? 'spanish',
        ];
    }

    /**
     * Generate prescription drugs array
     */
    private function generatePrescriptionDrugs(int $min = 0, int $max = 5): array
    {
        $drugs = [];
        $count = $this->faker->numberBetween($min, $max);

        $commonDrugs = [
            'Lisinopril', 'Atorvastatin', 'Levothyroxine', 'Metformin',
            'Amlodipine', 'Metoprolol', 'Omeprazole', 'Simvastatin',
            'Losartan', 'Albuterol', 'Gabapentin', 'Hydrochlorothiazide',
            'Sertraline', 'Acetaminophen', 'Ibuprofen', 'Aspirin',
            'Fluoxetine', 'Amoxicillin', 'Prednisone', 'Furosemide',
        ];

        for ($i = 0; $i < $count; $i++) {
            $drugs[] = [
                'name' => $this->faker->randomElement($commonDrugs),
                'dosage' => $this->faker->randomElement(['5mg', '10mg', '20mg', '25mg', '50mg', '100mg']),
                'frequency' => $this->faker->randomElement(['daily', 'twice daily', 'as needed', 'weekly']),
                'reason' => $this->faker->optional()->sentence(3),
            ];
        }

        return $drugs;
    }

    /**
     * Create a quote with Life policy type
     */
    public function withHealthPolicy(): static
    {
        return $this->state([
            'policy_types' => [PolicyType::Health->value],
        ]);
    }
}
