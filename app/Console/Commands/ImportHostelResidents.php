<?php

namespace App\Console\Commands;

use App\Models\Hostel;
use App\Services\HostelResidentService;
use Illuminate\Console\Command;

class ImportHostelResidents extends Command
{
    protected $signature = 'hostel:import-residents {file : Private path to the remaining-boys CSV} {--hostel= : Required hostel ID} {--dry-run : Validate and count without saving}';

    protected $description = 'Import boys into the separate hostel resident register without creating school students';

    public function handle(HostelResidentService $service): int
    {
        $hostel = Hostel::query()->find((int) $this->option('hostel'));
        if (! $hostel) {
            $this->error('Supply a valid --hostel ID.');

            return self::FAILURE;
        }
        try {
            $rows = $service->readCsv($this->argument('file'));
            if ($this->option('dry-run')) {
                $this->info(count($rows).' resident entries validated for '.$hostel->name.'. No records written.');

                return self::SUCCESS;
            }
            $result = $service->import($rows, $hostel);
            $this->info("Hostel residents: {$result['created']} added, {$result['unchanged']} already imported. School students unchanged.");

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
