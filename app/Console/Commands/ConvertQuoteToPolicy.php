<?php

namespace App\Console\Commands;

use App\Models\Quote;
use App\Services\QuoteConversionService;
use App\Enums\QuoteStatus;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ConvertQuoteToPolicy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quotes:convert {id : The ID of the quote to convert} {--date= : The creation date for the policy (format: YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert a specific quote to a policy';

    /**
     * Execute the console command.
     */
    public function handle(QuoteConversionService $conversionService)
    {
        $quoteId = $this->argument('id');
        
        // Find the quote
        $quote = Quote::find($quoteId);
        
        if (!$quote) {
            $this->error("Quote with ID {$quoteId} not found.");
            return 1;
        }
        
        // Check if quote has already been converted
        if ($quote->status === QuoteStatus::Converted) {
            $this->warn("Quote with ID {$quoteId} has already been converted to policy ID: {$quote->policy_id}.");
            return 1;
        }
        
        $this->info("Converting quote ID: {$quoteId}...");
        
        try {
            // Check if a date was provided
            $dateOptions = null;
            if ($this->option('date')) {
                try {
                    $date = Carbon::parse($this->option('date'));
                    $dateOptions = ['date' => $date];
                } catch (\Exception $e) {
                    $this->error("Invalid date format. Please use YYYY-MM-DD.");
                    return 1;
                }
            }
            
            // Convert the quote to policy
            $policy = $conversionService->convertQuoteToPolicy($quote, null, $dateOptions);
            
            $this->info("✅ Successfully converted to policy ID: {$policy->id}");
            $this->table(
                ['Quote ID', 'Policy ID', 'Policy Type', 'Effective Date', 'Premium Amount'],
                [[$quote->id, $policy->id, $policy->policy_type->value, $policy->effective_date, '$' . number_format($policy->premium_amount, 2)]]
            );
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to convert quote: {$e->getMessage()}");
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
