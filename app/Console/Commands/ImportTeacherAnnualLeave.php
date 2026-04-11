<?php

namespace App\Console\Commands;

use App\Models\Teacher;
use App\Models\TeacherAcr;
use App\Models\TeacherAcrMetric;
use App\Modules\Timetable\Services\XlsxWorkbookReader;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportTeacherAnnualLeave extends Command
{
    protected $signature = 'teachers:import-annual-leave
        {file : Absolute path to the Excel (.xlsx) file}
        {--session= : Academic session in YYYY-YYYY format (defaults to current)}
        {--sheet=sheet1 : Sheet name (normalized) to import}
        {--working-days=220 : Working days used to convert leave into attendance percentage}
        {--include-finalized : Also update attendance score for finalized ACRs}
        {--store-only : Store leave data and attendance percentage only, without changing ACR scores}
        {--dry-run : Validate and preview matches without writing to database}';

    protected $description = 'Import annual teacher leave data, merge by teacher name, and update ACR attendance.';

    public function __construct(private readonly XlsxWorkbookReader $workbookReader)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! Schema::hasTable('teachers') || ! Schema::hasTable('teacher_acrs') || ! Schema::hasTable('teacher_acr_metrics')) {
            $this->error('Required tables are missing. Run ACR and teacher module migrations first.');

            return self::FAILURE;
        }

        $file = trim((string) $this->argument('file'));
        if ($file === '' || ! is_file($file)) {
            $this->error('Excel file not found: '.$file);

            return self::FAILURE;
        }

        $session = $this->resolveSession((string) $this->option('session'));
        $requestedSheet = $this->normalizeKey((string) $this->option('sheet'));
        $dryRun = (bool) $this->option('dry-run');
        $storeOnly = (bool) $this->option('store-only');
        $includeFinalized = (bool) $this->option('include-finalized');
        $workingDays = (float) $this->option('working-days');

        if ($workingDays <= 0) {
            $this->error('Working days must be greater than zero. Example: --working-days=220');

            return self::FAILURE;
        }

        $workbook = $this->workbookReader->read($file);
        if ($workbook === []) {
            $this->error('No worksheet data found in Excel file.');

            return self::FAILURE;
        }

        $sheetKey = $this->resolveSheetKey($requestedSheet, array_keys($workbook));
        if ($sheetKey === null) {
            $this->error('Requested sheet was not found. Available sheets: '.implode(', ', array_keys($workbook)));

            return self::FAILURE;
        }

        $rows = $workbook[$sheetKey] ?? [];
        if ($rows === []) {
            $this->warn('Selected sheet is empty. Nothing to import.');

            return self::SUCCESS;
        }

        /** @var Collection<int, Teacher> $teachers */
        $teachers = Teacher::query()
            ->with('user:id,name')
            ->orderBy('id')
            ->get(['id', 'teacher_id', 'employee_code', 'user_id']);

        if ($teachers->isEmpty()) {
            $this->error('No teachers found in database.');

            return self::FAILURE;
        }

        $prepared = [];
        $unmatched = [];
        $ambiguous = [];

        foreach ($rows as $index => $row) {
            $excelName = trim((string) ($row['teacher_name'] ?? ''));
            if ($excelName === '') {
                continue;
            }

            $match = $this->matchTeacher($excelName, $teachers);

            if (($match['status'] ?? '') === 'unmatched') {
                $unmatched[] = $excelName;
                continue;
            }

            if (($match['status'] ?? '') === 'ambiguous') {
                $ambiguous[] = [
                    'excel_name' => $excelName,
                    'candidates' => $match['candidates'] ?? [],
                ];
                continue;
            }

            /** @var Teacher $teacher */
            $teacher = $match['teacher'];
            $totalLeave = $this->toNumber($row['total'] ?? null);
            $monthly = $this->extractMonthlyValues($row);

            if ($totalLeave === null) {
                $totalLeave = $monthly !== [] ? round(array_sum($monthly), 2) : 0.0;
            }

            $attendancePercentage = $this->attendancePercentageFromLeave($totalLeave, $workingDays);
            $attendanceScore = $this->attendanceScoreFromPercentage($attendancePercentage);

            $prepared[] = [
                'row_no' => $index + 2,
                'teacher' => $teacher,
                'excel_name' => $excelName,
                'match_type' => $match['status'],
                'total_leave' => $totalLeave,
                'attendance_percentage' => $attendancePercentage,
                'attendance_score' => $attendanceScore,
                'monthly' => $monthly,
            ];
        }

        $this->line('Session: <info>'.$session.'</info>');
        $this->line('Sheet: <info>'.$sheetKey.'</info>');
        $this->line('Working days: <info>'.number_format($workingDays, 2).'</info>');
        $this->line('Store only (no ACR score updates): <info>'.($storeOnly ? 'yes' : 'no').'</info>');
        $this->line('Include finalized ACRs: <info>'.($includeFinalized ? 'yes' : 'no').'</info>');
        $this->line('Rows prepared: <info>'.count($prepared).'</info>');
        $this->line('Rows unmatched: <comment>'.count($unmatched).'</comment>');
        $this->line('Rows ambiguous: <comment>'.count($ambiguous).'</comment>');

        if ($unmatched !== []) {
            $this->warn('Unmatched names:');
            foreach (array_slice($unmatched, 0, 20) as $name) {
                $this->line(' - '.$name);
            }
        }

        if ($ambiguous !== []) {
            $this->warn('Ambiguous matches:');
            foreach (array_slice($ambiguous, 0, 20) as $entry) {
                $this->line(' - '.$entry['excel_name'].' => '.implode(', ', $entry['candidates']));
            }
        }

        if ($prepared === []) {
            $this->warn('No importable rows found after matching.');

            return self::SUCCESS;
        }

        $previewRows = collect($prepared)
            ->take(20)
            ->map(function (array $row): array {
                /** @var Teacher $teacher */
                $teacher = $row['teacher'];

                return [
                    'Excel Name' => $row['excel_name'],
                    'Matched Teacher' => (string) ($teacher->user?->name ?? ('Teacher '.$teacher->teacher_id)),
                    'Teacher Code' => (string) $teacher->teacher_id,
                    'Total Leave' => number_format((float) $row['total_leave'], 2),
                    'Attendance %' => number_format((float) $row['attendance_percentage'], 2),
                    'ACR Attendance Score' => number_format((float) $row['attendance_score'], 2),
                    'Match' => (string) $row['match_type'],
                ];
            })
            ->all();

        $this->table(
            ['Excel Name', 'Matched Teacher', 'Teacher Code', 'Total Leave', 'Attendance %', 'ACR Attendance Score', 'Match'],
            $previewRows
        );

        if ($dryRun) {
            $this->info('Dry run completed. No database changes were made.');

            return self::SUCCESS;
        }

        $now = now();
        $sourceFile = basename($file);
        $stats = [
            'rows_written' => 0,
            'acrs_created' => 0,
            'metrics_created' => 0,
            'acrs_updated' => 0,
            'finalized_skipped' => 0,
        ];

        DB::transaction(function () use ($prepared, $session, $sourceFile, $now, $storeOnly, $includeFinalized, $workingDays, &$stats): void {
            foreach ($prepared as $row) {
                /** @var Teacher $teacher */
                $teacher = $row['teacher'];

                $acr = TeacherAcr::query()->firstOrCreate(
                    [
                        'teacher_id' => (int) $teacher->id,
                        'session' => $session,
                    ],
                    [
                        'attendance_score' => 0,
                        'academic_score' => 0,
                        'improvement_score' => 0,
                        'conduct_score' => 0,
                        'pd_score' => 0,
                        'principal_score' => 0,
                        'total_score' => 0,
                        'status' => TeacherAcr::STATUS_DRAFT,
                    ]
                );
                if ($acr->wasRecentlyCreated) {
                    $stats['acrs_created']++;
                }

                $metric = TeacherAcrMetric::query()->firstOrCreate(
                    ['acr_id' => (int) $acr->id],
                    [
                        'trainings_attended' => 0,
                        'late_count' => 0,
                        'discipline_flags' => 0,
                        'meta' => [],
                    ]
                );
                if ($metric->wasRecentlyCreated) {
                    $stats['metrics_created']++;
                }

                $meta = is_array($metric->meta) ? $metric->meta : [];
                $meta['annual_leave_import'] = [
                    'source_file' => $sourceFile,
                    'excel_teacher_name' => (string) $row['excel_name'],
                    'total_leave_days' => round((float) $row['total_leave'], 2),
                    'working_days' => round($workingDays, 2),
                    'attendance_percentage' => round((float) $row['attendance_percentage'], 2),
                    'acr_attendance_score' => round((float) $row['attendance_score'], 2),
                    'monthly_leave_days' => $row['monthly'],
                    'session' => $session,
                    'imported_at' => $now->toDateTimeString(),
                ];

                $metric->attendance_percentage = (float) $row['attendance_percentage'];
                $metric->meta = $meta;
                $metric->save();

                $stats['rows_written']++;

                if ($storeOnly) {
                    continue;
                }

                if (! $includeFinalized && $acr->status === TeacherAcr::STATUS_FINALIZED) {
                    $stats['finalized_skipped']++;
                    continue;
                }

                $acr->attendance_score = (float) $row['attendance_score'];
                $acr->total_score = $this->recalculateAcrTotal($acr);
                $acr->final_grade = $this->gradeForScore((float) $acr->total_score);
                $acr->save();

                $stats['acrs_updated']++;
            }
        });

        $this->table(
            ['Metric', 'Value'],
            [
                ['Rows written', (string) $stats['rows_written']],
                ['ACRs created', (string) $stats['acrs_created']],
                ['Metrics created', (string) $stats['metrics_created']],
                ['ACRs attendance updated', (string) $stats['acrs_updated']],
                ['Finalized ACRs skipped', (string) $stats['finalized_skipped']],
            ]
        );

        $this->info('Import completed successfully.');

        return self::SUCCESS;
    }

    private function resolveSession(string $candidate): string
    {
        $candidate = trim($candidate);
        if (preg_match('/^(\d{4})-(\d{4})$/', $candidate, $matches) === 1 && ((int) $matches[2] === (int) $matches[1] + 1)) {
            return $candidate;
        }

        $now = now();
        $startYear = $now->month >= 7 ? $now->year : ($now->year - 1);

        return $startYear.'-'.($startYear + 1);
    }

    /**
     * @param array<int, string> $sheetKeys
     */
    private function resolveSheetKey(string $requestedSheet, array $sheetKeys): ?string
    {
        if ($requestedSheet === '') {
            return $sheetKeys[0] ?? null;
        }

        foreach ($sheetKeys as $key) {
            if ($this->normalizeKey($key) === $requestedSheet) {
                return $key;
            }
        }

        return null;
    }

    private function normalizeKey(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/i', '_', $value) ?? '';

        return trim($value, '_');
    }

    /**
     * @param Collection<int, Teacher> $teachers
     * @return array{
     *   status:string,
     *   teacher?:Teacher,
     *   candidates?:array<int, string>
     * }
     */
    private function matchTeacher(string $excelName, Collection $teachers): array
    {
        $needle = $this->normalizeTeacherName($excelName);
        if ($needle === '') {
            return ['status' => 'unmatched'];
        }

        $exact = $teachers
            ->filter(fn (Teacher $teacher): bool => $this->normalizeTeacherName((string) ($teacher->user?->name ?? '')) === $needle)
            ->values();

        if ($exact->count() === 1) {
            return [
                'status' => 'exact',
                'teacher' => $exact->first(),
            ];
        }

        if ($exact->count() > 1) {
            return [
                'status' => 'ambiguous',
                'candidates' => $exact
                    ->map(fn (Teacher $teacher): string => (string) ($teacher->user?->name ?? ('Teacher '.$teacher->teacher_id)))
                    ->all(),
            ];
        }

        $tokens = collect(explode(' ', $needle))
            ->filter(fn (string $token): bool => strlen($token) >= 4)
            ->values()
            ->all();

        $fuzzy = $teachers
            ->filter(function (Teacher $teacher) use ($needle, $tokens): bool {
                $teacherName = $this->normalizeTeacherName((string) ($teacher->user?->name ?? ''));
                if ($teacherName === '') {
                    return false;
                }

                if (str_contains($teacherName, $needle) || str_contains($needle, $teacherName)) {
                    return true;
                }

                if ($tokens === []) {
                    return false;
                }

                foreach ($tokens as $token) {
                    if (! str_contains($teacherName, $token)) {
                        return false;
                    }
                }

                return true;
            })
            ->values();

        if ($fuzzy->count() === 1) {
            return [
                'status' => 'fuzzy',
                'teacher' => $fuzzy->first(),
            ];
        }

        if ($fuzzy->count() > 1) {
            return [
                'status' => 'ambiguous',
                'candidates' => $fuzzy
                    ->map(fn (Teacher $teacher): string => (string) ($teacher->user?->name ?? ('Teacher '.$teacher->teacher_id)))
                    ->all(),
            ];
        }

        $scored = $teachers
            ->map(function (Teacher $teacher) use ($needle): array {
                $candidate = $this->normalizeTeacherName((string) ($teacher->user?->name ?? ''));
                $score = $this->nameSimilarity($needle, $candidate);

                return [
                    'teacher' => $teacher,
                    'score' => $score,
                    'name' => (string) ($teacher->user?->name ?? ('Teacher '.$teacher->teacher_id)),
                ];
            })
            ->filter(fn (array $item): bool => (float) $item['score'] >= 75.0)
            ->sortByDesc('score')
            ->values();

        if ($scored->count() === 1) {
            return [
                'status' => 'fuzzy_score',
                'teacher' => $scored->first()['teacher'],
            ];
        }

        if ($scored->count() > 1) {
            $best = $scored->first();
            $second = $scored->get(1);

            if ($second !== null && ((float) $best['score'] - (float) $second['score']) >= 5.0) {
                return [
                    'status' => 'fuzzy_score',
                    'teacher' => $best['teacher'],
                ];
            }

            return [
                'status' => 'ambiguous',
                'candidates' => $scored
                    ->take(8)
                    ->map(fn (array $item): string => $item['name'].' ('.number_format((float) $item['score'], 1).'%)')
                    ->all(),
            ];
        }

        return ['status' => 'unmatched'];
    }

    private function normalizeTeacherName(string $value): string
    {
        $value = preg_replace('/\([^)]*\)/', '', $value) ?? $value;
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/i', ' ', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        return trim($value);
    }

    /**
     * @param array<string, string> $row
     * @return array<string, float>
     */
    private function extractMonthlyValues(array $row): array
    {
        $monthMap = [
            'april' => 'april',
            'may' => 'may',
            'june' => 'june',
            'july' => 'july',
            'august' => 'august',
            'september' => 'september',
            'october' => 'october',
            'november' => 'november',
            'december' => 'december',
            'january' => 'january',
            'february' => 'february',
            'fabruary' => 'february',
            'march' => 'march',
        ];

        $monthly = [];
        foreach ($monthMap as $key => $monthName) {
            if (! array_key_exists($key, $row)) {
                continue;
            }

            $value = $this->toNumber($row[$key] ?? null);
            if ($value === null) {
                continue;
            }

            $monthly[$monthName] = round($value, 2);
        }

        return $monthly;
    }

    private function toNumber(mixed $value): ?float
    {
        $raw = trim((string) $value);
        if ($raw === '' || $raw === '-' || str_contains($raw, '?')) {
            return null;
        }

        $clean = preg_replace('/[^0-9.\-]+/', '', $raw) ?? '';
        if ($clean === '' || ! is_numeric($clean)) {
            return null;
        }

        return (float) $clean;
    }

    private function attendancePercentageFromLeave(float $leaveDays, float $workingDays): float
    {
        $safeWorkingDays = max(1.0, $workingDays);
        $safeLeave = max(0.0, $leaveDays);
        $presentDays = max(0.0, $safeWorkingDays - $safeLeave);

        return round(($presentDays * 100.0) / $safeWorkingDays, 2);
    }

    private function attendanceScoreFromPercentage(float $attendancePercentage): float
    {
        $normalized = max(0.0, min(100.0, $attendancePercentage));

        return round(($normalized / 100.0) * 15.0, 2);
    }

    private function recalculateAcrTotal(TeacherAcr $acr): float
    {
        return round(
            (float) $acr->attendance_score
            + (float) $acr->academic_score
            + (float) $acr->improvement_score
            + (float) $acr->conduct_score
            + (float) $acr->pd_score
            + (float) $acr->principal_score,
            2
        );
    }

    private function gradeForScore(float $totalScore): string
    {
        return match (true) {
            $totalScore >= 90 => 'Outstanding',
            $totalScore >= 80 => 'Very Good',
            $totalScore >= 70 => 'Good',
            $totalScore >= 60 => 'Average',
            $totalScore >= 50 => 'Weak',
            default => 'Unsatisfactory',
        };
    }

    private function nameSimilarity(string $left, string $right): float
    {
        if ($left === '' || $right === '') {
            return 0.0;
        }

        similar_text($left, $right, $percent);

        return round((float) $percent, 2);
    }
}
