<?php

namespace Database\Seeders;

use App\Models\InsuranceCompany;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InsuranceCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            [
                'name' => 'No Asignada',
                'code' => 'NA',
                'is_active' => true,
            ],
            [
                'name' => 'Aetna',
                'code' => 'AET',
                'is_active' => true,
            ],
            [
                'name' => 'Ambetter Health',
                'code' => 'AMB',
                'is_active' => true,
            ],
            [
                'name' => 'Anthem',
                'code' => 'ANT',
                'is_active' => true,
            ],
            [
                'name' => 'BCBS-Texas',
                'code' => 'BCT',
                'is_active' => true,
            ],
            [
                'name' => 'Blue Cross Blue Shield',
                'code' => 'BSC',
                'is_active' => true,
            ],
            [
                'name' => 'CareSource',
                'code' => 'CAR',
                'is_active' => true,
            ],
            [
                'name' => 'Constant Care',
                'code' => 'CCA',
                'is_active' => true,
            ],
            [
                'name' => 'Cigna Healthcare',
                'code' => 'CIG',
                'is_active' => true,
            ],
            [
                'name' => 'Florida Blue',
                'code' => 'FLO',
                'is_active' => true,
            ],
            [
                'name' => 'Kaiser Permanente',
                'code' => 'KAI',
                'is_active' => true,
            ],
            [
                'name' => 'Molina',
                'code' => 'MOL',
                'is_active' => true,
            ],
            [
                'name' => 'Oscar',
                'code' => 'OSC',
                'is_active' => true,
            ],
            [
                'name' => 'Regence Blue Cross',
                'code' => 'RBC',
                'is_active' => true,
            ],
            [
                'name' => 'Sentara',
                'code' => 'SEN',
                'is_active' => true,
            ],
            [
                'name' => 'SHP',
                'code' => 'SHP',
                'is_active' => true,
            ],
            [
                'name' => 'SSI',
                'code' => 'Standard Silver',
                'is_active' => true,
            ],            
            [
                'name' => 'UnitedHealthcare',
                'code' => 'UHC',
                'is_active' => true,
            ],
            [
                'name' => 'Delta Dental',
                'code' => 'DLD',
                'is_active' => true,
            ],
            [
                'name' => 'Delta Vision',
                'code' => 'DLV',
                'is_active' => true,
            ],
            [
                'name' => 'Essential Choice',
                'code' => 'ECH',
                'is_active' => true,
            ],
            [
                'name' => 'Blue View',
                'code' => 'BLV',
                'is_active' => true,
            ],
            
            
            
            
        ];

        foreach ($companies as $company) {
            InsuranceCompany::create($company);
        }
    }
}
