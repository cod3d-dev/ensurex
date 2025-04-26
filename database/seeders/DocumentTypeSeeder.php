<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DocumentType;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $documentTypes = [
            [
                'name' => 'SSN',
                'description' => 'Social Security Number',
                'requires_expiration' => false,
            ],
            [
                'name' => 'Lawful',
                'description' => 'Lawful Presence Documentation',
                'requires_expiration' => true,
            ],
            [
                'name' => 'APTC',
                'description' => 'Advanced Premium Tax Credit Documentation',
                'requires_expiration' => false,
            ],
            [
                'name' => 'Income',
                'description' => 'Income Verification',
                'requires_expiration' => false,
            ],
            [
                'name' => 'Social',
                'description' => 'Social Security Documentation',
                'requires_expiration' => false,
            ],
            [
                'name' => 'Citizenship',
                'description' => 'Citizenship Documentation',
                'requires_expiration' => false,
            ],
            [
                'name' => 'EAD',
                'description' => 'Employment Authorization Document',
                'requires_expiration' => true,
            ],
            [
                'name' => 'Residency',
                'description' => 'Proof of Residency',
                'requires_expiration' => false,
            ],
            [
                'name' => 'Household',
                'description' => 'Household Composition Documentation',
                'requires_expiration' => false,
            ],
            [
                'name' => 'Status',
                'description' => 'Immigration Status Documentation',
                'requires_expiration' => true,
            ],
            [
                'name' => 'Immigration Card Expiration Date',
                'description' => 'Immigration Card with Expiration Date',
                'requires_expiration' => true,
            ],
            [
                'name' => 'Loss Employment',
                'description' => 'Loss of Employment Documentation',
                'requires_expiration' => false,
            ],
            [
                'name' => 'Driver License',
                'description' => 'Driver License',
                'requires_expiration' => true,
            ],
            [
                'name' => 'Address',
                'description' => 'Address',
                'requires_expiration' => true,
            ],
            [
                'name' => 'Passport',
                'description' => 'Passport',
                'requires_expiration' => true,
            ],
        ];

        foreach ($documentTypes as $documentType) {
            DocumentType::updateOrCreate(
                ['name' => $documentType['name']],
                $documentType
            );
        }
    }
}
