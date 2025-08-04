<?php

namespace App\Console\Commands;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Services\PolicyAutoCompleteService;
use App\Services\QuoteConversionService;
use Illuminate\Console\Command;

class ConvertQuoteAndAutoComplete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quotes:convert-auto {quote_id : ID of the quote to convert}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert a quote to a policy and auto-complete it with random data';

    /**
     * Execute the console command.
     */
    public function handle(QuoteConversionService $conversionService, PolicyAutoCompleteService $autoCompleteService)
    {
        $quoteId = $this->argument('quote_id');
        
        // Find the quote
        $quote = Quote::find($quoteId);
        
        if (!$quote) {
            $this->error("Quote with ID {$quoteId} not found.");
            return 1;
        }
        
        // Check if already converted
        if ($quote->status === QuoteStatus::Converted) {
            $this->warn("Quote with ID {$quoteId} has already been converted to policy ID: {$quote->policy_id}.");
            
            // Ask if they want to continue with auto-complete on the existing policy
            if (!$this->confirm('Would you like to auto-complete the existing policy?')) {
                return 1;
            }
            
            $policy = $quote->policy;
        } else {
            // Convert the quote to a policy
            try {
                $this->info("Converting quote #{$quoteId} to a policy...");
                $policy = $conversionService->convertQuoteToPolicy($quote);
                $this->info("✅ Quote converted successfully to policy #{$policy->id}");
            } catch (\Exception $e) {
                $this->error("Failed to convert quote: " . $e->getMessage());
                return 1;
            }
        }
        
        // Auto-complete the policy with random data and finalize
        $this->info("Auto-completing policy with random data...");
        
        try {
            $policy = $autoCompleteService->completePolicy($policy);
            $this->info("✅ Policy #{$policy->id} auto-completed successfully!");
            
            // Make sure we have a fresh version of the policy with all relationships loaded
            $policy = \App\Models\Policy::with(['insuranceCompany', 'contact'])->find($policy->id);
            
            // Display the data that was filled in
            $this->info("\n📋 Policy Details:");
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $policy->id],
                    ['Code', $policy->code ?? 'N/A'],
                    ['Policy Type', $policy->policy_type ? $policy->policy_type->value : 'N/A'],
                    ['Status', $policy->status ? $policy->status->value : 'N/A'],
                    ['Insurance Company', $policy->insuranceCompany ? $policy->insuranceCompany->name : 'N/A'],
                    ['Inscription Type', $policy->policy_inscription_type ? $policy->policy_inscription_type->value : 'N/A'],
                    ['Plan', $policy->policy_plan ?? 'N/A'],
                    ['Premium Amount', '$' . number_format($policy->premium_amount ?? 0, 2)],
                    ['Contact', $policy->contact ? $policy->contact->full_name : 'N/A'],
                ]
            );
            
            // Show additional created policies if any
            // Using Policy::where() since Quote doesn't have a policies() relationship
            $additionalPolicies = \App\Models\Policy::where('quote_id', $quote->id)
                ->where('id', '!=', $policy->id)
                ->get();
            
            if ($additionalPolicies->count() > 0) {
                $this->info("\n📑 Additional Created Policies:");
                $rows = [];
                
                foreach ($additionalPolicies as $addPolicy) {
                    $rows[] = [
                        $addPolicy->id,
                        $addPolicy->code ?? 'N/A',
                        $addPolicy->policy_type ? $addPolicy->policy_type->value : 'N/A',
                        $addPolicy->status ? $addPolicy->status->value : 'N/A',
                        $addPolicy->policy_plan ?? 'N/A',
                    ];
                }
                
                $this->table(
                    ['ID', 'Code', 'Type', 'Status', 'Plan'],
                    $rows
                );
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to auto-complete policy: " . $e->getMessage());
            return 1;
        }
    }
}
