<?php

namespace Database\Factories;

use App\Enums\UsState;
use App\Enums\ImmigrationStatus;
use App\Models\Contact;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\MaritialStatus;
use App\Enums\Gender;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contact>
 */
class ContactFactory extends Factory
{
    private static $lastCreatedAt = null;

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
//    protected $model = Contact::class;

    /**
     * The Faker instance for this factory.
     *
     * @var \Faker\Generator
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
            $latestContact = Contact::orderBy('created_at', 'desc')->first();
            self::$lastCreatedAt = $latestContact ? $latestContact->created_at : now()->subYears(4);
        }
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $users = User::all();
        if ($users->isEmpty()) {
            throw new \RuntimeException('No users found in the database. Please seed users first.');
        }

        $user = $users->random();
        $isPhysicalAddressSameAsMailing = $this->faker->boolean(80);

        return [
            // System Fields
            'created_at' => function() {
                $minDate = self::$lastCreatedAt;
                $maxDate = Carbon::instance(clone $minDate)->addHours(48);
                $newDate = $this->faker->dateTimeBetween($minDate, $maxDate);
                self::$lastCreatedAt = $newDate;
                return $newDate;
            },
            'is_lead' => $this->faker->boolean,
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'priority' => $this->faker->randomElement(['high', 'medium', 'low']),
            'created_by' => $user->id,
            'last_contact_date' => $this->faker->boolean(70) ? now()->subDays(rand(1, 365)) : null,
            'next_follow_up_date' => $this->faker->boolean(60) ? now()->addDays(rand(1, 30)) : null,
            'preferred_contact_method' => $this->faker->optional()->randomElement(['email', 'phone', 'sms']),
            'preferred_contact_time' => $this->faker->boolean(70) ? $this->faker->time() : null,
            'referral_source' => $this->faker->optional()->randomElement(['website', 'referral', 'social_media']),
            'is_eligible_for_coverage' => $this->faker->boolean,

            // Personal Information
            'preferred_language' => 'spanish', // Default to Spanish
            'first_name' => $this->faker->firstName(),
            'middle_name' => $this->faker->optional()->firstName(),
            'last_name' => $this->faker->lastName(),
            'second_last_name' => $this->faker->optional()->lastName(),
            'email_address' => $this->faker->optional()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'phone2' => $this->faker->optional()->phoneNumber(),
            'whatsapp' => $this->faker->optional()->phoneNumber(),
            // date of birth is required all the time and it should be a valid data (máx 120 years)
            'date_of_birth' => $this->faker->date('Y-m-d', '-120 years'),
            'gender' => $this->faker->randomElement(Gender::class)->value,
            'marital_status' => $this->faker->randomElement(MaritialStatus::class)->value,
            'country_of_birth' => $this->faker->optional()->country(),
            'weight' => $this->faker->optional()->randomFloat(2, 50, 300),
            'height' => $this->faker->optional()->randomFloat(2, 4, 7),
            'is_tobacco_user' => $this->faker->boolean(20),
            'is_pregnant' => $this->faker->boolean(10),

            // Employment Information - Source 1
            'employer_name_1' => $this->faker->optional()->company(),
            'employer_phone_1' => $this->faker->optional()->phoneNumber(),
            'position_1' => $this->faker->optional()->jobTitle(),
            'annual_income_1' => $this->faker->optional()->randomFloat(2, 20000, 150000),

            // Employment Information - Source 2
            'employer_name_2' => $this->faker->optional(0.3)->company(),
            'employer_phone_2' => $this->faker->optional(0.3)->phoneNumber(),
            'position_2' => $this->faker->optional(0.3)->jobTitle(),
            'annual_income_2' => $this->faker->optional(0.3)->randomFloat(2, 20000, 150000),

            // Employment Information - Source 3
            'employer_name_3' => $this->faker->optional(0.1)->company(),
            'employer_phone_3' => $this->faker->optional(0.1)->phoneNumber(),
            'position_3' => $this->faker->optional(0.1)->jobTitle(),
            'annual_income_3' => $this->faker->optional(0.1)->randomFloat(2, 20000, 150000),

            // Immigration/Legal Documents
            'immigration_status' => $this->faker->randomElement(ImmigrationStatus::class)->value,
            // 'immigration_status_category' => $this->faker->optional()->randomElement(ImmigrationStatusCategory::cases())->value,
            'passport_number' => $this->faker->optional()->regexify('[A-Z][0-9]{8}'),
            'uscis_number' => $this->faker->optional()->regexify('[0-9]{9}'),
            'ssn' => $this->faker->optional()->regexify('[0-9]{3}-[0-9]{2}-[0-9]{4}'),
            'ssn_issue_date' => $this->faker->boolean(50) ? $this->faker->date() : null,
            'green_card_number' => $this->faker->optional()->regexify('[A-Z][0-9]{8}'),
            'green_card_expiration_date' => $this->faker->boolean(50) ? $this->faker->date() : null,
            'work_permit_number' => $this->faker->optional()->regexify('[A-Z][0-9]{8}'),
            'work_permit_expiration_date' => $this->faker->boolean(50) ? $this->faker->date() : null,
            'driver_license_number' => $this->faker->optional()->regexify('[A-Z][0-9]{7}'),
            'driver_license_expiration_date' => $this->faker->boolean(50) ? $this->faker->date() : null,

            // Physical Address
            'address_line_1' => $this->faker->optional()->streetAddress(),
            'address_line_2' => $this->faker->optional(0.3)->secondaryAddress(),
            'city' => $this->faker->city(),
            'state_province' => $this->faker->randomElement(UsState::class)->value,
            'zip_code' => $this->faker->postcode(),
            'county' => $this->faker->city(),

            // Mailing Address
            'is_same_as_physical' => $isPhysicalAddressSameAsMailing,
            'mailing_address_line_1' => $isPhysicalAddressSameAsMailing ? null : $this->faker->streetAddress(),
            'mailing_address_line_2' => $isPhysicalAddressSameAsMailing ? null : $this->faker->optional(0.3)->secondaryAddress(),
            'mailing_city' => $isPhysicalAddressSameAsMailing ? null : $this->faker->city(),
            'mailing_state_province' => $isPhysicalAddressSameAsMailing ? null : $this->faker->randomElement(UsState::cases())->value,
            'mailing_zip_code' => $isPhysicalAddressSameAsMailing ? null : $this->faker->postcode(),
        ];
    }
}
