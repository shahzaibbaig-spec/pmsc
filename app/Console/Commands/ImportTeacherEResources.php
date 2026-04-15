<?php

namespace App\Console\Commands;

use App\Services\TeacherEResourceService;
use Illuminate\Console\Command;

class ImportTeacherEResources extends Command
{
    protected $signature = 'teacher-resources:import
        {zip* : Absolute path(s) of ZIP files to import}
        {--fresh : Remove existing imported resources before importing}';

    protected $description = 'Import teaching e-resources from one or more ZIP bundles (supports nested ZIPs).';

    public function __construct(private readonly TeacherEResourceService $resourceService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $zipPaths = collect((array) $this->argument('zip'))
            ->map(static fn ($path): string => trim((string) $path))
            ->filter(static fn (string $path): bool => $path !== '')
            ->values()
            ->all();

        if ($zipPaths === []) {
            $this->error('Please provide at least one ZIP file path.');

            return self::FAILURE;
        }

        $this->info('Import started. This may take some time depending on ZIP size...');

        $summary = $this->resourceService->importFromZipPaths(
            $zipPaths,
            (bool) $this->option('fresh')
        );

        $this->table(
            ['Metric', 'Value'],
            [
                ['Top-level ZIP files processed', (string) ($summary['zip_files_processed'] ?? 0)],
                ['Nested ZIP files processed', (string) ($summary['nested_zip_files_processed'] ?? 0)],
                ['Resource files extracted', (string) ($summary['resource_files_extracted'] ?? 0)],
                ['Skipped files', (string) ($summary['skipped_files'] ?? 0)],
                ['Errors', (string) count($summary['errors'] ?? [])],
            ]
        );

        $errors = (array) ($summary['errors'] ?? []);
        if ($errors !== []) {
            $this->warn('Some files were skipped due to errors:');
            foreach (array_slice($errors, 0, 20) as $error) {
                $this->line(' - '.$error);
            }
        }

        $this->info('Teacher E Resources import completed.');

        return self::SUCCESS;
    }
}

