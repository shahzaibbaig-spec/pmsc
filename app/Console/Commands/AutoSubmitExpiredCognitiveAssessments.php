<?php

namespace App\Console\Commands;

use App\Services\CognitiveAssessmentService;
use Illuminate\Console\Command;

class AutoSubmitExpiredCognitiveAssessments extends Command
{
    protected $signature = 'cognitive-assessments:auto-submit-expired';

    protected $description = 'Auto-submit expired in-progress cognitive assessment attempts.';

    public function handle(CognitiveAssessmentService $service): int
    {
        $summary = $service->autoSubmitExpiredAttempts();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Expired Attempts Found', (string) ($summary['expired_attempts'] ?? 0)],
                ['Auto Submitted', (string) ($summary['auto_submitted'] ?? 0)],
            ]
        );

        return self::SUCCESS;
    }
}
