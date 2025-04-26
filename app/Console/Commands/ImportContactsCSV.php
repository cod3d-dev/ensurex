<?php

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Enums\UsState;

class ImportContactsCSV extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contacts:import {file : Path to the CSV file}';
    

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import contacts from a CSV file';

    /**
     * Execute the console command.
     */

     protected $columnMap = [
        // Basic identification
        'code' => 'code',
        'fullname' => 'full_name',
        // Contact information
        'correo' => 'email_address',
        'phone1' => 'phone',
        'phone2' => 'phone2', 
        // Personal information
        'DOB' => 'date_of_birth',
        'Genero' => 'gender',
        ' Genero' => 'gender',
        'status_migratorio' => 'immigration_status',
        ' status_migratorio' => 'immigration_status',
        
        // Address information
        'direccion1' => 'address_line_1',
        'direccion2' => 'address_line_2',
        'Ciudad' => 'city',
        'State' => 'state_province',
        'zip' => 'zip_code',
        'CONDADO' => 'county',
        // Identification documents
        'SSN' => 'ssn',
        'APTC' => 'aptc_number',
        'pasaporte' => 'passport_number',
        // System fields
        'Asistente' => 'created_by', // Will be processed specially
        'Asistente' => 'assigned_to', // Will be processed specially
        'Kynect' => 'kynect_case_number',
        // 'CASE KYNECT' => 'kynect_case_number',
        'pais_origen' => 'country_of_birth',
        'creado' => 'created_at',
        'suspended_license' => 'license_has_been_revoked',
        'felonia' => 'has_made_felony',
        'bancarrota' => 'has_declared_bankruptcy',
    ];

    // User initials to user ID mapping
    protected $userMap = [
        'GS' => 'Ghercy Segovia',
        'AG' => 'AG',
        'CH' => 'Christell',
        'CR' => 'Carlos Rojas',
        'FS' => 'Fhiona Segovia',
        'MC' => 'Maly Carvajal',
        'OO' => 'Omar Ostos',
        'RM' => 'Raul Medrano',
        'RS' => 'Ricardo Segovia',
    ];

    // Cache for user IDs
    protected $userIdCache = [];

    protected $notesColumns = [
        'notas', 
        'whastapp_interaccion', 
        'observaciones', 
        'correo_creado_para_cliente',
    ];


    public function handle()
    {
        // Pre-load user IDs to avoid multiple database queries
        $this->loadUserIds();
        
        $filePath = $this->argument('file');
        
        if (!file_exists($filePath)) {
            $this->error('File does not exist: ' . $filePath);
            return 1;
        }

        $file = fopen($filePath, 'r');
        if (!$file) {
            $this->error('Failed to open file: ' . $filePath);
            return 1;
        }

        $headers = fgetcsv($file);
        if (!$headers) {
            $this->error('Failed to read headers from file: ' . $filePath);
            fclose($file);
            return 1;
        }

        DB::beginTransaction();

        try {
            $rowCount = 0;
            $successCount = 0;
            $errorCount = 0;

            $this->info('Starting to import contacts...');
            $this->output->progressStart(count(file($filePath)) - 1); // Exclude header row

            while (($row = fgetcsv($file)) !== false) {
                $rowCount++;
                
                try {
                    // Convert row to associative array
                    $data = array_combine($headers, $row);
                    if (!$data) {
                        continue;
                    }

                    // Skip empty rows (where customer name is empty)
                    if (empty(trim($data['fullname'] ?? ''))) {
                        continue;
                    }

                    $contact = new Contact();
                    
                    // Special handling for created_by (user mapping)
                    if (isset($data['asistente']) && !empty(trim($data['asistente']))) {
                        $userInitials = trim($data['asistente']);
                        $userId = $this->getUserIdFromInitials($userInitials);
                        if ($userId) {
                            $contact->created_by = $userId;
                            $contact->assigned_to = $userId;
                        }
                    }
                    
                    // Map standard fields
                    foreach ($this->columnMap as $csvColumn => $dbField) {
                        // Skip created_by as we handle it specially
                        if ($dbField === 'created_by') {
                            continue;
                        }
                        
                        // Check if the CSV column exists in the data array
                        if (isset($data[$csvColumn]) && !empty(trim($data[$csvColumn]))) {
                            try {
                                $value = trim($data[$csvColumn]);
                                // Special handling for dates
                                if ($dbField === 'date_of_birth') {
                                    $contact->$dbField = $this->parseDate($value);
                                }
                                // Special handling for gender field
                                else if ($dbField === 'gender') {
                                    // Only set gender if it's not empty
                                    if (!empty($value)) {
                                        // Map gender values to valid enum values
                                        $genderValue = strtolower(trim($value));
                                        $mappedGender = '';
                                        
                                        if (strpos($genderValue, 'f') === 0 || strpos($genderValue, 'm') === 0) {
                                            // If it starts with 'f', it's female, otherwise if it starts with 'm', it's male
                                            $mappedGender = strpos($genderValue, 'f') === 0 ? 'female' : 'male';
                                        } else {
                                            // Default to male if we can't determine the gender
                                            $mappedGender = 'male';
                                        }
                                        
                                        $contact->$dbField = $mappedGender;
                                    } else {
                                        // Skipping empty gender value
                                    }
                                }
                                // Special handling for created_at (ensure it has time component)
                                else if ($dbField === 'created_at') {
                                    $parsedDate = $this->parseDate($value);
                                    if ($parsedDate) {
                                        $contact->$dbField = $parsedDate . ' 12:00:00';
                                    } else {
                                        $contact->$dbField = now();
                                    }
                                }
                                // Special handling for immigration status
                                else if ($dbField === 'immigration_status') {
                                    $normalizedValue = $this->normalizeImmigrationStatus($value, $contact);
                                    $contact->$dbField = $normalizedValue;
                                }
                                // Special handling for boolean fields
                                else if (in_array($dbField, ['license_has_been_revoked', 'has_made_felony', 'has_declared_bankruptcy'])) {
                                    $contact->$dbField = strtolower($value) === 'yes' || strtolower($value) === 'si';
                                }
                                // Special handling for state code (convert to uppercase)
                                else if ($dbField === 'state_province') {
                                    $contact->$dbField = $this->handleStateProvince($value);
                                }
                                // Special handling for code (pad with zeros)
                                else if ($dbField === 'code') {
                                    $contact->$dbField = $this->formatCode($value);
                                }
                                // Special handling for email validation
                                else if ($dbField === 'email_address') {
                                    $contact->$dbField = $this->validateEmail($value);
                                }
                                // Normal field mapping
                                else {
                                    $contact->$dbField = $value;
                                }
                            } catch (\Exception $e) {
                                $errorCount++;
                            }
                        }
                    }
                    
                    // Set default values for required fields
                    if (empty($contact->is_lead)) {
                        $contact->is_lead = true;
                    }

                    $notesContent = [];
                    foreach ($this->notesColumns as $notesColumn) {
                        if (isset($data[$notesColumn]) && !empty(trim($data[$notesColumn]))) {
                            $notesContent[] = "{$notesColumn}: " . trim($data[$notesColumn]);
                        }
                    }

                    if (!empty($notesContent)) {
                        $contact->notes = implode("\n", $notesContent);
                    }
                    
                    // Save the contact
                    try {
                        $contact->save();
                        $successCount++;
                    } catch (\Exception $e) {
                        $errorCount++;
                    }
                    
                } catch (\Exception $e) {
                    $errorCount++;
                }
                
                $this->output->progressAdvance();
            }
            
            $this->output->progressFinish();
            
            // Commit the transaction
            DB::commit();
            
            $this->info("Import completed:");
            $this->info("  Total rows processed: {$rowCount}");
            $this->info("  Successfully imported: {$successCount}");
            $this->info("  Errors: {$errorCount}");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Fatal error during import: " . $e->getMessage());
            fclose($file);
            return 1;
        }
        
        fclose($file);
        return 0;
    }
    
    protected function handleStateProvince($value)
    {
        $stateCode = strtoupper(trim($value));
        
        try {
            return UsState::from($stateCode)->value;
        } catch (\ValueError $e) {
            return null;
        }
    }

    /**
     * Load all user IDs into cache
     */
    private function loadUserIds()
    {
        $users = User::all(['id', 'name']);
        foreach ($users as $user) {
            $this->userIdCache[$user->name] = $user->id;
        }
    }
    
    /**
     * Get user ID from initials
     */
    private function getUserIdFromInitials($initials)
    {
        // If initials don't exist in our mapping, return null
        if (!isset($this->userMap[$initials])) {
            return null;
        }
        
        // Get the full name from the mapping
        $fullName = $this->userMap[$initials];
        
        // Check if we have this user's ID in our cache
        if (isset($this->userIdCache[$fullName])) {
            return $this->userIdCache[$fullName];
        }
        
        // If not in cache, try to find in database
        $user = User::where('name', $fullName)->first();
        if ($user) {
            // Cache the result for future use
            $this->userIdCache[$fullName] = $user->id;
            return $user->id;
        }
        
        return null;
    }
    
    /**
     * Parse a date from various formats
     */
    private function parseDate($dateString)
    {
        if (empty($dateString)) {
            return null;
        }
        
        // Special handling for Month/Year format (e.g., Jan/2021)
        if (preg_match('/^([A-Za-z]{3})\/(\d{4})$/', $dateString, $matches)) {
            $month = date_parse($matches[1])['month'];
            $year = $matches[2];
            if ($month !== false) {
                return sprintf('%s-%02d-01', $year, $month);
            }
        }
        
        // Try different date formats (based on sample data)
        $formats = [
            'm/d/Y', 'd/m/Y', // 9/24/1974, 24/9/1974
            'n/j/Y', 'j/n/Y', // 9/24/1974, 24/9/1974 (no leading zeros)
            'm-d-Y', 'd-m-Y', // 9-24-1974, 24-9-1974
            'Y-m-d', // 1974-09-24
            'M/j/Y', 'j/M/Y', // Sep/24/1974, 24/Sep/1974
            'M-j-Y', 'j-M-Y', // Sep-24-1974, 24-Sep-1974
        ];
        
        // Special handling for MM/DD/YYYY format
        if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})/', $dateString, $matches)) {
            return sprintf('%s-%02d-%02d', $matches[3], $matches[1], $matches[2]);
        }
        
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $dateString);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }
        
        // If all formats fail, return null
        return null;
    }
    
    /**
     * Normalize immigration status to match valid values and set immigration_status_category
     */
    private function normalizeImmigrationStatus($status, &$contact)
    {
        if (empty($status)) {
            return null;
        }
        $status = trim(strtolower($status));
        
        if (strpos($status, 'ciudad') !== false) {
            return 'citizen';
        } else if (strpos($status, 'residen') !== false) {
            return 'resident';
        } else {
            // Store the original value in immigration_status_category with first letter uppercase
            $contact->immigration_status_category = ucfirst($status);
            return 'other';
        }
    }
    
    /**
     * Parse a full name into its components
     */
    private function parseName($fullName)
    {
        $result = [
            'first_name' => null,
            'middle_name' => null,
            'last_name' => null,
            'second_last_name' => null,
        ];
        
        $parts = explode(' ', trim($fullName));
        $count = count($parts);
        
        if ($count == 1) {
            $result['first_name'] = $parts[0];
        } else if ($count == 2) {
            $result['first_name'] = $parts[0];
            $result['last_name'] = $parts[1];
        } else if ($count == 3) {
            // For Spanish names, assuming First Last1 Last2 pattern
            $result['first_name'] = $parts[0];
            $result['last_name'] = $parts[1];
            $result['second_last_name'] = $parts[2];
        } else if ($count >= 4) {
            // For longer names with multiple parts
            // Assuming pattern: First [Middle] Last1 Last2
            // Take first part as first name
            $result['first_name'] = $parts[0];
            
            if ($count == 4) {
                // Could be First Middle Last1 Last2
                $result['middle_name'] = $parts[1];
                $result['last_name'] = $parts[2];
                $result['second_last_name'] = $parts[3];
            } else {
                // For names with more parts, combine middle parts
                $result['middle_name'] = $parts[1];
                
                // Last two parts as last names
                $lastIndex = $count - 1;
                $secondLastIndex = $count - 2;
                
                $result['last_name'] = $parts[$secondLastIndex];
                $result['second_last_name'] = $parts[$lastIndex];
            }
        }
        
        return $result;
    }
    
    /**
     * Format code with leading zeros
     * Converts C1 to C00001, N10 to N00010, etc.
     */
    private function formatCode($code)
    {
        if (empty($code)) {
            return null;
        }
        
        // Extract the letter prefix and number
        if (preg_match('/^([A-Za-z]+)(\d+)$/', $code, $matches)) {
            $prefix = $matches[1];
            $number = $matches[2];
            
            // Pad the number with leading zeros to 5 digits
            $paddedNumber = str_pad($number, 5, '0', STR_PAD_LEFT);
            
            // Return the formatted code
            return $prefix . $paddedNumber;
        }
        
        // If the code doesn't match the expected format, return it unchanged
        return $code;
    }
    
    /**
     * Validate email address
     */
    private function validateEmail($value)
    {
        $email = trim($value);
        if (empty($email)) {
            return null;
        }
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }
        
        return null;
    }
}