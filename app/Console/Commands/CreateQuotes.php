<?php

namespace App\Console\Commands;

use App\Enums\QuoteStatus;
use App\Enums\PolicyType;
use App\Models\Contact;
use App\Models\Quote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CreateQuotes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'factory:quotes
                            {count=5 : Number of quotes to create}
                            {--status= : Set quote status (pending|sent|accepted|rejected|converted)}
                            {--policy_types= : Comma-separated policy types (health,dental,vision,accident,life)}
                            {--contact_id= : Use a specific contact ID}
                            {--user_id= : Use a specific user ID}
                            {--date= : Set specific quote date (YYYY-MM-DD)}
                            {--date-range= : Date range for quotes (last_week|last_month|this_month|this_year)}
                            {--date-start= : Start date for custom range (YYYY-MM-DD)}
                            {--date-end= : End date for custom range (YYYY-MM-DD)}
                            {--random-dates : Randomize dates within the selected range}
                            {--applicants=1 : Number of applicants for each quote}
                            {--show : Show detailed information about created quotes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create quotes using the Quote factory';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->argument('count');
        $status = $this->option('status');
        $policyTypes = $this->option('policy_types');
        $contactId = $this->option('contact_id');
        $userId = $this->option('user_id');
        $dateOption = $this->option('date');
        $dateRange = $this->option('date-range');
        $dateStart = $this->option('date-start');
        $dateEnd = $this->option('date-end');
        $randomDates = $this->option('random-dates');
        $applicants = (int) $this->option('applicants');
        $showDetails = $this->option('show');
        
        // Determine the date range to use
        $dateRangeBounds = $this->resolveDateRange($dateRange, $dateStart, $dateEnd, $dateOption);

        // Validate status if provided
        $statusEnum = null;
        if ($status) {
            try {
                $statusEnum = match(strtolower($status)) {
                    'pending' => QuoteStatus::Pending,
                    'sent' => QuoteStatus::Sent,
                    'accepted' => QuoteStatus::Accepted,
                    'rejected' => QuoteStatus::Rejected,
                    'converted' => QuoteStatus::Converted,
                    default => null,
                };
            } catch (\UnhandledMatchError $e) {
                $this->error("Invalid status: '{$status}'. Must be one of: pending, sent, accepted, rejected, converted");
                return 1;
            }
        }

        // Parse policy types if provided
        $policyTypesArray = [];
        if ($policyTypes) {
            $types = explode(',', $policyTypes);
            foreach ($types as $type) {
                try {
                    $typeEnum = match(strtolower(trim($type))) {
                        'health' => PolicyType::Health,
                        'dental' => PolicyType::Dental,
                        'vision' => PolicyType::Vision,
                        'accident' => PolicyType::Accident,
                        'life' => PolicyType::Life,
                        default => null,
                    };
                    
                    if ($typeEnum) {
                        $policyTypesArray[] = $typeEnum->value;
                    } else {
                        $this->warn("Ignoring invalid policy type: '{$type}'");
                    }
                } catch (\UnhandledMatchError $e) {
                    $this->warn("Ignoring invalid policy type: '{$type}'");
                }
            }
            
            if (empty($policyTypesArray)) {
                $this->error("No valid policy types provided. Must be one or more of: health, dental, vision, accident, life");
                return 1;
            }
        }

        // Check contact if provided
        if ($contactId) {
            $contact = Contact::find($contactId);
            if (!$contact) {
                $this->error("Contact with ID {$contactId} not found.");
                return 1;
            }
        }

        // Check user if provided
        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return 1;
            }
        }

        // Verify that we have valid date information
        if (!$dateRangeBounds) {
            return 1; // Error message already shown in resolveDateRange
        }

        // Start creating quotes
        $this->info('Creating ' . $count . ' quotes' . $this->getDateRangeDescription($dateRangeBounds) . '...');
        $createdQuotes = [];

        // Factory state setup
        for ($i = 0; $i < $count; $i++) {
            $factory = Quote::factory()
                ->when($status, function ($factory, $statusValue) use ($statusEnum) {
                    return $factory->state(['status' => $statusEnum]);
                })
                ->when(!empty($policyTypesArray), function ($factory) use ($policyTypesArray) {
                    return $factory->state(['policy_types' => $policyTypesArray]);
                })
                ->when($contactId, function ($factory) use ($contactId) {
                    return $factory->state(['contact_id' => $contactId]);
                })
                ->when($userId, function ($factory) use ($userId) {
                    return $factory->state(['user_id' => $userId]);
                })
                ->when($dateRangeBounds, function ($factory) use ($dateRangeBounds, $randomDates, $i, $count) {
                    // If randomized dates are requested, generate a random date within the range
                    // Otherwise, distribute quotes evenly across the date range
                    if ($randomDates) {
                        $date = Carbon::createFromTimestamp(mt_rand(
                            $dateRangeBounds['start']->timestamp, 
                            $dateRangeBounds['end']->timestamp
                        ));
                    } else {
                        // Distribute evenly across the range
                        $totalSeconds = $dateRangeBounds['end']->timestamp - $dateRangeBounds['start']->timestamp;
                        $interval = $count > 1 ? $totalSeconds / ($count - 1) : 0;
                        $timestamp = $dateRangeBounds['start']->timestamp + ($interval * $i);
                        $date = Carbon::createFromTimestamp($timestamp);
                    }
                    
                    return $factory->state([
                        'date' => $date,
                        'created_at' => $date,
                        'updated_at' => $date
                    ]);
                })
                ->when($applicants > 1, function ($factory) use ($applicants) {
                    // Custom handling for applicants count, but this requires internal knowledge of the factory
                    // This may need to be adjusted based on your specific QuoteFactory implementation
                    return $factory->state([
                        'total_applicants' => $applicants,
                        'total_family_members' => $applicants + rand(0, 2)
                    ]);
                });

            // Create the quote
            $quote = $factory->create();
            $createdQuotes[] = $quote;
            
            $this->info("✅ Created Quote #{$quote->id} with status: {$quote->status->value}");
        }

        // Show a summary of created quotes
        if ($count > 0) {
            $this->showQuoteSummary($createdQuotes, $showDetails);
        }
        
        return 0;
    }

    /**
     * Resolves the date range based on provided options
     * 
     * @param string|null $dateRange Predefined range (last_week, last_month, this_month, this_year)
     * @param string|null $dateStart Custom range start date
     * @param string|null $dateEnd Custom range end date
     * @param string|null $specificDate Specific date override
     * @return array|null Array with 'start' and 'end' Carbon dates, or null if invalid
     */
    private function resolveDateRange($dateRange, $dateStart, $dateEnd, $specificDate)
    {
        // Specific date takes precedence over ranges
        if ($specificDate) {
            if ($specificDate === 'now') {
                $date = Carbon::now()->startOfDay();
                return [
                    'start' => $date->copy(),
                    'end' => $date->copy(),
                    'description' => ' for today'
                ];
            }
            
            try {
                $date = Carbon::createFromFormat('Y-m-d', $specificDate)->startOfDay();
                return [
                    'start' => $date->copy(),
                    'end' => $date->copy(),
                    'description' => ' for ' . $date->format('M j, Y')
                ];
            } catch (\Exception $e) {
                $this->error("Invalid date format: '{$specificDate}'. Please use YYYY-MM-DD.");
                return null;
            }
        }
        
        // Predefined ranges
        if ($dateRange) {
            $now = Carbon::now();
            $today = $now->copy()->startOfDay();
            
            switch (strtolower($dateRange)) {
                case 'last_week':
                    return [
                        'start' => $now->copy()->subWeek()->startOfWeek(),
                        'end' => $now->copy()->subWeek()->endOfWeek(),
                        'description' => ' for last week'
                    ];
                    
                case 'last_month':
                    return [
                        'start' => $now->copy()->subMonth()->startOfMonth(),
                        'end' => $now->copy()->subMonth()->endOfMonth(),
                        'description' => ' for last month'
                    ];
                    
                case 'this_month':
                    return [
                        'start' => $now->copy()->startOfMonth(),
                        'end' => $today,
                        'description' => ' for this month (until today)'
                    ];
                    
                case 'this_year':
                    return [
                        'start' => $now->copy()->startOfYear(),
                        'end' => $today,
                        'description' => ' for this year (until today)'
                    ];
                    
                default:
                    $this->error("Invalid date range: '{$dateRange}'. Valid options are: last_week, last_month, this_month, this_year");
                    return null;
            }
        }
        
        // Custom date range
        if ($dateStart || $dateEnd) {
            $start = null;
            $end = null;
            $description = '';
            
            // Parse start date if provided
            if ($dateStart) {
                try {
                    $start = Carbon::createFromFormat('Y-m-d', $dateStart)->startOfDay();
                    $description .= ' from ' . $start->format('M j, Y');
                } catch (\Exception $e) {
                    $this->error("Invalid start date format: '{$dateStart}'. Please use YYYY-MM-DD.");
                    return null;
                }
            } else {
                // Default to 30 days ago if no start date
                $start = Carbon::now()->subDays(30)->startOfDay();
                $description .= ' from 30 days ago';
            }
            
            // Parse end date if provided
            if ($dateEnd) {
                try {
                    $end = Carbon::createFromFormat('Y-m-d', $dateEnd)->endOfDay();
                    $description .= ' to ' . $end->format('M j, Y');
                } catch (\Exception $e) {
                    $this->error("Invalid end date format: '{$dateEnd}'. Please use YYYY-MM-DD.");
                    return null;
                }
            } else {
                // Default to today if no end date
                $end = Carbon::now()->endOfDay();
                $description .= ' to today';
            }
            
            // Validate that start date is before end date
            if ($start->isAfter($end)) {
                $this->error("Start date '{$dateStart}' must be before end date '{$dateEnd}'.");
                return null;
            }
            
            return [
                'start' => $start,
                'end' => $end,
                'description' => $description
            ];
        }
        
        // Default to today if no options specified
        $now = Carbon::now()->startOfDay();
        return [
            'start' => $now,
            'end' => $now,
            'description' => ' for today'
        ];
    }
    
    /**
     * Gets a user-friendly description of the date range
     * 
     * @param array $dateRange Date range data from resolveDateRange
     * @return string Description of the date range
     */
    private function getDateRangeDescription($dateRange)
    {
        return $dateRange['description'] ?? '';
    }
    
    /**
     * Show a summary of created quotes
     */
    private function showQuoteSummary($quotes, $showDetails = false)
    {
        $this->info("\n📋 Quote Summary:");
        
        $rows = [];
        foreach ($quotes as $quote) {
            // Convert policy_types array to string
            $policyTypesStr = '';
            if (is_array($quote->policy_types)) {
                $policyTypesStr = implode(', ', $quote->policy_types);
            }
            
            $rows[] = [
                $quote->id,
                $quote->status ? $quote->status->value : 'N/A',
                $policyTypesStr,
                $quote->contact ? $quote->contact->full_name : 'N/A',
                $quote->date ? $quote->date->format('Y-m-d') : 'N/A',
                count($quote->applicants ?? [])
            ];
        }
        
        $this->table(
            ['ID', 'Status', 'Policy Types', 'Contact', 'Date', 'Applicants'],
            $rows
        );
        
        // If show details flag is set, display more information about the first quote
        if ($showDetails && count($quotes) > 0) {
            $quote = $quotes[0];
            
            $this->info("\n📝 Detailed information for Quote #{$quote->id}:");
            
            // Show main applicant
            $mainApplicant = isset($quote->applicants[0]) ? $quote->applicants[0] : null;
            if ($mainApplicant) {
                $this->info("\n👤 Main Applicant:");
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['Name', $mainApplicant['full_name'] ?? 'N/A'],
                        ['Gender', $mainApplicant['gender'] ?? 'N/A'],
                        ['DOB', $mainApplicant['date_of_birth'] ?? 'N/A'],
                        ['Age', $mainApplicant['age'] ?? 'N/A'],
                        ['Relationship', $mainApplicant['relationship'] ?? 'self'],
                    ]
                );
            }
            
            // Show additional applicants summary if any
            if (count($quote->applicants ?? []) > 1) {
                $this->info("\n👥 Additional Applicants:");
                $applicantRows = [];
                
                foreach (array_slice($quote->applicants, 1) as $index => $applicant) {
                    $applicantRows[] = [
                        $index + 1,
                        $applicant['full_name'] ?? 'N/A',
                        $applicant['relationship'] ?? 'N/A',
                        $applicant['gender'] ?? 'N/A',
                        $applicant['date_of_birth'] ?? 'N/A',
                    ];
                }
                
                $this->table(
                    ['#', 'Name', 'Relationship', 'Gender', 'DOB'],
                    $applicantRows
                );
            }
            
            // Show financial information
            $this->info("\n💰 Financial Information:");
            $this->table(
                ['Field', 'Value'],
                [
                    ['Estimated Household Income', '$' . number_format($quote->estimated_household_income ?? 0, 2)],
                    ['Total Family Members', $quote->total_family_members ?? 0],
                    ['Total Applicants', $quote->total_applicants ?? 0],
                ]
            );
        }
    }
}
