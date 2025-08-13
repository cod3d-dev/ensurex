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
                            {--issue_type= : Create issues of a specific type ID}
                            {--random : Attach issues to random policies}';


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
        $issueTypeId = $this->option('issue_type');
        $random = $this->option('random');

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



        // If random, attach to random policies
        if ($random) {
            $this->info("Creating {$count} issues for random policies...");
            
            // Get random policies
            $policies = Policy::inRandomOrder()->limit($count)->get();
            if ($policies->isEmpty()) {
                $this->warn("No policies found in the database.");
                return 1;
            } else {
                foreach ($policies as $policy) {
                    $this->createIssuesForPolicy(1, $policy, $issueTypeId);
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
