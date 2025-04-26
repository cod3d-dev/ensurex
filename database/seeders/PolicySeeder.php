<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Policy;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PolicySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        for ($i = 0; $i < 200; $i++) {
            Policy::factory(1)->create();
        }
    }
}
