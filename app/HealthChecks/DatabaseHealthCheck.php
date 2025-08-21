<?php

namespace App\HealthChecks;

use Illuminate\Support\Facades\DB;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

class DatabaseHealthCheck extends Check
{
    public function run(): Result
    {
        // Temporarily force this check to fail for testing
        return Result::make()
            ->failed()
            ->shortSummary('Database connection failed (test mode)')
            ->notificationMessage('Database connection test failure for notification testing')
            ->meta([
                'test_mode' => true,
                'connection' => DB::connection()->getName(),
            ]);

        // Original code (commented out for testing):
        /*
        try {
            DB::connection()->getPdo();

            return Result::make()
                ->ok()
                ->shortSummary('Database connection is healthy')
                ->meta([
                    'connection' => DB::connection()->getName(),
                    'database' => DB::connection()->getDatabaseName(),
                ]);
        } catch (\Exception $e) {
            return Result::make()
                ->failed()
                ->shortSummary('Database connection failed')
                ->notificationMessage('Database connection is not working: ' . $e->getMessage())
                ->meta([
                    'error' => $e->getMessage(),
                ]);
        }
        */
    }
}
