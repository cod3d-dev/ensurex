<?php

namespace App\Console\Commands;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Services\PolicyAutoCompleteService;
use App\Services\QuoteConversionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class ConvertPendingQuotes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quotes:convert-batch {count=5 : Number of quotes to convert}'
                          . ' {--status=pending : Status of quotes to convert (pending/accepted)}'
                          . ' {--type= : Filter by policy type (health, dental, etc.)}'
                          . ' {--quote-date= : Filter by specific quote date (YYYY-MM-DD)}'
                          . ' {--quote-date-range= : Filter by quote date range (last_week|last_month|this_month|this_year)}'
                          . ' {--quote-date-start= : Start date for filtering quotes by date range (YYYY-MM-DD)}'
                          . ' {--quote-date-end= : End date for filtering quotes by date range (YYYY-MM-DD)}'
                          . ' {--date= : Set a specific policy creation date (YYYY-MM-DD)}'
                          . ' {--date-range= : Set policy dates within range (last_week|last_month|this_month|this_year)}'
                          . ' {--date-start= : Start date for policy date range (YYYY-MM-DD)}'
                          . ' {--date-end= : End date for policy date range (YYYY-MM-DD)}'
                          . ' {--random-dates : When used with date range, randomize dates instead of evenly spacing}'
                          . ' {--dry-run : Show what would be converted without making changes}'
                          . ' {--auto-complete : Automatically complete the policies with random data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert a batch of random pending quotes to policies';

    /**
     * Execute the console command.
     */
    public function handle(QuoteConversionService $conversionService, PolicyAutoCompleteService $autoCompleteService)
    {
        $count = (int) $this->argument('count');
        $status = $this->option('status');
        $type = $this->option('type');
        $dryRun = $this->option('dry-run');
        $autoComplete = $this->option('auto-complete');
        $randomDates = $this->option('random-dates');
        
        // Quote filtering date options
        $quoteDateOption = $this->option('quote-date');
        $quoteDateRange = $this->option('quote-date-range');
        $quoteDateStart = $this->option('quote-date-start');
        $quoteDateEnd = $this->option('quote-date-end');
        
        // Policy creation date options
        $policyDateOption = $this->option('date');
        $policyDateRange = $this->option('date-range');
        $policyDateStart = $this->option('date-start');
        $policyDateEnd = $this->option('date-end');
        
        // Determine the quote date range for filtering
        $quoteDateRangeBounds = null;
        if ($quoteDateOption || $quoteDateRange || $quoteDateStart || $quoteDateEnd) {
            $quoteDateRangeBounds = $this->resolveDateRange($quoteDateRange, $quoteDateStart, $quoteDateEnd, $quoteDateOption);
            if (!$quoteDateRangeBounds) {
                return 1; // Error message already shown in resolveDateRange
            }
        }
        
        // Determine the policy date range for created policies
        $policyDateRangeBounds = null;
        if ($policyDateOption || $policyDateRange || $policyDateStart || $policyDateEnd) {
            $policyDateRangeBounds = $this->resolveDateRange($policyDateRange, $policyDateStart, $policyDateEnd, $policyDateOption);
            if (!$policyDateRangeBounds) {
                return 1; // Error message already shown in resolveDateRange
            }
        }

        if ($dryRun) {
            $this->info("🔍 DRY RUN - No changes will be made");
        }
        
        if ($autoComplete) {
            $this->info("🔧 AUTO-COMPLETE mode enabled - Policies will be automatically completed");
        }

        // Build the query to find quotes to convert
        $query = Quote::query()
            ->whereNot('status', QuoteStatus::Converted);

        // Filter by status if provided
        if ($status) {
            try {
                $statusEnum = QuoteStatus::from($status);
                $query->where('status', $statusEnum);
            } catch (\ValueError $e) {
                $this->error("Invalid status: {$status}");
                return 1;
            }
        }

        // Filter by policy type if provided
        if ($type) {
            $query->where(function($q) use ($type) {
                // This handles searching within the JSON policy_types array
                $q->whereJsonContains('policy_types', $type);
            });
        }
        
        // Filter by quote date range if provided
        if ($quoteDateRangeBounds) {
            $query->whereBetween('date', [
                $quoteDateRangeBounds['start']->format('Y-m-d'), 
                $quoteDateRangeBounds['end']->format('Y-m-d')
            ]);
        }

        // Update message with quote date range info if applicable
        $dateFilterInfo = $this->getDateRangeDescription($quoteDateRangeBounds);
        
        // Add info about policy date setting if applicable
        $policyDateInfo = '';
        if ($policyDateRangeBounds) {
            $policyDateInfo = ' (policy dates will be ' . 
                ($randomDates ? 'randomized' : 'evenly spaced') . 
                $this->getDateRangeDescription($policyDateRangeBounds) . ')';  
        }
        
        // Get the total count of available quotes
        $availableCount = $query->count();

        if ($availableCount === 0) {
            $this->warn("No quotes found matching your criteria.");
            return 1;
        }

        // Adjust count if more requested than available
        if ($count > $availableCount) {
            $this->warn("Only {$availableCount} quotes available matching criteria. Will use all available.");
            $count = $availableCount;
        }

        // Get random quotes
        $quotes = $query->inRandomOrder()->limit($count)->get();

        $this->info("Found {$count} quotes to convert{$dateFilterInfo}{$policyDateInfo}" . ($dryRun ? " (dry run)" : ""));

        $results = [];
        $successCount = 0;
        $failCount = 0;

        // Process each quote
        foreach ($quotes as $quote) {
            $this->output->write("Converting Quote #{$quote->id}... ");

            if ($dryRun) {
                $this->info("[DRY RUN - Would convert]");
                $results[] = [
                    $quote->id, 
                    'N/A (dry run)', 
                    $quote->policy_types ? implode(', ', $quote->policy_types) : 'Unknown',
                    $quote->contact->full_name ?? 'Unknown',
                    '$' . number_format($quote->premium_amount ?? 0, 2)
                ];
                $successCount++;
                continue;
            }

            try {
                // Prepare date options if policy dates are specified
                $dateOptions = null;
                
                if ($policyDateRangeBounds) {
                    if ($randomDates) {
                        // Use a random date within the range
                        $start = $policyDateRangeBounds['start']->timestamp;
                        $end = $policyDateRangeBounds['end']->timestamp;
                        $randomTimestamp = mt_rand($start, $end);
                        $date = Carbon::createFromTimestamp($randomTimestamp);
                    } else {
                        // Evenly distribute based on position in the results
                        $totalQuotes = count($quotes);
                        $currentIndex = array_search($quote, $quotes->all());
                        
                        if ($totalQuotes > 1) {
                            $start = $policyDateRangeBounds['start']->timestamp;
                            $end = $policyDateRangeBounds['end']->timestamp;
                            $interval = ($end - $start) / ($totalQuotes - 1);
                            $timestamp = $start + ($interval * $currentIndex);
                            $date = Carbon::createFromTimestamp($timestamp);
                        } else {
                            // Only one quote, use middle of range
                            $date = $policyDateRangeBounds['start']->clone()->addSeconds(
                                ($policyDateRangeBounds['end']->timestamp - $policyDateRangeBounds['start']->timestamp) / 2
                            );
                        }
                    }
                    
                    $dateOptions = ['date' => $date];
                }
                
                $policy = $conversionService->convertQuoteToPolicy($quote, null, $dateOptions);
                $this->info("✅ Quote converted successfully to policy #{$policy->id}" . 
                    (isset($dateOptions['date']) ? " with date {$dateOptions['date']->format('Y-m-d')}" : ''));
                
                // Auto-complete the policy if requested
                if ($autoComplete) {
                    try {
                        $this->output->write("Auto-completing policy #{$policy->id}... ");
                        $policy = $autoCompleteService->completePolicy($policy);
                        $this->info("✅ Completed!");
                        $results[] = [
                            $quote->id, 
                            $policy->id, 
                            $policy->policy_type->value,
                            $quote->contact ? $quote->contact->full_name : 'Unknown',
                            '$' . number_format($policy->premium_amount ?? 0, 2),
                            '✓ Auto-completed'
                        ];
                    } catch (\Exception $e) {
                        $this->error("❌ Auto-completion failed: {$e->getMessage()}");
                        $results[] = [
                            $quote->id, 
                            $policy->id, 
                            $policy->policy_type->value ?? 'Unknown',
                            $quote->contact ? $quote->contact->full_name : 'Unknown',
                            '$' . number_format($policy->premium_amount ?? 0, 2),
                            '✗ Failed to auto-complete'
                        ];
                    }
                } else {
                    $results[] = [
                        $quote->id, 
                        $policy->id, 
                        $policy->policy_type ? $policy->policy_type->value : 'Unknown',
                        $quote->contact ? $quote->contact->full_name : 'Unknown',
                        '$' . number_format($policy->premium_amount ?? 0, 2),
                        ''
                    ];
                }
                
                $successCount++;
            } catch (\Exception $e) {
                $this->error("❌ Failed: {$e->getMessage()}");
                $failCount++;
            }
        }

        // Display summary table
        if (!empty($results)) {
            $this->info("\n📋 Conversion Summary:");
            $this->table(
                ['Quote ID', 'Policy ID', 'Policy Type', 'Contact', 'Premium', $autoComplete ? 'Auto-Complete Status' : ''],
                $results
            );
        }

        // Display summary counts
        $this->info("\n📊 Results Summary:");
        $this->info("  ✓ Successfully converted: {$successCount}");
        $this->info("  ✗ Failed conversions: {$failCount}");

        return ($failCount === 0) ? 0 : 1;
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
            try {
                $date = Carbon::createFromFormat('Y-m-d', $specificDate)->startOfDay();
                return [
                    'start' => $date->copy(),
                    'end' => $date->copy(),
                    'description' => ' from ' . $date->format('M j, Y')
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
                        'description' => ' from last week'
                    ];
                    
                case 'last_month':
                    return [
                        'start' => $now->copy()->subMonth()->startOfMonth(),
                        'end' => $now->copy()->subMonth()->endOfMonth(),
                        'description' => ' from last month'
                    ];
                    
                case 'this_month':
                    return [
                        'start' => $now->copy()->startOfMonth(),
                        'end' => $today,
                        'description' => ' from this month (until today)'
                    ];
                    
                case 'this_year':
                    return [
                        'start' => $now->copy()->startOfYear(),
                        'end' => $today,
                        'description' => ' from this year (until today)'
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
        
        // No date filtering
        return null;
    }
    
    /**
     * Gets a user-friendly description of the date range
     * 
     * @param array|null $dateRange Date range data from resolveDateRange
     * @return string Description of the date range
     */
    private function getDateRangeDescription($dateRange)
    {
        return $dateRange ? $dateRange['description'] : '';
    }
}
