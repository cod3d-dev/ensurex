<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\ImmigrationStatus;
use App\Enums\MaritialStatus;
use App\Enums\UsState;
use App\Models\Contact;
use App\Models\User;
use App\Services\ZipCodeService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contact>
 */
class ContactFactory extends Factory
{
    // private static $lastCreatedAt = null;

    // protected ?Carbon $customCreatedAt = null;

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    //    protected $model = Contact::class;

    /**
     * The Faker instance for this factory.
     */
    protected $faker;

    protected ?ZipCodeService $zipCodeService = null;

    /**
     * Create a new factory instance.
     */

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $this->faker = \Faker\Factory::create('es_VE');

        $users = User::all();
        if ($users->isEmpty()) {
            throw new \RuntimeException('No users found in the database. Please seed users first.');
        }

        $user = $users->random();
        $isPhysicalAddressSameAsMailing = $this->faker->boolean(80);
        $locationData = $this->getRandomLocationData();
        $now = now();

        return [
            // System Fields
            'created_by' => $user->id,
            'last_contact_date' => $this->faker->boolean(70) ? now()->subDays(rand(1, 365)) : null,
            'next_follow_up_date' => $this->faker->boolean(60) ? now()->addDays(rand(1, 30)) : null,

            // Personal Information
            'preferred_language' => 'spanish', // Default to Spanish
            'full_name' => $this->faker->firstName().' '.$this->faker->lastName(),
            'email_address' => $this->faker->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'phone2' => $this->faker->optional()->phoneNumber(),
            'date_of_birth' => $this->faker->date('Y-m-d', '-80 years'),
            'gender' => $this->faker->randomElement(Gender::class)->value,
            'marital_status' => $this->faker->randomElement(MaritialStatus::class)->value,
            'country_of_birth' => $this->faker->optional()->country(),

            // Address - Using ZipCodeService to get real US location data
            'zip_code' => $locationData['zip_code'] ?? $this->faker->postcode(),

            // Expanded Address
            'address_line_1' => $this->faker->optional()->streetAddress(),
            'address_line_2' => $this->faker->optional(0.3)->secondaryAddress(),
            'city' => $locationData['city'] ?? $this->faker->city(),
            'state_province' => $locationData['state'] ?? $this->faker->randomElement(UsState::class)->value,
            'county' => $locationData['county'] ?? $this->faker->city(),

            // Billing Address
            // 'is_same_as_physical' => $isPhysicalAddressSameAsMailing,
            // 'mailing_address_line_1' => $isPhysicalAddressSameAsMailing ? null : $this->faker->streetAddress(),
            // 'mailing_address_line_2' => $isPhysicalAddressSameAsMailing ? null : $this->faker->optional(0.3)->secondaryAddress(),
            // 'mailing_city' => $isPhysicalAddressSameAsMailing ? null : $this->faker->city(),
            // 'mailing_state_province' => $isPhysicalAddressSameAsMailing ? null : $this->faker->randomElement(UsState::cases())->value,
            // 'mailing_zip_code' => $isPhysicalAddressSameAsMailing ? null : $this->faker->postcode(),

            // Life Policy Data
            'weight' => $this->faker->optional()->randomFloat(2, 50, 300),
            'height' => $this->faker->optional()->randomFloat(2, 4, 7),

            // Employment Information
            'employer_name_1' => $this->faker->optional()->company(),
            'employer_phone_1' => $this->faker->optional()->phoneNumber(),
            'position_1' => $this->faker->optional()->jobTitle(),
            'annual_income_1' => $this->faker->optional()->randomFloat(2, 20000, 150000),

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

        ];
    }

    /**
     * Set a custom creation date for the contact.
     *
     * @param  string|Carbon  $date
     * @return $this
     */
    public function createdAt(string $date): static
    {
        $date = Carbon::parse($date);

        // Use afterCreating to set the timestamps directly in the database
        return $this->state([
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }

    /**
     * Get random location data using ZipCodeService
     *
     * @return array Location data with zip_code, city, state, county
     */
    private function getRandomLocationData(): array
    {
        // Get the content of the CSV file
        $csvPath = storage_path('app/zipcodes.csv');

        if (! file_exists($csvPath)) {
            return [
                'zip_code' => $this->faker->postcode(),
                'city' => $this->faker->city(),
                'state' => $this->faker->randomElement(UsState::cases())->value,
                'county' => $this->faker->city(),
            ];
        }

        try {
            // Open the CSV file
            $file = fopen($csvPath, 'r');
            if (! $file) {
                return [
                    'zip_code' => $this->faker->postcode(),
                    'city' => $this->faker->city(),
                    'state' => $this->faker->randomElement(UsState::cases())->value,
                    'county' => $this->faker->city(),
                ];
            }

            // Skip the header row
            fgetcsv($file, 0, ';');

            // Count the number of lines
            $lineCount = 0;
            $positions = [];
            $position = ftell($file);

            while (fgetcsv($file, 0, ';') !== false) {
                $positions[] = $position;
                $position = ftell($file);
                $lineCount++;
            }

            if ($lineCount === 0) {
                fclose($file);

                return [
                    'zip_code' => $this->faker->postcode(),
                    'city' => $this->faker->city(),
                    'state' => $this->faker->randomElement(UsState::cases())->value,
                    'county' => $this->faker->city(),
                ];
            }

            // Select a random line
            $randomIndex = rand(0, $lineCount - 1);
            fseek($file, $positions[$randomIndex]);

            $data = fgetcsv($file, 0, ';');
            fclose($file);

            if ($data && count($data) >= 10) {
                return [
                    'zip_code' => str_pad($data[0], 5, '0', STR_PAD_LEFT),  // Ensure 5-digit zipcode
                    'city' => $data[1],          // City name
                    'state' => $data[2],         // State code
                    'county' => $data[9],        // County name
                ];
            }

            return [
                'zip_code' => $this->faker->postcode(),
                'city' => $this->faker->city(),
                'state' => $this->faker->randomElement(UsState::cases())->value,
                'county' => $this->faker->city(),
            ];

        } catch (\Exception $e) {
            // Fall back to faker if there's any error
            return [
                'zip_code' => $this->faker->postcode(),
                'city' => $this->faker->city(),
                'state' => $this->faker->randomElement(UsState::cases())->value,
                'county' => $this->faker->city(),
            ];
        }
    }
}
