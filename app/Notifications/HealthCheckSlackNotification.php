<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Spatie\Health\ResultStores\ResultStore;

class HealthCheckSlackNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ResultStore $resultStore
    ) {}

    public function via($notifiable): array
    {
        return ['slack'];
    }

    public function toSlack($notifiable): SlackMessage
    {
        $failedChecks = $this->resultStore->latestResults()?->failedChecks() ?? collect();

        $message = (new SlackMessage)
            ->error()
            ->content('🚨 Health checks failed in EnsureX!')
            ->attachment(function ($attachment) use ($failedChecks) {
                $attachment
                    ->title('Failed Health Checks')
                    ->fields([
                        'Failed Checks' => $failedChecks->count(),
                        'Environment' => config('app.env'),
                        'Application' => config('app.name'),
                    ]);

                foreach ($failedChecks as $check) {
                    $attachment->field(
                        $check->name,
                        $check->notificationMessage ?? $check->shortSummary ?? 'No details available',
                        true
                    );
                }
            });

        return $message;
    }
}
