<?php

namespace Database\Seeders;

use App\Models\PolicyType;
use Illuminate\Database\Seeder;

class PolicyTypeSeeder extends Seeder
{
    public function run(): void
    {
        PolicyType::create([
            'name' => 'Salud',
            'description' => 'Póliza de seguro de salud',
            'is_active' => true,
        ]);
    }
}
