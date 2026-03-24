<?php

namespace App\Console\Commands;

use App\Modules\Fees\Services\FeeDefaulterService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ProcessFeeDefaulters extends Command
{
    protected $signature = 'fees:process-defaulters {--session= : Academic session (e.g. 2025-2026)} {--date= : Override processing date (Y-m-d)}';

    protected $description = 'Mark and clear fee defaulters based on overdue challans, installment dues, and manual arrears.';

    public function handle(FeeDefaulterService $service): int
    {
        $dateOption = trim((string) ($this->option('date') ?? ''));
        $asOf = $dateOption !== ''
            ? Carbon::parse($dateOption)->startOfDay()
            : now()->startOfDay();

        $sessionOption = trim((string) ($this->option('session') ?? ''));
        $session = $sessionOption !== ''
            ? $sessionOption
            : $service->sessionFromDate($asOf);

        $summary = $service->processSession($session, $asOf);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Session', (string) ($summary['session'] ?? $session)],
                ['As Of', (string) ($summary['as_of'] ?? $asOf->toDateString())],
                ['Scanned', (string) ($summary['scanned'] ?? 0)],
                ['Active Defaulters', (string) ($summary['active'] ?? 0)],
                ['Marked', (string) ($summary['marked'] ?? 0)],
                ['Cleared', (string) ($summary['cleared'] ?? 0)],
                ['Updated', (string) ($summary['updated'] ?? 0)],
            ]
        );

        return self::SUCCESS;
    }
}
