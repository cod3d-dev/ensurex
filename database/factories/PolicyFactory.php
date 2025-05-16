<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Enums\FamilyRelationship;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Enums\RenewalStatus;
use App\Enums\UsState;
use App\Models\Agent;
use App\Models\Contact;
use App\Models\InsuranceCompany;
use App\Models\Quote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Policy;
use Illuminate\Support\Facades\DB;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Policy>
 */
class PolicyFactory extends Factory
{
    private static $lastCreatedAt = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

     public $faker;

    /**
     * Create a new factory instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->faker = \Faker\Factory::create('es_VE');
        
        if (self::$lastCreatedAt === null) {
            $latestPolicy = Policy::orderBy('created_at', 'desc')->first();
            self::$lastCreatedAt = $latestPolicy ? $latestPolicy->created_at : now()->subYears(4);
        }
    }


    public function definition(): array
    {
        // Create a new contact for the policy owner instead of using an existing one
        $contact = Contact::factory()->create();
        $user = User::inRandomOrder()->first();
        $insuranceCompany = InsuranceCompany::inRandomOrder()->first();
        $agent = Agent::inRandomOrder()->first();
        // $quote = Quote::inRandomOrder()->first();

        // Generate 0-3 additional applicants
        $additionalApplicantsCount = $this->faker->numberBetween(0, 4);
        $totalFamilyMembers = $additionalApplicantsCount + 1; // Main applicant + additional applicants

        // Generate random dates - up to 3 months from today, no future dates, not from past year
        $today = now();
        $threeMonthsAgo = (clone $today)->subMonths(3);
        $startOfCurrentYear = Carbon::create($today->year, 1, 1);
        $minDate = max($threeMonthsAgo, $startOfCurrentYear);
        $effectiveDate = $this->faker->dateTimeBetween($minDate, $today);
        $expirationDate = (clone $effectiveDate)->modify('+1 year');

        // Generate contact information
        $contactInformation = $this->generateContactInformation($contact);

        // Generate prescription drugs
        $prescriptionDrugs = $this->generatePrescriptionDrugs();

        // Default policy type (can be overridden by state methods)
        // 70% chance to be a health policy, 30% chance to be another type
        $policyType = $this->faker->boolean(70) 
            ? PolicyType::Health 
            : $this->faker->randomElement(array_filter(PolicyType::cases(), fn($type) => $type !== PolicyType::Health));
        
        // Generate life insurance data if policy type is Life
        $lifeInsurance = null;
        if ($policyType === PolicyType::Life) {
            // Create a placeholder for main applicant data to generate life insurance
            $mainApplicantData = [
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
                'date_of_birth' => $contact->date_of_birth,
                'gender' => $contact->gender
            ];
            
            $lifeInsurance = $this->generateLifeInsuranceData($mainApplicantData);
        }

        // Created date according to year
        $minDate = max($contact->created_at, self::$lastCreatedAt);
        $maxDate = Carbon::instance(clone $minDate)->addHours(36);
        $createdDate = $this->faker->dateTimeBetween($minDate, $maxDate);
        self::$lastCreatedAt = $createdDate;

        return [
            // Basic Information
            'created_at' => $createdDate,
            'contact_id' => $contact->id,
            'user_id' => $user->id,
            'insurance_company_id' => $insuranceCompany->id,
            'policy_type' => $policyType,
            'quote_id' => $quote?->id,
            'agent_id' => $agent->id,
            'policy_number' => $this->faker->regexify('[A-Z]{2}[0-9]{6}'),
            'policy_year' => now()->format('Y'),
            'policy_us_state' => $this->faker->randomElement(UsState::class),
            'kynect_case_number' => $this->faker->optional()->regexify('[0-9]{8}'),
            'insurance_company_policy_number' => $this->faker->optional()->regexify('[A-Z]{3}[0-9]{7}'),
            'policy_plan' => $this->faker->randomElement(['Bronze', 'Silver', 'Gold', 'Platinum']),
            'policy_level' => $this->faker->randomElement(['Basic', 'Standard', 'Premium']),

            // Financial Information
            'policy_total_cost' => $totalCost = $this->faker->randomFloat(2, 5000, 20000),
            'policy_total_subsidy' => $subsidy = $this->faker->optional(0.7)->randomFloat(2, 1000, $totalCost * 0.8),
            'premium_amount' => $subsidy ? $totalCost - $subsidy : $totalCost,
            'coverage_amount' => $this->faker->randomFloat(2, 100000, 1000000),
            'recurring_payment' => $this->faker->boolean(80),

            // Dates
            'effective_date' => $effectiveDate,
            'expiration_date' => $expirationDate,
            'first_payment_date' => $this->faker->dateTimeBetween('-30 days', '+30 days'),
            'last_payment_date' => $this->faker->optional(0.5)->dateTimeBetween('-30 days', 'now'),
            'preferred_payment_day' => $this->faker->numberBetween(1, 28),

            // Payment Status
            'initial_paid' => $this->faker->boolean(70),
            'autopay' => $this->faker->boolean(60),
            'aca' => $this->faker->boolean(50),
            'document_status' => $this->faker->randomElement(DocumentStatus::class),
            'observations' => $this->faker->optional(0.7)->paragraph(),
            'client_notified' => $this->faker->boolean(80),

            // Family Information - We'll now use the pivot table instead of these JSON fields
            // These fields are kept for backward compatibility but will be empty
            'main_applicant' => null,
            'additional_applicants' => null,
            'total_family_members' => $totalFamilyMembers,
            'total_applicants' => $totalFamilyMembers,
            'total_applicants_with_medicaid' => 0, // Will be calculated in the configure method
            'estimated_household_income' => 0, // Will be calculated in the configure method
            
            'preferred_doctor' => $this->faker->optional(0.5)->name(),
            'prescription_drugs' => $prescriptionDrugs,
            'contact_information' => $contactInformation,
            
            // Life Insurance Data
            'life_insurance' => $lifeInsurance,

            // Policy Status and Dates
            'start_date' => $effectiveDate,
            'end_date' => $expirationDate,
            'status' => $this->faker->randomElement(PolicyStatus::class),
            'status_changed_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'status_changed_by' => $user->id,
            'notes' => $this->faker->optional(0.7)->paragraph(),

            // Payment Information (encrypted fields)
            'payment_card_type' => $this->faker->optional(0.6)->randomElement(['visa', 'mastercard', 'amex', 'discover']),
            'payment_card_bank' => $this->faker->optional(0.6)->company(),
            'payment_card_holder' => $this->faker->optional(0.6)->name(),
            'payment_card_number' => $this->faker->optional(0.6)->creditCardNumber(),
            'payment_card_exp_month' => $this->faker->optional(0.6)->numberBetween(1, 12),
            'payment_card_exp_year' => $this->faker->optional(0.6)->numberBetween(now()->format('Y'), now()->addYears(5)->format('Y')),
            'payment_card_cvv' => $this->faker->optional(0.6)->numberBetween(100, 999),

            // Bank Account Information (encrypted fields)
            'payment_bank_account_bank' => $this->faker->optional(0.4)->company(),
            'payment_bank_account_holder' => $this->faker->optional(0.4)->name(),
            'payment_bank_account_aba' => $this->faker->optional(0.4)->regexify('[0-9]{9}'),
            'payment_bank_account_number' => $this->faker->optional(0.4)->regexify('[0-9]{10,12}'),

            // Billing Address (encrypted fields)
            'billing_address_1' => $this->faker->optional(0.7)->streetAddress(),
            'billing_address_2' => $this->faker->optional(0.3)->secondaryAddress(),
            'billing_address_city' => $this->faker->optional(0.7)->city(),
            'billing_address_state' => $this->faker->optional(0.7)->state(),
            'billing_address_zip' => $this->faker->optional(0.7)->postcode(),

            // Renewal fields
            'is_renewal' => $isRenewal = $this->faker->boolean(20),
            'renewed_from_policy_id' => $isRenewal ? null : null, // Would need to set this manually
            'renewed_to_policy_id' => null, // Would need to set this manually
            'renewed_by' => $isRenewal ? $user->id : null,
            'renewal_status' => $isRenewal ? $this->faker->randomElement(RenewalStatus::cases()) : null,
            'renewal_notes' => $isRenewal ? $this->faker->optional(0.7)->paragraph() : null,

            // Audit Information
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ];
    }

    // Previous methods for creating applicants have been removed as we now use the pivot table approach

    /**
     * Generate contact information array
     */
    private function generateContactInformation(Contact $contact): array
    {
        return [
            'name' => trim($contact->first_name . ' ' . $contact->last_name),
            'email' => $contact->email_address,
            'phone' => $contact->phone,
            'phone2' => $contact->phone2,
            'whatsapp' => $contact->whatsapp,
            'address' => [
                'line1' => $contact->address_line_1,
                'line2' => $contact->address_line_2,
                'city' => $contact->city,
                'state' => $contact->state_province,
                'zip' => $contact->zip_code,
                'county' => $contact->county,
            ],
            'mailing_address' => $contact->is_same_as_physical ? null : [
                'line1' => $contact->mailing_address_line_1,
                'line2' => $contact->mailing_address_line_2,
                'city' => $contact->mailing_city,
                'state' => $contact->mailing_state_province,
                'zip' => $contact->mailing_zip_code,
            ],
            'preferred_language' => $contact->preferred_language ?? 'spanish',
            'preferred_contact_method' => $contact->preferred_contact_method,
            'preferred_contact_time' => $contact->preferred_contact_time,
        ];
    }

