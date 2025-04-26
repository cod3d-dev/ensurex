<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Seeder;
use App\Enums\UsState;

class ContactSeeder extends Seeder
{
    public function run(): void
    {
        // $admin = User::first();

        // $ricardo_id = User::where('name', 'Ricardo Segovia')->first();
        // $fhiona_id = User::where('name', 'Fhiona Segovia')->first();
        // $carlos_id = User::where('name', 'Carlos Rojas')->first();
        // $raul_id = User::where('name', 'Raul Medrano')->first();

        // $contacts = [
        //     [
        //         'first_name' => 'Glicel',
        //         'last_name' => 'Ferrer',
        //         'created_by' => $raul_id->id,
        //         'email_address' => 'glicel0310@gmail.com',
        //         'phone' => '+15025996379',
        //         'date_of_birth' => '1965-08-06',
        //         'state_province' => UsState::KENTUCKY->value,
        //         'ssn' => '040-87-8784',
        //         'zip_code' => '40218',
        //         'city' => 'Louisville',
        //         'county' => 'Jefferson',
        //         'address_line_1' => '123 Main St',
        //         'created_by' => $admin->id,
        //         'status' => 'active',
        //         'preferred_language' => 'spanish',
        //         'kommo_id' => '9231448',
        //     ],
        //     [
        //         'first_name' => 'Silva',
        //         'last_name' => 'Arce',
        //         'second_last_name' => 'Escovar',
        //         'email_address' => 'escobarcv@icloud.com',
        //         'phone' => '+8593822736',
        //         'date_of_birth' => '1985-08-29',
        //         'state_province' => UsState::KENTUCKY->value,
        //         'ssn' => '771-15-2553',
        //         'zip_code' => '40517',
        //         'city' => 'Lexington',
        //         'county' => 'Fayette',
        //         'address_line_1' => '3552 Olympia Rd',
        //         'created_by' => $admin->id,
        //         'status' => 'active',
        //         'preferred_language' => 'spanish',
        //         'kommo_id' => '2334602',
        //     ],
        // ];

        // foreach ($contacts as $contact) {
        //     Contact::create($contact);
        //     // dd($contact);
        // }

        for ($i = 0; $i < 1300; $i++) {
            Contact::factory(1)->create();
        }
    }
}
