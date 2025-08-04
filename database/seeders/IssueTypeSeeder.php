<?php

namespace Database\Seeders;

use App\Models\IssueType;
use Illuminate\Database\Seeder;

class IssueTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        // Create Cargo Adicional
        IssueType::create([
            'name' => 'Cargo Adicional',
            'description' => 'Cargo adicional en la póliza',
        ]);

        // Create Falta Cobertura
        IssueType::create([
            'name' => 'Falta Cobertura',
            'description' => 'Falta cobertura en la póliza',
        ]);
    }
}
