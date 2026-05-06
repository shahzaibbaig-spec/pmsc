<?php

namespace App\Console\Commands;

use Database\Seeders\KcatQuestionBankSeeder;
use Illuminate\Console\Command;
use Throwable;

class GenerateKcatQuestionBank extends Command
{
    protected $signature = 'kcat:generate-question-bank {--dry-run : Generate and validate questions without inserting into database}';

    protected $description = 'Generate a large original KCAT question bank and insert it in batches.';

    public function handle(): int
    {
        /** @var KcatQuestionBankSeeder $seeder */
        $seeder = app(KcatQuestionBankSeeder::class);

        try {
            $this->info('Starting KCAT question bank generation...');

            $summary = $seeder->seedBank(
                function (string $message): void {
                    $this->line($message);
                },
                (bool) $this->option('dry-run')
            );

            $this->newLine();
            $this->info('Done.');

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Test ID', (string) ($summary['test_id'] ?? '-')],
                    ['Dry Run', ! empty($summary['dry_run']) ? 'Yes' : 'No'],
                    ['Inserted Questions', (string) ($summary['inserted_questions'] ?? 0)],
                    ['Inserted Options', (string) ($summary['inserted_options'] ?? 0)],
                ]
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('KCAT question bank generation failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
