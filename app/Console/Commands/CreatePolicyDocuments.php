<?php

namespace App\Console\Commands;

use App\Enums\DocumentStatus;
use App\Models\Policy;
use App\Models\PolicyDocument;
use Illuminate\Console\Command;

class CreatePolicyDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'factory:policy-documents
                            {count=5 : Number of documents to create}
                            {--policy_id= : Attach documents to a specific policy ID}
                            {--status= : Set document status (draft|pending|approved|rejected)}
                            {--expired : Create expired documents}
                            {--due_today : Create documents due today}
                            {--due_next_week : Create documents due next week}
                            {--random : Attach documents to random policies}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create policy documents using the PolicyDocument factory';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->argument('count');
        $policyId = $this->option('policy_id');
        $status = $this->option('status');
        $expired = $this->option('expired');
        $dueToday = $this->option('due_today');
        $dueNextWeek = $this->option('due_next_week');
        $random = $this->option('random');

        // Validate status if provided
        if ($status && !in_array(strtolower($status), ['draft', 'pending', 'approved', 'rejected'])) {
            $this->error("Invalid status: '{$status}'. Must be one of: draft, pending, approved, rejected");
            return 1;
        }

        // Check if we're attaching to a specific policy
        if ($policyId) {
            $policy = Policy::find($policyId);
            if (!$policy) {
                $this->error("Policy with ID {$policyId} not found.");
                return 1;
            }
            
            $this->createDocumentsForPolicy($count, $policy, $status, $expired, $dueToday, $dueNextWeek);
            return 0;
        }

        // If random, attach to random policies
        if ($random) {
            $this->info("Creating {$count} documents for random policies...");
            
            // Get random policies
            $policies = Policy::inRandomOrder()->limit($count)->get();
            if ($policies->isEmpty()) {
                $this->error("No policies found in the database. Create some policies first.");
                return 1;
            }
            
            foreach ($policies as $policy) {
                $this->createDocumentsForPolicy(1, $policy, $status, $expired, $dueToday, $dueNextWeek);
            }
            
            return 0;
        }

        // If we got here, create standalone documents
        $this->info("Creating {$count} standalone policy documents...");
        
        $factory = PolicyDocument::factory($count);
        
        // Apply document state based on options
        $factory = $this->applyDocumentState($factory, $status, $expired, $dueToday, $dueNextWeek);
        
        // Create the documents
        $documents = $factory->create();
            
        $this->info("✅ Created {$count} standalone policy documents.");
        
        // Show a summary of created documents
        $this->showDocumentSummary($documents);
        
        return 0;
    }

    /**
     * Create documents for a specific policy
     */
    private function createDocumentsForPolicy(int $count, Policy $policy, ?string $status, bool $expired, bool $dueToday, bool $dueNextWeek)
    {
        $this->info("Creating {$count} documents for Policy #{$policy->id}...");
        
        // Set up the factory for the policy
        $factory = PolicyDocument::factory($count)->for($policy);
        
        // Apply document state based on options
        $factory = $this->applyDocumentState($factory, $status, $expired, $dueToday, $dueNextWeek);
        
        // Create the documents
        $documents = $factory->create();
            
        $this->info("✅ Created {$count} documents for Policy #{$policy->id}.");
        
        // Show a summary of created documents
        $this->showDocumentSummary($documents);
    }

    /**
     * Apply document state based on options
     */
    private function applyDocumentState($factory, ?string $status, bool $expired, bool $dueToday, bool $dueNextWeek)
    {
        // Apply status if provided
        if ($status) {
            $statusEnum = match(strtolower($status)) {
                'draft' => DocumentStatus::Draft,
                'pending' => DocumentStatus::Pending,
                'approved' => DocumentStatus::Approved,
                'rejected' => DocumentStatus::Rejected,
                default => null,
            };
            
            if ($statusEnum) {
                $factory = $factory->state(['status' => $statusEnum]);
            }
        }
        
        // Apply due date options
        if ($expired) {
            $factory = $factory->expireLastWeek();
        } elseif ($dueToday) {
            $factory = $factory->expireToday();
        } elseif ($dueNextWeek) {
            $factory = $factory->expireNextWeek();
        }
        
        return $factory;
    }

    /**
     * Show a summary of created documents
     */
    private function showDocumentSummary($documents)
    {
        $rows = [];
        foreach ($documents as $document) {
            $status = $document->status ? $document->status->value : 'Unknown';
            
            $rows[] = [
                $document->id,
                $document->name ?? 'Untitled',
                $status,
                $document->due_date ? $document->due_date->format('Y-m-d') : 'N/A',
                $document->policy_id ?? 'N/A',
            ];
        }
        
        if (!empty($rows)) {
            $this->table(
                ['ID', 'Name', 'Status', 'Due Date', 'Policy ID'],
                $rows
            );
        }
    }
}
