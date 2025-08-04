<?php

namespace App\Console\Commands;

use App\Models\Issue;
use App\Models\IssueType;
use App\Models\Policy;
use App\Models\Quote;
use Illuminate\Console\Command;

class CreateIssues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'factory:issues
                            {count=5 : Number of issues to create}
                            {--policy_id= : Attach issues to a specific policy ID}
                            {--quote_id= : Attach issues to a specific quote ID}
                            {--issue_type= : Create issues of a specific type ID}
                            {--random : Attach issues to random policies or quotes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create issues using the Issue factory';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->argument('count');
        $policyId = $this->option('policy_id');
        $quoteId = $this->option('quote_id');
        $issueTypeId = $this->option('issue_type');
        $random = $this->option('random');

        // Validate parameters
        if ($policyId && $quoteId) {
            $this->error('Cannot specify both policy_id and quote_id. Choose one.');
            return 1;
        }

        // Check if issue type exists if specified
        if ($issueTypeId) {
            $issueType = IssueType::find($issueTypeId);
            if (!$issueType) {
                $this->error("Issue type with ID {$issueTypeId} not found.");
                
                // Show available issue types
                $this->info("\nAvailable issue types:");
                $issueTypes = IssueType::all(['id', 'name']);
                $this->table(['ID', 'Name'], $issueTypes->toArray());
                return 1;
            }
        }

        // Check if we're attaching to a specific policy
        if ($policyId) {
            $policy = Policy::find($policyId);
            if (!$policy) {
                $this->error("Policy with ID {$policyId} not found.");
                return 1;
            }
            
            $this->createIssuesForPolicy($count, $policy, $issueTypeId);
            return 0;
        }

        // Check if we're attaching to a specific quote
        if ($quoteId) {
            $quote = Quote::find($quoteId);
            if (!$quote) {
                $this->error("Quote with ID {$quoteId} not found.");
                return 1;
            }
            
            $this->createIssuesForQuote($count, $quote, $issueTypeId);
            return 0;
        }

        // If random, attach to random policies or quotes
        if ($random) {
            // Split the count between policies and quotes
            $policyCount = (int)($count / 2);
            $quoteCount = $count - $policyCount;
            
            $this->info("Creating {$policyCount} issues for random policies and {$quoteCount} issues for random quotes...");
            
            // Get random policies
            $policies = Policy::inRandomOrder()->limit($policyCount)->get();
            if ($policies->isEmpty()) {
                $this->warn("No policies found in the database.");
                $policyCount = 0;
            } else {
                foreach ($policies as $policy) {
                    $this->createIssuesForPolicy(1, $policy, $issueTypeId);
                }
            }
            
            // Get random quotes
            $quotes = Quote::inRandomOrder()->limit($quoteCount)->get();
            if ($quotes->isEmpty()) {
                $this->warn("No quotes found in the database.");
            } else {
                foreach ($quotes as $quote) {
                    $this->createIssuesForQuote(1, $quote, $issueTypeId);
                }
            }
            
            return 0;
        }

        // If we got here, create standalone issues
        $this->info("Creating {$count} standalone issues...");
        
        $issues = Issue::factory($count)
            ->when($issueTypeId, function ($factory, $typeId) {
                return $factory->state(['issue_type_id' => $typeId]);
            })
            ->create();
            
        $this->info("✅ Created {$count} standalone issues.");
        
        // Show a summary of created issues
        $this->showIssueSummary($issues);
        
        return 0;
    }

    /**
     * Create issues for a specific policy
     */
    private function createIssuesForPolicy(int $count, Policy $policy, ?int $issueTypeId = null)
    {
        $this->info("Creating {$count} issues for Policy #{$policy->id}...");
        
        $issues = Issue::factory($count)
            ->for($policy)
            ->when($issueTypeId, function ($factory, $typeId) {
                return $factory->state(['issue_type_id' => $typeId]);
            })
            ->create();
            
        $this->info("✅ Created {$count} issues for Policy #{$policy->id}.");
        
        // Show a summary of created issues
        $this->showIssueSummary($issues);
    }

    /**
     * Create issues for a specific quote
     */
    private function createIssuesForQuote(int $count, Quote $quote, ?int $issueTypeId = null)
    {
        $this->info("Creating {$count} issues for Quote #{$quote->id}...");
        
        $issues = Issue::factory($count)
            ->for($quote)
            ->when($issueTypeId, function ($factory, $typeId) {
                return $factory->state(['issue_type_id' => $typeId]);
            })
            ->create();
            
        $this->info("✅ Created {$count} issues for Quote #{$quote->id}.");
        
        // Show a summary of created issues
        $this->showIssueSummary($issues);
    }

    /**
     * Show a summary of created issues
     */
    private function showIssueSummary($issues)
    {
        $rows = [];
        foreach ($issues as $issue) {
            $rows[] = [
                $issue->id,
                $issue->issue_type->name ?? 'Unknown',
                substr($issue->description, 0, 40) . (strlen($issue->description) > 40 ? '...' : ''),
                $issue->policy_id ?? 'N/A',
                $issue->quote_id ?? 'N/A',
            ];
        }
        
        if (!empty($rows)) {
            $this->table(
                ['ID', 'Type', 'Description', 'Policy ID', 'Quote ID'],
                $rows
            );
        }
    }
}
