<?php

namespace App\Console\Commands;

use App\Enums\PolicyStatus;
use App\Models\Policy;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ChangePolicyStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'policies:change-status 
                            {--id= : The ID of a specific policy to change}
                            {--count= : Number of policies to change in batch mode}
                            {--from= : Current status to filter policies by (comma-separated for multiple)}
                            {--to= : Target status to set (random if not provided)}
                            {--start-date= : Start date for filtering policies (format: YYYY-MM-DD)}
                            {--end-date= : End date for filtering policies (format: YYYY-MM-DD)}
                            {--activation-date= : Specific activation date for active policies (format: YYYY-MM-DD)}
                            {--activation-date-range= : Predefined date range for activation date (this_week|last_week|this_month|last_month|this_year)}
                            {--use-quote-date : Use the quote creation date as the activation date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change the status of a specific policy or a batch of policies';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Single policy mode
        if ($this->option('id')) {
            $this->changeSinglePolicyStatus($this->option('id'));
            return;
        }

        // Batch mode
        $this->changeBatchPolicyStatus();
    }

    /**
     * Change the status of a single policy
     */
    private function changeSinglePolicyStatus(int $policyId): void
    {
        $policy = Policy::find($policyId);
        
        if (!$policy) {
            $this->error("Policy with ID {$policyId} not found.");
            return;
        }
        
        // Get the current status (which might be a string value or an enum instance)
        $oldStatusValue = $policy->status instanceof PolicyStatus ? $policy->status->value : $policy->status;
        $newStatus = $this->getTargetStatus($policy->status);
        
        if (!$newStatus) {
            $this->error("Invalid status option provided.");
            return;
        }
        
        try {
            $updateData = ['status' => $newStatus->value];
            
            // Set activation date if changing to active status
            if ($newStatus === PolicyStatus::Active) {
                $activationDate = $this->determineActivationDate($policy);
                if ($activationDate) {
                    $updateData['activation_date'] = $activationDate;
                    $this->info("Setting activation date to: {$activationDate->format('Y-m-d')}");
                }
            }
            
            $policy->update($updateData);
            $this->info("✅ Policy ID {$policyId} status changed: {$oldStatusValue} → {$newStatus->value}");
        } catch (\Exception $e) {
            $this->error("Failed to change policy status: " . $e->getMessage());
        }
    }

    /**
     * Change status for a batch of policies
     */
    private function changeBatchPolicyStatus(): void
    {
        // Build the query based on filters
        $query = Policy::query();
        
        // Filter by current status if provided
        if ($this->option('from')) {
            $currentStatuses = explode(',', $this->option('from'));
            $query->where(function ($query) use ($currentStatuses) {
                foreach ($currentStatuses as $index => $status) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $query->$method('status', $status);
                }
            });
        }
        
        // Filter by date range if provided
        if ($this->option('start-date')) {
            try {
                $startDate = Carbon::parse($this->option('start-date'))->startOfDay();
                $query->where('created_at', '>=', $startDate);
            } catch (\Exception $e) {
                $this->error("Invalid start date format. Please use YYYY-MM-DD.");
                return;
            }
        }
        
        if ($this->option('end-date')) {
            try {
                $endDate = Carbon::parse($this->option('end-date'))->endOfDay();
                $query->where('created_at', '<=', $endDate);
            } catch (\Exception $e) {
                $this->error("Invalid end date format. Please use YYYY-MM-DD.");
                return;
            }
        }
        
        // Get total count of matching policies
        $totalCount = $query->count();
        
        if ($totalCount === 0) {
            $this->error("No policies match the specified criteria.");
            return;
        }
        
        // Determine how many policies to update
        $requestedCount = $this->option('count') ? (int) $this->option('count') : $totalCount;
        $actualCount = min($requestedCount, $totalCount);
        
        if ($this->option('count') && $actualCount < $requestedCount) {
            $this->warn("Only {$actualCount} policies match your criteria (you requested {$requestedCount}).");
        }
        
        // Get the target status
        $targetStatus = $this->option('to') ? PolicyStatus::from($this->option('to')) : null;
        
        // Confirm the batch update
        $fromStatusText = $this->option('from') ? "with status [{$this->option('from')}]" : "regardless of status";
        $toStatusText = $targetStatus ? "to '{$targetStatus->value}'" : "to random statuses";
        
        if (!$this->confirm("This will change {$actualCount} policies {$fromStatusText} {$toStatusText}. Continue?")) {
            $this->info("Operation cancelled.");
            return;
        }
        
        // Start a database transaction
        DB::beginTransaction();
        
        try {
            $updatedCount = 0;
            $this->output->progressStart($actualCount);
            
            // Process policies in chunks for better memory management
            $query->limit($actualCount)->each(function ($policy) use (&$updatedCount, $targetStatus) {
                $oldStatus = $policy->status;
                $newStatus = $targetStatus ?? $this->getRandomStatus($oldStatus);
                
                $updateData = ['status' => $newStatus];
                
                // Set activation date if changing to active status
                if ($newStatus === PolicyStatus::Active) {
                    $activationDate = $this->determineActivationDate($policy);
                    if ($activationDate) {
                        $updateData['activation_date'] = $activationDate;
                    }
                }
                
                $policy->update($updateData);
                $updatedCount++;
                $this->output->progressAdvance();
            });
            
            $this->output->progressFinish();
            DB::commit();
            
            $this->info("✅ Successfully updated status for {$updatedCount} policies.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed to update policy statuses: " . $e->getMessage());
        }
    }

    /**
     * Get a target status based on provided option or random if not specified
     */
    private function getTargetStatus($currentStatus): ?PolicyStatus
    {
        if ($this->option('to')) {
            try {
                return PolicyStatus::from($this->option('to'));
            } catch (\ValueError $e) {
                $this->error("Invalid status '{$this->option('to')}'. Available statuses: " . 
                    implode(', ', array_map(fn($case) => $case->value, PolicyStatus::cases())));
                return null;
            }
        }
        
        return $this->getRandomStatus($currentStatus);
    }

    /**
     * Get a random status different from the current one
     */
    private function getRandomStatus($currentStatus): PolicyStatus
    {
        $allStatuses = PolicyStatus::cases();
        $availableStatuses = array_filter($allStatuses, function ($status) use ($currentStatus) {
            return $status !== $currentStatus;
        });
        
        return $availableStatuses[array_rand($availableStatuses)];
    }
    
    /**
     * Determine the activation date based on command options
     * 
     * @param Policy $policy The policy being updated
     * @return Carbon|null The activation date or null if not applicable
     */
    private function determineActivationDate(Policy $policy): ?Carbon
    {
        // Only set activation date when changing to active status
        if ($this->option('to') !== PolicyStatus::Active->value) {
            return null;
        }
        
        // If specific activation date is provided, use it
        if ($this->option('activation-date')) {
            try {
                return Carbon::parse($this->option('activation-date'));
            } catch (\Exception $e) {
                $this->warn("Invalid activation date format. Using default.");
            }
        }
        
        // If using quote date is specified
        if ($this->option('use-quote-date') && $policy->quote_id) {
            $quote = $policy->quote;
            if ($quote && $quote->created_at) {
                return Carbon::parse($quote->created_at);
            }
        }
        
        // If a predefined date range is specified
        if ($this->option('activation-date-range')) {
            $now = Carbon::now();
            
            switch ($this->option('activation-date-range')) {
                case 'this_week':
                    return $now->copy()->startOfWeek()->addDays(rand(0, $now->dayOfWeek));
                    
                case 'last_week':
                    return $now->copy()->subWeek()->startOfWeek()
                        ->addDays(rand(0, 6));
                    
                case 'this_month':
                    $daysInMonth = $now->daysInMonth;
                    $maxDay = min($now->day, $daysInMonth);
                    return $now->copy()->startOfMonth()->addDays(rand(0, $maxDay - 1));
                    
                case 'last_month':
                    $lastMonth = $now->copy()->subMonth();
                    $daysInLastMonth = $lastMonth->daysInMonth;
                    return $lastMonth->startOfMonth()->addDays(rand(0, $daysInLastMonth - 1));
                    
                case 'this_year':
                    $currentDayOfYear = $now->dayOfYear;
                    return $now->copy()->startOfYear()->addDays(rand(0, $currentDayOfYear - 1));
                    
                default:
                    $this->warn("Invalid activation date range. Using policy creation date.");
            }
        }
        
        // Default: use policy creation date
        return Carbon::parse($policy->created_at);
    }
}
