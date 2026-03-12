<?php

namespace App\Console\Commands;

use App\Modules\Fees\Services\FeeManagementService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ProcessLateFees extends Command
{
    protected $signature = 'fees:process-late-fees {--date= : Override processing date (Y-m-d)}';

    protected $description = 'Apply configured late fee to overdue unpaid/partial fee challans.';

    public function handle(FeeManagementService $feeManagementService): int
    {
        $dateOption = trim((string) ($this->option('date') ?? ''));
        $asOf = $dateOption !== ''
            ? Carbon::parse($dateOption)->startOfDay()
            : now()->startOfDay();

        $summary = $feeManagementService->processLateFees($asOf);

        $this->table(
            ['Metric', 'Value'],
            [
                ['As Of', (string) ($summary['as_of'] ?? $asOf->toDateString())],
                ['Late Fee Amount', number_format((float) ($summary['late_fee_amount'] ?? 0), 2)],
                ['Grace Days', (string) ($summary['grace_days'] ?? 0)],
                ['Scanned', (string) ($summary['scanned'] ?? 0)],
                ['Applied', (string) ($summary['applied'] ?? 0)],
                ['Skipped', (string) ($summary['skipped'] ?? 0)],
            ]
        );

        return self::SUCCESS;
    }
}