    /**
     * Generate prescription drugs array
     */
    private function generatePrescriptionDrugs(int $min = 0, int $max = 5): array
    {
        $drugs = [];
        $count = $this->faker->numberBetween($min, $max);

        if ($count <= 0) {
            return $drugs;
        }

        $commonDrugs = [
            'Lisinopril', 'Atorvastatin', 'Levothyroxine', 'Metformin',
            'Amlodipine', 'Metoprolol', 'Omeprazole', 'Simvastatin',
            'Losartan', 'Albuterol', 'Gabapentin', 'Hydrochlorothiazide',
            'Sertraline', 'Acetaminophen', 'Ibuprofen', 'Aspirin',
            'Amoxicillin', 'Azithromycin', 'Fluoxetine', 'Prednisone'
        ];

        $drugIndices = array_rand($commonDrugs, min($count, count($commonDrugs)));

        if (!is_array($drugIndices)) {
            $drugIndices = [$drugIndices];
        }

        foreach ($drugIndices as $index) {
            $drugs[] = [
                'name' => $commonDrugs[$index],
                'dosage' => $this->faker->randomElement(['5mg', '10mg', '20mg', '25mg', '50mg', '100mg']),
                'frequency' => $this->faker->randomElement(['daily', 'twice daily', 'as needed', 'weekly']),
            ];
        }

        return $drugs;
    }

