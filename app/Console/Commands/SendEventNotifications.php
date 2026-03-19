<?php

namespace App\Console\Commands;

use App\Modules\Academic\Services\AcademicEventNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendEventNotifications extends Command
{
    protected $signature = 'academic-events:send-notifications {--date= : Override date (Y-m-d)}';

    protected $description = 'Send academic event reminders to teachers based on notify_days_before.';

    public function handle(AcademicEventNotificationService $service): int
    {
        $dateOption = trim((string) ($this->option('date') ?? ''));
        $targetDate = $dateOption !== ''
            ? Carbon::parse($dateOption)->startOfDay()
            : now()->startOfDay();

        $result = $service->sendScheduledReminders($targetDate);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Date', (string) ($result['date'] ?? $targetDate->toDateString())],
                ['Events Checked', (string) ($result['events_checked'] ?? 0)],
                ['Events Due', (string) ($result['events_due'] ?? 0)],
                ['Events Notified', (string) ($result['events_notified'] ?? 0)],
                ['Notifications Sent', (string) ($result['sent'] ?? 0)],
                ['Skipped', (string) ($result['skipped'] ?? 0)],
            ]
        );

        return self::SUCCESS;
    }
}

