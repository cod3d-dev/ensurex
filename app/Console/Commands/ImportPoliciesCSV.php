<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Policy;
use App\Models\Contact;
use App\Models\User;
use App\Models\InsuranceCompany;
use App\Models\PolicyApplicant;
use App\Models\PolicyDocument;
use App\Enums\FamilyRelationship;
use App\Enums\DocumentStatus;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ImportPoliciesCSV extends Command
{
    protected $signature = 'policies:import {file} 
                            {--debug : Enable debug output}
                            {--rows= : Comma-separated list of row numbers to debug (e.g., 1,2,3 or 1-5,7-10)}
                            {--contact= : Contact code to debug}
                            {--policy= : Policy ID to debug}
                            {--csv-rows= : Import only specific CSV file rows (e.g., 1-10,20-30)}';
    protected $description = 'Import policies from a CSV file';

    // Column mapping for case-insensitive and flexible matching
    protected $columnMap = [
        'row' => ['row_number'],
        'policy' => ['policy_number'],
        'contact_code' => ['contact_code'],
        'applicant_type' => ['applicant_type'],
        'current_user' => ['current_user'],
        'user_2024' => ['user_2024'],
        'client_informed' => ['client_informed'],
        'initial_paid' => ['initial_paid'],
        'autopay' => ['autopay_activated'],
        'in_aca' => ['in_aca'],
        'life_offered' => ['life_offered'],
        'docs_status' => ['docs_status'],
        'doc_exp_date' => ['doc_exp_date'],
        'premium' => ['premium_amount'],
        'insurance_company' => ['insurance_company_code'],
        'policy_type' => ['policy_type'],
        'plan_de_salud_2025' => ['health_plan_name'],
        'plan_dental' => ['dental_plan_name'],
        'dental_premium' => ['dental_premium', 'Dental_Premium', 'dental premium'],
        'dental_insurance_company_code' => ['dental_insurance_company_code'],
        'plan_de_vision' => ['vision_plan_name'],
        'vision_insurance_company_code' => ['vision_insurance_company_code'],
        'ingresos_2024' => ['total_estimated_income'],
        'empleador' => ['employeer_name'],
        'work_role' => ['work_role'],
        'telefono_empleador' => ['employer_phone'],
        'card_number' => ['card_number'],
        'card_type' => ['card_type'],
        'card_expiration' => ['card_expiration'],
        'card_csc' => ['card_csv'],
        'bank' => ['bank_name'],
        'account_number' => ['account_number'],
        'route_number' => ['route_number'],
        'cuenta_nombre' => ['account_holder'],
        'emergency_contact' => ['emergency_contact'],
        'emergency_phone' => ['emergency_phone'],
        'emergency_relationship' => ['emergency_relationship'],
        // Document requirement columns
        'ssn_required' => ['ssn_required'],
        'lawful_required' => ['lawful_required'],
        'aptc_required' => ['aptc_required'],
        'income_required' => ['income_required'],
        'social_required' => ['social_required'],
        'citizenship_required' => ['citizenship_required'],
        'ead_required' => ['ead_required'],
        'residency_required' => ['residency_required'],
        'household_required' => ['household_required'],
        'status_required' => ['status_required'],
        'im_exp_date_required' => ['im_exp_date_required'],
        'loss_employement_required' => ['loss_employement_required'],
        'driver_license_required' => ['driver_license_required'],
        'address_required' => ['address_required'],
        'passport_required' => ['passport_required'],
        'state_code' => ['state_code'],
        'zip_code' => ['zip_code'],
        'us_county' => ['us_county'],
        'city_name' => ['city_name'],
        // Enrollment date
        'enroll_date' => ['enroll_date']
    ];

    // Required document columns that should trigger "Pending" status
    protected $docColumns = [
        'ssn_required', 'lawful_required', 'aptc_required', 'income_required', 'social_required',
        'citizenship_required', 'ead_required', 'residency_required', 'household_required', 'status_required',
        'im_exp_date_required', 'loss_employement_required', 'driver_license_required', 'address_required',
        'passport_required'
    ];

    // Map CSV column names to document type names
    protected $docTypeMap = [
        'ssn_required' => 'SSN',
        'lawful_required' => 'Lawful',
        'aptc_required' => 'APTC',
        'income_required' => 'Income',
        'social_required' => 'Social',
        'citizenship_required' => 'Citizenship',
        'ead_required' => 'EAD',
        'residency_required' => 'Residency',
        'household_required' => 'Household',
        'status_required' => 'Status',
        'im_exp_date_required' => 'Immigration Card Expiration Date',
        'loss_employement_required' => 'Loss Employment',
        'driver_license_required' => 'Driver License',
        'address_required' => 'Address',
        'passport_required' => 'Passport'
    ];

    // Document status columns to exclude from document creation
    protected $docStatusColumns = [
        'docs_status', 'doc_exp_date'
    ];

    // Store all rows for reference in other methods
    protected $rows = [];

    public function handle()
    {
        $file = $this->argument('file');
        $debug = $this->option('debug');
        $rowsToDebug = $this->parseRowsToDebug($this->option('rows'));
        $contactToDebug = $this->option('contact');
        $policyToDebug = $this->option('policy');
        $csvRowsToImport = $this->parseRowsToDebug($this->option('csv-rows'));
        
        if (!file_exists($file)) {
            $this->error("File not found: $file");
            return 1;
        }

        $handle = fopen($file, 'r');
        if (!$handle) {
            $this->error("Could not open file: $file");
            return 1;
        }

        $header = fgetcsv($handle);
        $this->info('Processing CSV with ' . count($header) . ' columns');
        
        // Normalize header names (lowercase, trim)
        $normalizedHeader = array_map(function($item) {
            return trim($item);
        }, $header);
        
        // Create a mapping from actual CSV headers to our expected keys
        $headerMap = $this->createHeaderMap($normalizedHeader);
        
        // Parse all rows into normalized data
        $this->rows = $this->parseAllRows($handle, $normalizedHeader, $headerMap);
        
        // Set agent_id to 1 (Ghercy Segovia)
        $agent = 1;
        
        $imported = 0;
        $skipped = 0;
        $errors = [];
        
        // FIRST PASS: Process policy owners (applicant_type 1, 5, or 4)
        $this->info("FIRST PASS: Processing policy owners...");
        $policyMap = []; // Map policy codes to policy IDs
        $policyDocStatus = []; // Track document status for each policy
        $policyExpDates = []; // Track expiration dates for each policy
        
        foreach ($this->rows as $rowNum => $data) {
            // Add detailed row info when debugging specific rows
            
            // // Special debugging for row 10 to see all data
            // if ($data['_rowNum'] == 10) {
            //     $this->info("SPECIAL DEBUG - Row 10 Complete Data:");
            //     foreach ($data as $key => $value) {
            //         $this->info("  $key: " . ($value ?? 'null'));
            //     }
                
            //     // Check specific insurance company fields
            //     $this->info("Insurance company fields:");
            //     $this->info("  insurance_company: " . ($data['insurance_company'] ?? 'null'));
            //     $this->info("  dental_insurance_co: " . ($data['dental_insurance_co'] ?? 'null'));
            //     $this->info("  dentalvision_insurance_co: " . ($data['dentalvision_insurance_co'] ?? 'null'));
            //     $this->info("  vision_insurance_co: " . ($data['vision_insurance_co'] ?? 'null'));
            // }

            $isDebugRow = in_array($data['row'], $rowsToDebug) || ($contactToDebug && $data['contact_code'] === $contactToDebug);
            
            if ($isDebugRow) {
                $this->info("--- Processing Row {$data['row']} (CSV row: {$data['_rowNum']}) ---");
                $this->info("Policy: " . ($data['policy'] ?? 'N/A'));
                $this->info("Contact Code: " . ($data['contact_code'] ?? 'N/A'));
                $this->info("Applicant Type: " . ($data['applicant_type'] ?? 'N/A'));
                $this->info("Policy Type: " . ($data['policy_type'] ?? 'N/A'));
                
                // Dump all data for this row to help debug
                $this->info("All data for this row:");
                foreach ($data as $key => $value) {
                    $this->info("  $key: " . ($value ?? 'null'));
                }
            }
            
            if (empty($data['policy'])) {
                if ($debug || $isDebugRow) {
                    $this->warn("Row {$data['row']}: No policy code found. Skipping.");
                }
                continue; // Skip rows without policy code
            }
            
            $applicantType = $data['applicant_type'] ?? null;
            if (!in_array($applicantType, ['1', '5', '4'])) {
                if ($debug || $isDebugRow) {
                    $this->warn("Row {$data['row']}: Skipping non-owner row.");
                }
                continue; // Skip non-owner rows in first pass
            }
            
            // Find contact by code
            $contact = Contact::where('code', $data['contact_code'])->first();
            if (!$contact) {
                if ($debug || $isDebugRow) {
                    $this->warn("Row {$data['row']}: Contact not found for code: {$data['contact_code']}. Skipping.");
                }
                $skipped++;
                continue;
            }
            
            // Process each policy type
            $policyTypes = $this->decodePolicyTypes($data['policy_type'] ?? null);
            if (empty($policyTypes)) {
                $policyTypes = ['health']; // Default to health if no type specified
            }
            
            foreach ($policyTypes as $type) {
                DB::beginTransaction();
                try {
                    // Create unique policy code with type prefix and 5-digit number
                    $typePrefix = match($type) {
                        'health' => 'H',
                        'vision' => 'V',
                        'dental' => 'D',
                        'life' => 'L',
                        default => 'H'
                    };

                    $insuranceCompanyCode = null;
                    // If health policy, get the insurance company code from the column insurance_company
                    if ($type === 'health') {
                        $rawCode = $data['insurance_company'] ?? '';
                        $insuranceCompanyCode = trim($rawCode);
                        if ($this->option('debug') || $isDebugRow) {
                            $this->info("Health policy raw insurance company code: '{$rawCode}'");
                            $this->info("Health policy trimmed insurance company code: '{$insuranceCompanyCode}'");
                        }
                    } elseif ($type === 'vision') {
                        $rawCode = $data['vision_insurance_co'] ?? $data['dentalvision_insurance_co'] ?? $data['insurance_company'] ?? '';
                        $insuranceCompanyCode = trim($rawCode);
                        if ($this->option('debug') || $isDebugRow) {
                            $this->info("Vision policy raw insurance company code: '{$rawCode}'");
                            $this->info("Vision policy trimmed insurance company code: '{$insuranceCompanyCode}'");
                        }
                    } elseif ($type === 'dental') {
                        $rawCode = $data['dental_insurance_co'] ?? $data['dentalvision_insurance_co'] ?? $data['insurance_company'] ?? '';
                        $insuranceCompanyCode = trim($rawCode);
                        if ($this->option('debug') || $isDebugRow) {
                            $this->info("Dental policy raw insurance company code: '{$rawCode}'");
                            $this->info("Dental policy trimmed insurance company code: '{$insuranceCompanyCode}'");
                        }
                    } elseif ($type === 'life') {
                        $rawCode = $data['insurance_company'] ?? '';
                        $insuranceCompanyCode = trim($rawCode);
                        if ($this->option('debug') || $isDebugRow) {
                            $this->info("Life policy raw insurance company code: '{$rawCode}'");
                            $this->info("Life policy trimmed insurance company code: '{$insuranceCompanyCode}'");
                        }
                    }
                    
                    $policyNumber = str_pad($data['policy'], 5, '0', STR_PAD_LEFT);
                    $policyCode = $typePrefix . $policyNumber;
                    
                    // Check if policy already exists
                    $policy = Policy::where('code', $policyCode)->first();
                    
                    if ($policy) {
                        if ($debug || $isDebugRow) {
                            $this->info("Policy with code '$policyCode' already exists. Using existing policy.");
                        }
                    } else {
                        // Create new policy
                        $policy = new Policy();
                        $policy->code = $policyCode;
                        $policy->contact_id = $contact->id; // Set policy owner
                        $policy->user_id = $this->findUserId($data['current_user']) ?? 1;
                        $policy->policy_year = 2025;
                        $policy->agent_id = $agent;
                        $policy->policy_zipcode = $data['zip_code'] ?? null;
                        $policy->policy_us_county = $data['us_county'] ?? null;
                        $policy->policy_city = $data['city_name'] ?? null;
                        $policy->policy_us_state = $data['state_code'] ?? null;
                        $policy->previous_year_policy_user_id = $this->findUserId($data['user_2024']);
                        $policy->client_notified = $this->parseBool($data['client_informed'] ?? null);
                        $policy->initial_paid = $this->parseBool($data['initial_paid'] ?? null);
                        $policy->autopay = $this->parseBool($data['autopay'] ?? null);
                        $policy->aca = $this->parseBool($data['in_aca'] ?? null, ['Si', 'Sí']);
                        $policy->life_offered = $this->parseBool($data['life_offered'] ?? null);
                        
                        // Process document status
                        $docStatus = $this->determineDocStatus($data);
                        
                        $policyDocumentStatus = DocumentStatus::Approved;
                        // If it is a health policy, check if the column docs_status has a value different than "Bien" and set the policy document status to pending
                        if ($type === 'health' && $data['docs_status'] !== 'Bien') {
                            $policyDocumentStatus = DocumentStatus::Pending;
                        }
                        
                        $policy->document_status = $policyDocumentStatus;
                        
                        // Store document status for this policy code
                        if (!isset($policyDocStatus[$policyCode]) || $policyDocumentStatus === DocumentStatus::Pending->value) {
                            $policyDocStatus[$policyCode] = $docStatus;
                        }
                        
                        // Process document expiration date
                        $expDate = $this->parseDate($data['doc_exp_date'] ?? null);
                        $policy->next_document_expiration_date = $expDate;
                        
                        // Store expiration date for this policy code
                        if ($expDate) {
                            if (!isset($policyExpDates[$policyCode]) || 
                                (isset($policyExpDates[$policyCode]) && strtotime($expDate) < strtotime($policyExpDates[$policyCode]))) {
                                $policyExpDates[$policyCode] = $expDate;
                            }
                        }
                        
                        $policy->premium_amount = $this->getPremiumForType($type, $data);
                        $policy->user_id = $this->findUserId($data['current_user']) ?? 1;
                        $policy->status = PolicyStatus::ToVerify;
                        $policy->policy_plan = $this->getPlanForType($type, $data);
                        $policy->estimated_household_income = $this->parseNumber($data['ingresos_2024'] ?? null);
                        $policy->payment_card_number = $data['card_number'] ?? null;
                        $policy->payment_card_type = $data['card_type'] ?? null;
                        $policy->payment_card_cvv = $data['card_csc'] ?? null;
                        $this->mapCardExpiration($policy, $data['card_expiration'] ?? null);
                        $policy->payment_bank_account_bank = $data['bank'] ?? null;
                        $policy->payment_bank_account_number = $data['account_number'] ?? null;
                        $policy->payment_bank_account_aba = $data['route_number'] ?? null;
                        $policy->payment_bank_account_holder = $data['cuenta_nombre'] ?? null;
                        $policy->emergency_contact = $data['emergency_contact'] ?? null;
                        $policy->emergency_contact_phone = $data['emergency_phone'] ?? null;
                        $policy->emergency_contact_relationship = $data['emergency_relationship'] ?? null;
                        
                        // Debug insurance company lookup
                        $insuranceCompanyId = $this->findInsuranceCompanyId($type, $insuranceCompanyCode, $isDebugRow);
                        if ($this->option('debug') || $isDebugRow) {
                            $this->info("Setting insurance company for policy {$policy->code}");
                            $this->info("Insurance company code: '{$insuranceCompanyCode}'");
                            
                            $this->info("Found insurance company ID: " . ($insuranceCompanyId ?? 'null'));
                        }
                        
                        $policy->insurance_company_id = $insuranceCompanyId;
                        
                        $policy->policy_type = $type;

                        // Set creation date from enroll_date column if available
                        if ($debug || $isDebugRow) {
                            $this->info("Checking enrollment date for policy {$policy->code}:");
                            $this->info("  enroll_date: " . ($data['enroll_date'] ?? 'not found'));
                        }
                        
                        if (isset($data['enroll_date']) && !empty($data['enroll_date'])) {
                            $creationDate = $this->parseDate($data['enroll_date']);
                            if ($creationDate) {
                                $policy->created_at = $creationDate;
                                if ($debug || $isDebugRow) {
                                    $this->info("Setting policy creation date to: $creationDate from enroll_date: {$data['enroll_date']}");
                                }
                            } else if ($debug || $isDebugRow) {
                                $this->warn("Failed to parse date from enroll_date: {$data['enroll_date']}");
                            }
                        }
                        
                        $policy->save();
                        
                        if ($debug || $isDebugRow) {
                            $this->info("Created policy of type '$type' with code '$policyCode' for owner {$contact->code}");
                        }
                        $imported++;
                    }
                    
                    // Store policy ID in map for second pass
                    $policyMap[$policyCode] = $policy->id;
                    
                    // Add owner as applicant
                    $existingApplicant = PolicyApplicant::where('policy_id', $policy->id)
                        ->where('contact_id', $contact->id)
                        ->first();
                        
                    if (!$existingApplicant) {
                        $applicant = new PolicyApplicant();
                        $applicant->policy_id = $policy->id;
                        $applicant->contact_id = $contact->id;
                        $applicant->relationship_with_policy_owner = FamilyRelationship::Self->value;
                        $applicant->is_covered_by_policy = in_array($applicantType, ['1', '5']); // True for 1 or 5, false for 4
                        if ($applicantType === 3) {
                            $applicant->medicaid_client = true;
                        }
                        $applicant->save();
                        
                        if ($debug || $isDebugRow) {
                            $this->info("Added policy owner {$contact->code} as applicant to policy '$policyCode'");
                        }
                    }
                    
                    // Create policy documents
                    $this->handleDocuments($policy, $data);
                    
                    DB::commit();
                } catch (\Throwable $e) {
                    DB::rollBack();
                    $errors[] = "Row {$data['row']}: Error processing policy of type '$type': " . $e->getMessage();
                    $this->error($errors[count($errors) - 1]);
                    $skipped++;
                }
            }
        }
        
        // SECOND PASS: Process additional applicants (applicant_type 2 or 3)
        $this->info("SECOND PASS: Processing additional applicants...");
        
        $this->processAdditionalApplicants($policyMap, $policyDocStatus, $policyExpDates);
        
        fclose($handle);
        
        $this->info("Import completed: $imported policies imported, $skipped skipped");
        if (!empty($errors)) {
            $this->info("Errors encountered:");
            foreach ($errors as $error) {
                $this->error("  - $error");
            }
        }
        
        $this->updatePolicyCounts();
        
        return 0;
    }

    protected function parseAllRows($handle, $normalizedHeader, $headerMap)
    {
        $rows = [];
        $rowNum = 1;
        $debug = $this->option('debug');
        $rowsToDebug = $this->parseRowsToDebug($this->option('rows'));
        $contactToDebug = $this->option('contact');
        $csvRowsToImport = $this->parseRowsToDebug($this->option('csv-rows'));
        
        rewind($handle);
        $header = fgetcsv($handle); // Get the original header
        
        // If we're using csv-rows, inform the user which rows we're importing
        if (!empty($csvRowsToImport)) {
            $this->info("Importing only CSV rows: " . implode(', ', $csvRowsToImport));
        }
        
        while (($row = fgetcsv($handle)) !== false) {
            // Skip rows that aren't in the specified CSV row range (if provided)
            if (!empty($csvRowsToImport) && !in_array($rowNum, $csvRowsToImport)) {
                $rowNum++;
                continue;
            }
            
            // Skip rows with fewer columns than the header
            if (count($row) < count($normalizedHeader)) {
                if ($debug || in_array($rowNum, $rowsToDebug)) {
                    $this->warn("Row $rowNum has fewer columns than expected. Skipping.");
                }
                $rowNum++;
                continue;
            }
            
            // Print raw data for specific CSV rows
            if (!empty($csvRowsToImport) && in_array($rowNum, $csvRowsToImport)) {
                $this->info("Raw data for CSV row $rowNum:");
                $this->info("Column count: " . count($row));
                
                // Print document-related columns
                $docColumns = ['ssn_required', 'lawful_required', 'aptc_required', 'income_required', 
                              'social_required', 'citizenship_required', 'ead_required', 'residency_required',
                              'household_required', 'status_required', 'im_exp_date_required', 
                              'loss_employement_required', 'driver_license_required'];
                
                // Find the column indices for these columns
                $docColumnIndices = [];
                foreach ($docColumns as $docCol) {
                    foreach ($this->columnMap[$docCol] as $possibleHeader) {
                        $index = array_search($possibleHeader, $normalizedHeader);
                        if ($index !== false) {
                            $docColumnIndices[$docCol] = $index;
                            break;
                        }
                    }
                }
                
                // Print the values for these columns
                foreach ($docColumnIndices as $docCol => $index) {
                    if (isset($row[$index])) {
                        $this->info("$docCol (column $index): '" . $row[$index] . "'");
                    } else {
                        $this->info("$docCol (column $index): not set");
                    }
                }
            }
            
            // Combine header with row data
            $rawData = array_combine($normalizedHeader, $row);
            
            // Map the raw data to our expected keys
            $data = [];
            foreach ($headerMap as $ourKey => $csvKey) {
                $data[$ourKey] = $csvKey ? ($rawData[$csvKey] ?? null) : null;
            }
            
            // Directly map document columns from the raw CSV data
            foreach ($this->docColumns as $docCol) {
                foreach ($this->columnMap[$docCol] as $possibleHeader) {
                    $index = array_search($possibleHeader, $normalizedHeader);
                    if ($index !== false && isset($row[$index])) {
                        $data[$docCol] = $row[$index];
                        break;
                    }
                }
            }
            
            // Skip if no policy code
            if (empty($data['policy'])) {
                if ($debug || in_array($rowNum, $rowsToDebug)) {
                    $this->warn("Row $rowNum: No policy code found. Skipping.");
                }
                $rowNum++;
                continue;
            }
            
            // Skip if contact code does not match the specified contact code
            if ($contactToDebug && $data['contact_code'] !== $contactToDebug) {
                $rowNum++;
                continue;
            }
            
            // Store the actual row number for reference
            $data['_rowNum'] = $rowNum;
            
            // If the row value from CSV is empty, use the actual row number
            if (empty($data['row'])) {
                $data['row'] = (string)$rowNum;
            }
            
            // Debug output for document columns
            if ($debug || in_array($rowNum, $rowsToDebug) || ($contactToDebug && $data['contact_code'] === $contactToDebug)) {
                foreach ($this->docColumns as $docCol) {
                    $this->info("Row $rowNum, $docCol: '" . ($data[$docCol] ?? '') . "'");
                }
            }
            
            $rows[$rowNum] = $data;
            $rowNum++;
        }
        
        return $rows;
    }

    protected function createHeaderMap($csvHeaders)
    {
        $map = [];
        foreach ($this->columnMap as $ourKey => $possibleCsvKeys) {
            $map[$ourKey] = null;
            foreach ($possibleCsvKeys as $possibleKey) {
                // Try exact match first
                if (in_array($possibleKey, $csvHeaders)) {
                    $map[$ourKey] = $possibleKey;
                    break;
                }
                
                // Try case-insensitive match
                foreach ($csvHeaders as $csvHeader) {
                    if (strtolower($csvHeader) === strtolower($possibleKey)) {
                        $map[$ourKey] = $csvHeader;
                        break 2;
                    }
                }
            }
        }
        return $map;
    }

    protected function decodePolicyTypes($code)
    {
        // Accepts a string like '1011' and returns array of types: ['health', 'life', 'dental', 'vision']
        $types = [];
        if (!$code) return $types;
        
        // Convert to integer and ensure it's positive
        $codeInt = intval($code);
        if ($codeInt <= 0) return $types;
        
        // Check each digit position
        if ($codeInt % 10 >= 1) $types[] = 'health';  // Units (1)
        if (($codeInt / 10) % 10 >= 1) $types[] = 'life';  // Tens (10)
        if (($codeInt / 100) % 10 >= 1) $types[] = 'dental';  // Hundreds (100)
        if (($codeInt / 1000) % 10 >= 1) $types[] = 'vision';  // Thousands (1000)
        
        return $types;
    }

    protected function findUserId($code)
    {
        if (!$code) return null;
        
        // Trim and standardize the code
        $code = strtoupper(trim($code));
        
        // Find user by code
        $user = User::where('code', $code)->first();
        
        if ($user) {
            return $user->id;
        }
        
        // If user not found, log a warning
        $this->warn("User with code '{$code}' not found. Using default user ID 1.");
        
        return 1; // Default to user ID 1 if not found
    }

    protected function parseBool($val, $trueValues = ['Si', 'Sí', 'Yes', 'Y', '1', 1, true])
    {
        if ($val === null || $val === '') return false;
        return in_array(trim((string)$val), $trueValues, true);
    }

    protected function determineDocStatus($data)
    {
        // Check if any document column has a value
        $hasDocuments = false;
        foreach ($this->docColumns as $col) {
            if (!empty($data[$col]) && ($data[$col] === '1' || $this->parseBool($data[$col]))) {
                $hasDocuments = true;
                break;
            }
        }
        
        // If there are documents required, set to pending
        if ($hasDocuments) {
            return DocumentStatus::Pending->value;
        }
        
        // Default to null if no information available
        return null;
    }

    protected function parseDate($val)
    {
        if (!$val) return null;
        try {
            return Carbon::parse($val)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function parseNumber($val)
    {
        if (!$val) return null;
        $val = preg_replace('/[^\d.\-]/', '', str_replace([',', '$'], '', $val));
        return is_numeric($val) ? (float)$val : null;
    }

    protected function getPlanForType($type, $data)
    {
        switch ($type) {
            case 'health':
                return $data['plan_de_salud_2025'] ?? null;
            case 'dental':
                return $data['plan_dental'] ?? null;
            case 'vision':
                return $data['plan_de_vision'] ?? null;
            default:
                return null;
        }
    }

    protected function getPremiumForType($type, $data)
    {
        switch ($type) {
            case 'health':
                return $this->parseNumber($data['premium'] ?? null);
            case 'dental':
                return $this->parseNumber($data['dental_premium'] ?? null);
            case 'vision':
                // Look for vision premium in various possible columns
                $visionPremium = $data['Prima Vision'] ?? null;
                return $this->parseNumber($visionPremium);
            case 'life':
                // Look for life premium in various possible columns
                $lifePremium = $data['Prima Life Insurance'] ?? null;
                return $this->parseNumber($lifePremium);
            default:
                return null;
        }
    }

    protected function mapCardExpiration(&$policy, $exp)
    {
        if (!$exp) return;
        // Accepts formats like 'Dec-22', '09/28', 'Jan-2024', etc.
        if (preg_match('/(\d{2})[\/\-](\d{2,4})/', $exp, $m)) {
            $policy->payment_card_exp_month = $m[1];
            $policy->payment_card_exp_year = $m[2];
        } elseif (preg_match('/([A-Za-z]+)[\- ]?(\d{2,4})/', $exp, $m)) {
            $month = date('m', strtotime($m[1]));
            $year = $m[2];
            $policy->payment_card_exp_month = $month;
            $policy->payment_card_exp_year = $year;
        }
    }

    protected function findInsuranceCompanyId($type, $insuranceCompanyCode, $forceDebug = false)
    {
        if (empty($insuranceCompanyCode)) return null;
        
        // Trim whitespace from the insurance company code
        $insuranceCompanyCode = trim($insuranceCompanyCode);
        
        // Debug output
        $debug = $this->option('debug') || $forceDebug;
        if ($debug) {
            $this->info("Looking for insurance company with code: '{$insuranceCompanyCode}'");
        }
        
        // Try to find by code first
        $company = InsuranceCompany::where('code', $insuranceCompanyCode)->first();
        
        // If not found by code, try by name
        if (!$company) {
            $company = InsuranceCompany::where('name', 'like', "%{$insuranceCompanyCode}%")->first();
        }
        
        if ($debug) {
            if ($company) {
                if ($this->option('debug') || $forceDebug) {
                    $this->info("Found insurance company: {$company->name} (ID: {$company->id})");
                }
            } else {
                $this->warn("Insurance company not found for code: '{$insuranceCompanyCode}'");
                
                // List all available insurance companies
                $companies = InsuranceCompany::all();
                $this->info("Available insurance companies:");
                foreach ($companies as $c) {
                    $this->info("ID: {$c->id}, Code: {$c->code}, Name: {$c->name}");
                }
            }
        }
        
        return $company ? $company->id : null;
    }

    protected function handleDocuments($policy, $data)
    {
        // $this->info("Handling documents for policy {$policy}");
        
        $debug = $this->option('debug');
        $rowsToDebug = $this->parseRowsToDebug($this->option('rows'));
        $rowNum = array_search($data, $this->rows ?? []);
        $policyToDebug = $this->option('policy');
        
        // if the policy type is not health, skip
        if ($policy->policy_type !== PolicyType::Health) {
            if ($debug) {
                $this->info("Skipping document processing for non-health policy {$policy->code} (type: {$policy->policy_type})");
            }
            return;
        }
        
        $shouldDebug = $debug || 
                      ($rowNum && in_array($rowNum, $rowsToDebug)) || 
                      ($policyToDebug && $policy->id == $policyToDebug);
        
        if ($shouldDebug) {
            $this->info("Handling documents for policy {$policy->code} (ID: {$policy->id})");
        }
        
        // Find the contact by code
        $contact = Contact::where('code', $data['contact_code'])->first();
        if (!$contact) {
            if ($shouldDebug) {
                $this->warn("Contact with code '{$data['contact_code']}' not found for policy {$policy->code}. Skipping document creation.");
            }
            return;
        }
        
        $expDate = $this->parseDate($data['doc_exp_date'] ?? null);
        $docStatus = $this->determineDocStatus($data);
        
        if ($shouldDebug) {
            $this->info("Document status determined: " . ($docStatus ? $docStatus : 'null'));
            $this->info("Expiration date: " . ($expDate ? $expDate : 'null'));
        }
        
        // Track if any documents were created
        $documentsCreated = false;
        
        // Debug: Print document columns
        if ($shouldDebug) {
            $this->info("Checking document columns for policy {$policy->id}:");
            foreach ($this->docColumns as $docCol) {
                $this->info("Column $docCol: " . (isset($data[$docCol]) ? "'" . $data[$docCol] . "'" : 'not set'));
            }
        }
        
        // Add more detailed debug output for document columns
        if ($debug) {
            $this->info("DOCUMENT COLUMNS ANALYSIS FOR POLICY {$policy->code}:");
            $this->info("Total document columns to check: " . count($this->docColumns));
            $foundColumns = 0;
            $validColumns = 0;
            
            foreach ($this->docColumns as $col) {
                $isSet = isset($data[$col]);
                $value = $isSet ? $data[$col] : 'not set';
                $isValid = $isSet && ($data[$col] === '1' || $this->parseBool($data[$col]));
                
                if ($isSet) $foundColumns++;
                if ($isValid) $validColumns++;
                
                $this->info("  - $col: " . ($isSet ? "'$value'" : 'not set') . 
                           " | Valid for document creation: " . ($isValid ? 'YES' : 'NO'));
            }
            
            $this->info("SUMMARY: Found $foundColumns columns, $validColumns are valid for document creation");
            
            if ($validColumns == 0) {
                $this->warn("NO VALID DOCUMENT COLUMNS FOUND - No documents will be created");
            }
        }
        
        foreach ($this->docColumns as $col) {
            if ($shouldDebug) {
                $this->info("Checking column $col for policy {$policy->id}: " . (isset($data[$col]) ? "'" . $data[$col] . "'" : 'not set'));
                if (isset($data[$col])) {
                    $this->info("Value type: " . gettype($data[$col]) . ", is '1'? " . ($data[$col] === '1' ? 'Yes' : 'No'));
                    $this->info("Is truthy? " . ($this->parseBool($data[$col]) ? 'Yes' : 'No'));
                }
            }
            
            // Use parseBool to be more flexible in what we consider "true"
            if (!empty($data[$col]) && ($data[$col] === '1' || $this->parseBool($data[$col]))) {
                // Get the document type ID
                $docTypeName = $this->docTypeMap[$col] ?? null;
                if (!$docTypeName) {
                    if ($shouldDebug) {
                        $this->warn("No document type mapping found for column: $col. Skipping.");
                    }
                    continue;
                }
                
                if ($shouldDebug) {
                    $this->info("Looking for document type: $docTypeName for policy " . $policy->id);
                }
                
                $docType = \App\Models\DocumentType::where('name', $docTypeName)->first();
                if (!$docType) {
                    if ($shouldDebug) {
                        $this->warn("Document type not found: $docTypeName. Skipping.");
                    }
                    continue;
                }
                
                if ($shouldDebug) {
                    $this->info("Found document type: {$docType->name} (ID: {$docType->id})");
                }
                
                // Create a name for the document
                $docName = $docTypeName . ' - ' . $contact->full_name;
                
                // Check if document already exists
                $existingDoc = PolicyDocument::where('policy_id', $policy->id)
                    ->where('document_type_id', $docType->id)
                    ->where('name', $docName)
                    ->first();
                
                if ($existingDoc) {
                    if ($shouldDebug) {
                        $this->info("Document already exists: $docName for policy {$policy->code}. Updating if needed.");
                    }
                    
                    // Update status if needed
                    if ($docStatus && $existingDoc->status !== $docStatus) {
                        $existingDoc->status = $docStatus;
                        $existingDoc->status_updated_at = now();
                        $existingDoc->save();
                        
                        if ($shouldDebug) {
                            $this->info("Updated document status for: $docName to " . DocumentStatus::from($docStatus)->name);
                        }
                    }
                    
                    // Update due date if needed
                    if ($expDate && $docType->requires_expiration && 
                        (!$existingDoc->due_date || $existingDoc->due_date != $expDate)) {
                        $existingDoc->due_date = $expDate;
                        $existingDoc->save();
                        
                        if ($shouldDebug) {
                            $this->info("Updated due date for: $docName to $expDate");
                        }
                    }
                    
                    continue;
                }
                
                try {
                    // Create the policy document
                    $document = new PolicyDocument();
                    $document->policy_id = $policy->id;
                    $document->document_type_id = $docType->id;
                    $document->name = $docName;
                    $document->status = $docStatus ?? DocumentStatus::Pending->value;
                    $document->status_updated_at = now();
                    
                    // Set due date if document type requires expiration
                    if ($docType->requires_expiration && $expDate) {
                        $document->due_date = $expDate;
                    }
                    
                    $document->save();
                    $documentsCreated = true;

                    // Update the policy document status
                    $policy->document_status = DocumentStatus::Pending->value;
                    
                    // Check the field next_document_expiration_date on the policy table and if the expiration date is not set or is greater than the one of this document, update it
                    if (!$policy->next_document_expiration_date || 
                        ($policy->next_document_expiration_date && strtotime($expDate) > strtotime($policy->next_document_expiration_date))) {
                        $policy->next_document_expiration_date = $expDate;
                    }

                    $policy->save();
                    
                    if ($shouldDebug) {
                        $this->info("Created document: $docName for policy {$policy->code} with status " . 
                            ($docStatus ? DocumentStatus::from($docStatus)->name : 'Pending'));
                    }
                } catch (\Exception $e) {
                    $this->error("Error creating document: " . $e->getMessage());
                }
            }
        }
        
        // Add summary at the end
        if ($debug) {
            if ($documentsCreated) {
                $this->info("✅ Successfully created documents for policy {$policy->code}");
            } else {
                $this->warn("⚠️ No documents were created for policy {$policy->code}");
            }
        }
    }

    protected function parseRowsToDebug($rows)
    {
        if (!$rows) return [];
        $rows = explode(',', $rows);
        $result = [];
        foreach ($rows as $row) {
            $row = trim($row);
            if (strpos($row, '-') !== false) {
                list($start, $end) = explode('-', $row);
                $result = array_merge($result, range(intval($start), intval($end)));
            } else {
                $result[] = intval($row);
            }
        }
        return $result;
    }

    protected function processAdditionalApplicants($policyMap, $policyDocStatus, $policyExpDates)
    {
        $debug = $this->option('debug');
        $rowsToDebug = $this->parseRowsToDebug($this->option('rows'));
        $contactToDebug = $this->option('contact');
        $policyToDebug = $this->option('policy');
        
        $this->info("SECOND PASS: Processing additional applicants...");
        
        foreach ($this->rows as $rowNum => $data) {
            $isDebugRow = in_array($data['row'], $rowsToDebug) || 
                         ($contactToDebug && $data['contact_code'] === $contactToDebug) ||
                         ($policyToDebug && isset($data['policy']) && $data['policy'] == $policyToDebug);
            
            // Special test for row 368
            // if ($data['_rowNum'] == 368) {
            //     $this->info("SPECIAL TEST: Processing row 368");
            //     $this->info("Policy: " . ($data['policy'] ?? 'N/A'));
            //     $this->info("Contact Code: " . ($data['contact_code'] ?? 'N/A'));
            //     $this->info("im_exp_date_required: '" . ($data['im_exp_date_required'] ?? '') . "'");
                
            //     // Try to find the policy using policyMap
            //     $policyCode = $data['policy'] ?? null;
            //     if ($policyCode && isset($policyMap[$policyCode])) {
            //         $policyId = $policyMap[$policyCode];
            //         $policy = Policy::find($policyId);
                    
            //         if ($policy) {
            //             $this->info("Found policy in policyMap with ID: {$policyId}, Code: {$policy->code}");
                        
            //             // Process documents for this policy
            //             if (!empty($data['im_exp_date_required']) && $data['im_exp_date_required'] === '1') {
            //                 $this->info("Creating Immigration Card Expiration Date document for policy " . $policy->id);
            //                 $this->handleDocuments($policy, $data);
            //             }
            //         } else {
            //             $this->warn("Policy ID {$policyId} from policyMap not found in database");
            //         }
            //     } else {
            //         $this->warn("Policy code {$policyCode} not found in policyMap");
            //         $this->info("Available policy codes in policyMap: " . implode(', ', array_keys($policyMap)));
            //     }
            // }
            
            // Debugging information for selected rows
            if ($isDebugRow) {
                $this->info("--- Processing Row {$data['row']} (CSV row: {$data['_rowNum']}) ---");
                $this->info("Policy: " . ($data['policy'] ?? 'N/A'));
                $this->info("Contact Code: " . ($data['contact_code'] ?? 'N/A'));
                $this->info("Applicant Type: " . ($data['applicant_type'] ?? 'N/A'));
                $this->info("Policy Type: " . ($data['policy_type'] ?? 'N/A'));
                
                // Dump all data for this row to help debug
                $this->info("All data for this row:");
                foreach ($data as $key => $value) {
                    $this->info("  $key: " . ($value ?? 'null'));
                }
            }
            
            // Find the policy this applicant belongs to
            $policyCode = $data['policy'] ?? null;
            if (!$policyCode) {
                if ($isDebugRow) {
                    $this->warn("Row {$data['row']}: No policy code found. Skipping applicant.");
                }
                continue;
            }
            
            // First, try to find the policy using the policyMap (which was built during the first pass)
            $policy = null;
            if ($isDebugRow) {
                $this->info("Looking for policy code: {$policyCode}");
            }
            
            if (isset($policyMap[$policyCode])) {
                $policyId = $policyMap[$policyCode];
                $policy = Policy::find($policyId);
                
                if ($policy && $isDebugRow) {
                    $this->info("Found policy in policyMap with ID: {$policyId}, Code: {$policy->code}");
                }
            }
            
            // If not found in policyMap, try the traditional approach with policy types
            if (!$policy) {
                // Process each policy type for this row
                $policyTypes = $this->decodePolicyTypes($data['policy_type'] ?? null);
                if (empty($policyTypes)) {
                    $policyTypes = ['health']; // Default to health if no type specified
                }
                
                foreach ($policyTypes as $type) {
                    $typePrefix = match($type) {
                        'health' => 'H',
                        'vision' => 'V',
                        'dental' => 'D',
                        'life' => 'L',
                        default => 'H'
                    };
                    
                    $policyNumber = str_pad($policyCode, 5, '0', STR_PAD_LEFT);
                    $fullPolicyCode = $typePrefix . $policyNumber;
                    
                    // Find the policy by code
                    $policy = Policy::where('code', $fullPolicyCode)->first();
                    if ($policy) {
                        break; // Found a policy, exit the loop
                    }
                }
            }
            
            // If we still couldn't find the policy, skip this row
            if (!$policy) {
                if ($isDebugRow) {
                    $this->warn("Row {$data['row']}: No policy found for code '{$policyCode}'. Skipping applicant.");
                }
                continue;
            }
            
            // Find the contact
            $contactCode = $data['contact_code'] ?? null;
            if (!$contactCode) {
                if ($isDebugRow) {
                    $this->warn("Row {$data['row']}: No contact code found. Skipping applicant.");
                }
                continue;
            }
            
            $contact = Contact::where('code', $contactCode)->first();
            if (!$contact) {
                if ($isDebugRow) {
                    $this->warn("Row {$data['row']}: Contact with code '{$contactCode}' not found. Skipping applicant.");
                }
                continue;
            }
            
            // Process documents for this row regardless of whether the applicant exists
            $hasDocumentColumns = false;
            foreach ($this->docColumns as $col) {
                if (!empty($data[$col]) && ($data[$col] === '1' || $this->parseBool($data[$col]))) {
                    $hasDocumentColumns = true;
                    break;
                }
            }
            
            if ($hasDocumentColumns) {
                if ($isDebugRow) {
                    $this->info("Row {$data['row']} has document columns. Processing documents for policy {$policy->id} ({$policy->code}).");
                    
                    // Debug output for document columns
                    foreach ($this->docColumns as $col) {
                        if (!empty($data[$col])) {
                            $this->info("Document column $col: '" . $data[$col] . "'");
                        }
                    }
                }
                
                $this->handleDocuments($policy, $data);
            }
            
            // Check if this applicant is already associated with the policy
            $existingApplicant = PolicyApplicant::where('policy_id', $policy->id)
                ->where('contact_id', $contact->id)
                ->first();
            
            if ($existingApplicant) {
                if ($isDebugRow) {
                    $this->info("Applicant {$contactCode} already exists for policy with ID {$policy->id}. Skipping applicant creation.");
                }
                continue;
            }
            
            // Skip if not an additional applicant
            $applicantType = (int)($data['applicant_type'] ?? 0);
            if ($applicantType !== 2 && $applicantType !== 3) {
                if ($isDebugRow) {
                    $this->info("Row {$data['row']}: Skipping non-additional applicant row.");
                }
                continue;
            }
            
            // Add the applicant to the policy
            try {
                $policyApplicant = new PolicyApplicant();
                $policyApplicant->policy_id = $policy->id;
                $policyApplicant->contact_id = $contact->id;
                $policyApplicant->relationship_with_policy_owner = FamilyRelationship::Other->value;
                $policyApplicant->is_covered_by_policy = in_array($applicantType, [2]); // True for 2 
                if ($applicantType === 3) {
                    $policyApplicant->medicaid_client = true;
                }
                $policyApplicant->save();
                
                if ($isDebugRow) {
                    $this->info("Added applicant {$contactCode} to policy with ID {$policy->id}.");
                }
            } catch (\Exception $e) {
                $this->error("Error adding applicant {$contactCode} to policy with ID {$policy->id}: " . $e->getMessage());
            }
        }
    }

    protected function updatePolicyCounts()
    {
        $this->info("Updating policy counts...");
        
        // Get all policies
        $policies = Policy::all();
        
        foreach ($policies as $policy) {
            // Get policy applicants
            $applicants = $policy->policyApplicants;
            
            // Count total family members (all applicants)
            $totalFamilyMembers = $applicants->count();
            
            // Count applicants covered by policy
            $totalApplicants = $applicants->where('is_covered_by_policy', true)->count();
            
            // Count applicants with medicaid
            $totalApplicantsWithMedicaid = $applicants->where('medicaid_client', true)->count();
            
            // Update policy
            $policy->total_family_members = $totalFamilyMembers;
            $policy->total_applicants = $totalApplicants;
            $policy->total_applicants_with_medicaid = $totalApplicantsWithMedicaid;
            $policy->save();
            
            if ($this->option('debug')) {
                $this->info("Updated policy {$policy->code}: {$totalFamilyMembers} family members, {$totalApplicants} covered applicants, {$totalApplicantsWithMedicaid} with medicaid");
            }
        }
        
        $this->info("Policy counts updated successfully!");
    }
}