    /**
     * Calculate total income from all applicants
     */
    private function calculateTotalHouseholdIncome(array $mainApplicant, array $additionalApplicants): float
    {
        $totalIncome = $mainApplicant['yearly_income'] ?? 0;

        foreach ($additionalApplicants as $applicant) {
            $totalIncome += $applicant['yearly_income'] ?? 0;
        }

        return $totalIncome;
    }

    /**
     * Generate realistic life insurance data
     */
    private function generateLifeInsuranceData(array $mainApplicant): array
    {
        // Generate 1-3 beneficiaries
        $totalBeneficiaries = $this->faker->numberBetween(1, 3);
        $beneficiaries = [];
        $totalPercentage = 0;
        
        // Calculate percentages for beneficiaries
        $percentages = $this->distributePercentages($totalBeneficiaries);
        
        // Generate beneficiary data
        for ($i = 1; $i <= $totalBeneficiaries; $i++) {
            $relationship = $this->faker->randomElement(\App\Enums\FamilyRelationship::cases());
            $beneficiaries[$i] = [
                'name' => $this->faker->name(),
                'date_of_birth' => $this->faker->date('Y-m-d', '-30 years'),
                'relationship' => $relationship->value,
                'id_number' => $this->faker->regexify('[0-9]{8}'),
                'phone_number' => $this->faker->phoneNumber(),
                'email' => $this->faker->email(),
                'percentage' => $percentages[$i - 1],
            ];
            $totalPercentage += $percentages[$i - 1];
        }
        
        // Generate 0-2 contingent beneficiaries
        $totalContingents = $this->faker->numberBetween(0, 2);
        $contingents = [];
        $totalContingentPercentage = 0;
        
        if ($totalContingents > 0) {
            // Calculate percentages for contingent beneficiaries
            $contingentPercentages = $this->distributePercentages($totalContingents);
            
            // Generate contingent beneficiary data
            for ($i = 1; $i <= $totalContingents; $i++) {
                $relationship = $this->faker->randomElement(\App\Enums\FamilyRelationship::cases());
                $contingents[$i] = [
                    'name' => $this->faker->name(),
                    'date_of_birth' => $this->faker->date('Y-m-d', '-30 years'),
                    'relationship' => $relationship->value,
                    'id_number' => $this->faker->regexify('[0-9]{8}'),
                    'phone_number' => $this->faker->phoneNumber(),
                    'email' => $this->faker->email(),
                    'percentage' => $contingentPercentages[$i - 1],
                ];
                $totalContingentPercentage += $contingentPercentages[$i - 1];
            }
        }
        
        // Generate physical data
        $heightCm = $this->faker->numberBetween(150, 200);
        $heightFeet = number_format($heightCm / 30.48, 2);
        $weightKg = $this->faker->numberBetween(50, 120);
        $weightLbs = number_format($weightKg / 0.45359237, 2);
        
        // Generate medical data
        $hasDiagnosis = $this->faker->boolean(30);
        $diagnosisDate = $hasDiagnosis ? $this->faker->dateTimeBetween('-5 years', 'now')->format('Y-m-d') : null;
        $diagnosis = $hasDiagnosis ? $this->faker->sentence(10) : null;
        
        $hasDisease = $this->faker->boolean(20);
        $disease = $hasDisease ? $this->faker->randomElement(['Diabetes', 'Hipertensión', 'Asma', 'Artritis', 'Migraña']) : null;
        
        $hasBeenHospitalized = $this->faker->boolean(25);
        $hospitalizedDate = $hasBeenHospitalized ? $this->faker->dateTimeBetween('-3 years', 'now')->format('Y-m-d') : null;
        
        // Generate family medical history
        $fatherIsAlive = $this->faker->boolean(70);
        $fatherAge = $fatherIsAlive ? $this->faker->numberBetween(50, 90) : $this->faker->numberBetween(50, 80);
        $fatherDeathReason = $fatherIsAlive ? null : $this->faker->randomElement(['Cáncer', 'Infarto', 'Accidente', 'Causas naturales']);
        
        $motherIsAlive = $this->faker->boolean(75);
        $motherAge = $motherIsAlive ? $this->faker->numberBetween(50, 90) : $this->faker->numberBetween(50, 80);
        $motherDeathReason = $motherIsAlive ? null : $this->faker->randomElement(['Cáncer', 'Infarto', 'Accidente', 'Causas naturales']);
        
        $hasFamilyMemberWithDisease = $this->faker->boolean(40);
        $familyMemberRelationship = $hasFamilyMemberWithDisease ? $this->faker->randomElement(\App\Enums\FamilyRelationship::cases()) : null;
        $familyMemberDiseaseDescription = $hasFamilyMemberWithDisease ? $this->faker->randomElement(['Cáncer', 'Diabetes', 'Enfermedad cardíaca', 'Alzheimer', 'Parkinson']) : null;
        
        // Generate employment information
        $employerName = $this->faker->company();
        $jobTitle = $this->faker->jobTitle();
        $employmentPhone = $this->faker->phoneNumber();
        $employmentAddress = $this->faker->address();
        $employmentStartDate = $this->faker->dateTimeBetween('-10 years', '-1 month')->format('Y-m-d');
        
        // Compile all life insurance data
        return [
            'applicant' => [
                'height_cm' => $heightCm,
                'height_feet' => $heightFeet,
                'weight_kg' => $weightKg,
                'weight_lbs' => $weightLbs,
                'smoker' => $this->faker->boolean(20),
                'practice_extreme_sport' => $this->faker->boolean(15),
                'has_made_felony' => $this->faker->boolean(5),
                'has_declared_bankruptcy' => $this->faker->boolean(10),
                'plans_to_travel_abroad' => $this->faker->boolean(30),
                'allows_videocall' => $this->faker->boolean(90),
                'primary_doctor' => $this->faker->name('male'),
                'primary_doctor_phone' => $this->faker->phoneNumber(),
                'primary_doctor_address' => $this->faker->address(),
                'diagnosis_date' => $diagnosisDate,
                'diagnosis' => $diagnosis,
                'disease' => $disease,
                'drugs_prescribed' => $hasDiagnosis ? $this->faker->sentence(5) : null,
                'has_been_hospitalized' => $hasBeenHospitalized,
                'hospitalized_date' => $hospitalizedDate,
                'patrimony' => $this->faker->randomFloat(2, 10000, 500000),
            ],
            'father' => [
                'is_alive' => $fatherIsAlive,
                'age' => $fatherAge,
                'death_reason' => $fatherDeathReason,
            ],
            'mother' => [
                'is_alive' => $motherIsAlive,
                'age' => $motherAge,
                'death_reason' => $motherDeathReason,
            ],
            'family' => [
                'member_final_disease' => $hasFamilyMemberWithDisease,
                'member_final_disease_relationship' => $familyMemberRelationship,
                'member_final_disease_description' => $familyMemberDiseaseDescription,
            ],
            'employer' => [
                'name' => $employerName,
                'job_title' => $jobTitle,
                'employment_phone' => $employmentPhone,
                'employment_address' => $employmentAddress,
                'employment_start_date' => $employmentStartDate,
            ],
            'total_beneficiaries' => $totalBeneficiaries,
            'beneficiaries' => array_merge($beneficiaries, ['total_percentage' => $totalPercentage]),
            'total_contingents' => $totalContingents,
            'contingents' => $totalContingents > 0 ? array_merge($contingents, ['total_percentage' => $totalContingentPercentage]) : [],
        ];
    }
    
