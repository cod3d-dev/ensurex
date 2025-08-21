<?php

namespace App\HealthChecks;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

class FailingHealthCheck extends Check
{
    public function run(): Result
    {
        return Result::make()
            ->failed()
            ->shortSummary('This check is designed to fail for testing')
            ->notificationMessage('This is a test failure to verify notifications are working properly')
            ->meta([
                'test' => true,
                'purpose' => 'notification testing',
            ]);
    }
}
