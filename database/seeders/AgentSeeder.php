<?php

namespace Database\Seeders;

use App\Models\Agent;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AgentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $table->string('name');

        Agent::create([
            'name' => 'Ghercy Segovia',
        ]);

        Agent::create([
            'name' => 'Maly Carvajal',
        ]);
    }
}