    /**
     * Distribute percentages to equal 100%
     */
    private function distributePercentages(int $count): array
    {
        if ($count === 1) {
            return [100];
        }
        
        $percentages = [];
        $remaining = 100;
        
        for ($i = 0; $i < $count - 1; $i++) {
            // For all but the last item, assign a random percentage
            $max = $remaining - ($count - $i - 1); // Ensure at least 1% for each remaining item
            $percentage = $this->faker->numberBetween(1, max(1, $max));
            $percentages[] = $percentage;
            $remaining -= $percentage;
        }
        
        // Assign the remaining percentage to the last item
        $percentages[] = $remaining;
        
        return $percentages;
    }
    
    /**
     * Configure the model factory to create a Life insurance policy.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function life()
    {
        return $this->state(function (array $attributes) {
            // Get the main applicant from attributes or use an empty array as fallback
            $mainApplicant = $attributes['main_applicant'] ?? [];
            
            // Force generate life insurance data regardless of what was in the attributes
            $lifeInsurance = $this->generateLifeInsuranceData($mainApplicant);
            
            return [
                'policy_type' => PolicyType::Life,
                'life_insurance' => $lifeInsurance,
            ];
        });
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterCreating(function (Policy $policy) {
            // Create applicant data for the policy owner
            $ownerIsSelf = $this->faker->boolean(80); // 80% chance the owner is also an applicant
            
            if ($ownerIsSelf) {
                // Add the policy owner as the main applicant
                $policy->applicants()->attach($policy->contact_id, [
                    'sort_order' => 0,
                    'relationship_with_policy_owner' => FamilyRelationship::Self,
                    'is_covered_by_policy' => true,
                    'medicaid_client' => $this->faker->boolean(20),
                    'is_self_employed' => $isSelfEmployed = $this->faker->boolean(30),
                    'self_employed_profession' => $isSelfEmployed ? $this->faker->jobTitle() : null,
                    'self_employed_yearly_income' => $isSelfEmployed ? $this->faker->randomFloat(2, 20000, 60000) : null,
                    'income_per_hour' => !$isSelfEmployed ? $this->faker->randomFloat(2, 15, 100) : null,
                    'hours_per_week' => !$isSelfEmployed ? $this->faker->numberBetween(10, 40) : null,
                    'weeks_per_year' => !$isSelfEmployed ? $this->faker->numberBetween(40, 52) : null,
                    'income_per_extra_hour' => !$isSelfEmployed ? $this->faker->optional(0.6)->randomFloat(2, 20, 150) : null,
                    'extra_hours_per_week' => !$isSelfEmployed ? $this->faker->optional(0.6)->numberBetween(1, 20) : null,
                    'yearly_income' => $isSelfEmployed 
                        ? ($this->faker->randomFloat(2, 20000, 60000)) 
                        : ($this->faker->randomFloat(2, 15, 100) * $this->faker->numberBetween(10, 40) * $this->faker->numberBetween(40, 52))
                ]);
            }
            
            // Generate 0-3 additional applicants
            $additionalApplicantsCount = $this->faker->numberBetween(0, 3);
            $totalMedicaidClients = 0;
            $totalHouseholdIncome = $ownerIsSelf ? $policy->applicants()->first()->pivot->yearly_income : 0;
            
            $relationships = ['spouse', 'child', 'parent', 'sibling'];
            
            for ($i = 0; $i < $additionalApplicantsCount; $i++) {
                // Create a new contact for each additional applicant
                $gender = $this->faker->randomElement(['male', 'female']);
                $firstName = $gender === 'male' ? $this->faker->firstNameMale() : $this->faker->firstNameFemale();
                $lastName = $this->faker->lastName();
                $relationship = $this->faker->randomElement($relationships);
                
                // Adjust age based on relationship
                $age = match($relationship) {
                    'spouse' => $this->faker->numberBetween(18, 80),
                    'child' => $this->faker->numberBetween(0, 26),
                    'parent' => $this->faker->numberBetween(45, 90),
                    'sibling' => $this->faker->numberBetween(18, 70),
                    default => $this->faker->numberBetween(18, 80),
                };
                
                $dob = Carbon::now()->subYears($age)->subDays($this->faker->numberBetween(0, 365))->format('Y-m-d');
                
                $contact = Contact::create([
                    'first_name' => $firstName,
                    'middle_name' => $this->faker->optional(0.3)->firstName(),
                    'last_name' => $lastName,
                    'second_last_name' => $this->faker->optional(0.3)->lastName(),
                    'gender' => $gender,
                    'date_of_birth' => $dob,
                    'email_address' => $relationship === 'child' && $age < 18 ? null : $this->faker->optional(0.7)->safeEmail(),
                    'phone' => $relationship === 'child' && $age < 18 ? null : $this->faker->phoneNumber(),
                    'immigration_status' => $this->faker->optional(0.5)->randomElement(['citizen', 'permanent_resident', 'temporary_resident', 'visa_holder']),
                    'ssn' => $this->faker->optional(0.7)->regexify('[0-9]{3}-[0-9]{2}-[0-9]{4}'),
                    'passport_number' => $this->faker->optional(0.3)->regexify('[A-Z][0-9]{8}'),
                    'green_card_number' => $this->faker->optional(0.3)->regexify('[A-Z][0-9]{8}'),
                    'green_card_expiration_date' => $this->faker->optional(0.3)->date(),
                    'work_permit_number' => $this->faker->optional(0.3)->regexify('[A-Z][0-9]{8}'),
                    'work_permit_expiration_date' => $this->faker->optional(0.3)->date(),
                    'driver_license_number' => $age >= 16 ? $this->faker->optional(0.5)->regexify('[A-Z][0-9]{7}') : null,
                    'driver_license_expiration_date' => $age >= 16 ? $this->faker->optional(0.5)->date() : null,
                    'marital_status' => $relationship === 'child' ? 'single' : $this->faker->randomElement(['single', 'married', 'divorced', 'widowed']),
                    'is_tobacco_user' => $this->faker->boolean(20),
                    'is_pregnant' => $gender === 'female' ? $this->faker->boolean(10) : false,
                    'height' => $this->faker->randomFloat(2, 3, 7),
                    'weight' => $this->faker->randomFloat(2, 30, 300),
                    'country_of_birth' => $this->faker->country(),
                    'created_at' => $policy->created_at,
                    'updated_at' => $policy->created_at,
                ]);
                
                // Only add income for applicants over 12 years old, with 40% chance
                $hasIncome = $age > 12 && $this->faker->boolean(40);
                $isSelfEmployed = $this->faker->boolean(30);
                $yearlyIncome = 0;
                
                if ($hasIncome) {
                    if ($isSelfEmployed) {
                        $yearlyIncome = $this->faker->randomFloat(2, 20000, 60000);
                    } else {
                        $incomePerHour = $this->faker->randomFloat(2, 15, 100);
                        $hoursPerWeek = $this->faker->numberBetween(10, 40);
                        $weeksPerYear = $this->faker->numberBetween(40, 52);
                        
                        // Calculate base yearly income
                        $yearlyIncome = $incomePerHour * $hoursPerWeek * $weeksPerYear;
                        
                        // Add extra hours if applicable (60% chance)
                        $hasExtraHours = $this->faker->boolean(60);
                        if ($hasExtraHours) {
                            $incomePerExtraHour = $this->faker->randomFloat(2, 20, 150);
                            $extraHoursPerWeek = $this->faker->numberBetween(1, 20);
                            $yearlyIncome += $incomePerExtraHour * $extraHoursPerWeek * $weeksPerYear;
                        }
                    }
                    
                    $totalHouseholdIncome += $yearlyIncome;
                }
                
                $medicaidClient = $this->faker->boolean(20);
                if ($medicaidClient) {
                    $totalMedicaidClients++;
                }
                
                // Attach the new contact to the policy as an applicant
                $policy->applicants()->attach($contact->id, [
                    'sort_order' => $i + 1, // Start at 1 since the owner is 0
                    'relationship_with_policy_owner' => FamilyRelationship::from($relationship),
                    'is_covered_by_policy' => $this->faker->boolean(90),
                    'medicaid_client' => $medicaidClient,
                    'is_self_employed' => $hasIncome ? $isSelfEmployed : null,
                    'self_employed_profession' => ($hasIncome && $isSelfEmployed) ? $this->faker->jobTitle() : null,
                    'self_employed_yearly_income' => ($hasIncome && $isSelfEmployed) ? $yearlyIncome : null,
                    'income_per_hour' => ($hasIncome && !$isSelfEmployed) ? $incomePerHour : null,
                    'hours_per_week' => ($hasIncome && !$isSelfEmployed) ? $hoursPerWeek : null,
                    'weeks_per_year' => ($hasIncome && !$isSelfEmployed) ? $weeksPerYear : null,
                    'income_per_extra_hour' => ($hasIncome && !$isSelfEmployed && $hasExtraHours) ? $incomePerExtraHour : null,
                    'extra_hours_per_week' => ($hasIncome && !$isSelfEmployed && $hasExtraHours) ? $extraHoursPerWeek : null,
                    'yearly_income' => $hasIncome ? $yearlyIncome : null
                ]);
            }
            
            // Update the policy with the calculated values
            $policy->update([
                'total_applicants_with_medicaid' => $totalMedicaidClients,
                'estimated_household_income' => $totalHouseholdIncome
            ]);
            
            // 40% chance to duplicate the policy with a different policy type
            if ($this->faker->boolean(40)) {
                // Get a different policy type
                $currentType = $policy->policy_type;
                $availableTypes = collect(PolicyType::cases())->filter(function ($type) use ($currentType) {
                    return $type !== $currentType;
                });
                
                $newPolicyType = $availableTypes->random();
                
                // Create a duplicate policy with the new type
                $duplicatePolicy = $policy->replicate(['id', 'code', 'created_at', 'updated_at']);
                $duplicatePolicy->policy_type = $newPolicyType;
                $duplicatePolicy->save();
                
                // Copy all applicants to the new policy
                foreach ($policy->applicants as $applicant) {
                    $duplicatePolicy->applicants()->attach($applicant->id, $applicant->pivot->toArray());
                }
            }
        });
    }
}
