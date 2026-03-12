<?php

namespace App\Console\Commands;

use App\Models\Subject;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class NormalizeFederalBoardSubjects extends Command
{
    protected $signature = 'subjects:normalize-federal-board {--dry-run : Show merge plan without changing data}';

    protected $description = 'Merge abbreviated/custom subjects into official Federal Board subjects and remove duplicates.';

    /**
     * @var array<int, string>
     */
    private const OFFICIAL_SUBJECTS = [
        'Accounting',
        'Arabic',
        'Biology',
        'Business Mathematics',
        'Chemistry',
        'Civics',
        'Computer Science',
        'Economics',
        'Education',
        'English',
        'General Mathematics',
        'General Science',
        'Geography',
        'History',
        'Islamiat',
        'Mathematics',
        'Nazra Quran',
        'Pakistan Studies',
        'Physics',
        'Principles of Accounting',
        'Principles of Commerce',
        'Principles of Economics',
        'Sociology',
        'Statistics',
        'Urdu',
    ];

    /**
     * @var array<string, string>
     */
    private const ALIAS_MAP = [
        'bio' => 'Biology',
        'businessmath' => 'Business Mathematics',
        'businessstatistics' => 'Statistics',
        'bmath' => 'Business Mathematics',
        'bstate' => 'Statistics',
        'computer' => 'Computer Science',
        'comp' => 'Computer Science',
        'gmath' => 'General Mathematics',
        'generalmath' => 'General Mathematics',
        'islamyat' => 'Islamiat',
        'islamiyat' => 'Islamiat',
        'islamicstudies' => 'Islamiat',
        'math' => 'Mathematics',
        'maths' => 'Mathematics',
        'nazra' => 'Nazra Quran',
        'quran' => 'Nazra Quran',
        'pakstudy' => 'Pakistan Studies',
        'pakstudies' => 'Pakistan Studies',
        'trading' => 'Principles of Commerce',
        'banking' => 'Principles of Commerce',
        'civis' => 'Civics',
    ];

    public function handle(): int
    {
        $subjects = Subject::query()
            ->orderBy('name')
            ->get(['id', 'name', 'is_default']);

        $subjectsByName = $subjects->keyBy('name');
        $missingOfficial = collect(self::OFFICIAL_SUBJECTS)
            ->filter(fn (string $name): bool => ! $subjectsByName->has($name))
            ->values();

        if ($missingOfficial->isNotEmpty()) {
            $this->error('Missing official subjects. Run FederalBoardSubjectSeeder first.');
            $this->line('Missing: '.$missingOfficial->implode(', '));

            return self::FAILURE;
        }

        $plan = [];
        $unmappedNonOfficial = [];

        foreach ($subjects as $subject) {
            $sourceName = (string) $subject->name;
            $canonical = $this->resolveCanonicalName($sourceName);

            if ($canonical === null) {
                if (! in_array($sourceName, self::OFFICIAL_SUBJECTS, true)) {
                    $unmappedNonOfficial[] = $sourceName;
                }
                continue;
            }

            if ($canonical === $sourceName) {
                continue;
            }

            /** @var Subject $target */
            $target = $subjectsByName[$canonical];
            if ((int) $target->id === (int) $subject->id) {
                continue;
            }

            $plan[] = [
                'source_id' => (int) $subject->id,
                'source_name' => $sourceName,
                'target_id' => (int) $target->id,
                'target_name' => $canonical,
            ];
        }

        if (empty($plan) && empty($unmappedNonOfficial)) {
            $this->info('No abbreviated duplicate subjects found.');

            return self::SUCCESS;
        }

        if (! empty($plan)) {
            $this->table(
                ['Source ID', 'Source', 'Target ID', 'Target'],
                array_map(fn (array $row): array => [
                    (string) $row['source_id'],
                    $row['source_name'],
                    (string) $row['target_id'],
                    $row['target_name'],
                ], $plan)
            );
        }

        if (! empty($unmappedNonOfficial)) {
            $this->warn('Found non-official subjects without alias mapping:');
            foreach (array_unique($unmappedNonOfficial) as $name) {
                $this->line(' - '.$name);
            }
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run complete. No data changed.');

            return self::SUCCESS;
        }

        $stats = [
            'subjects_merged' => 0,
            'subjects_deleted' => 0,
            'class_subject' => 0,
            'student_subject_assignments' => 0,
            'student_subjects' => 0,
            'student_subject' => 0,
            'teacher_subject_assignments' => 0,
            'subject_period_rules' => 0,
            'subject_group_subject' => 0,
            'teacher_assignments' => 0,
            'timetable_entries' => 0,
            'student_results' => 0,
            'exams_updated' => 0,
            'exams_merged' => 0,
            'marks_moved' => 0,
        ];

        DB::transaction(function () use ($plan, &$stats): void {
            foreach ($plan as $row) {
                $sourceId = (int) $row['source_id'];
                $targetId = (int) $row['target_id'];

                $this->mergeSubjectReferences($sourceId, $targetId, $stats);
                $stats['subjects_merged']++;
                $stats['subjects_deleted'] += (int) Subject::query()->whereKey($sourceId)->delete();
            }
        });

        $this->table(
            ['Metric', 'Value'],
            [
                ['Subjects merged', (string) $stats['subjects_merged']],
                ['Subjects deleted', (string) $stats['subjects_deleted']],
                ['class_subject rows moved', (string) $stats['class_subject']],
                ['student_subject_assignments rows moved', (string) $stats['student_subject_assignments']],
                ['student_subjects rows moved', (string) $stats['student_subjects']],
                ['student_subject rows moved', (string) $stats['student_subject']],
                ['teacher_subject_assignments rows moved', (string) $stats['teacher_subject_assignments']],
                ['subject_period_rules rows moved', (string) $stats['subject_period_rules']],
                ['subject_group_subject rows moved', (string) $stats['subject_group_subject']],
                ['teacher_assignments rows updated', (string) $stats['teacher_assignments']],
                ['timetable_entries rows updated', (string) $stats['timetable_entries']],
                ['student_results rows updated', (string) $stats['student_results']],
                ['exams updated', (string) $stats['exams_updated']],
                ['exams merged', (string) $stats['exams_merged']],
                ['marks moved', (string) $stats['marks_moved']],
            ]
        );

        $remaining = Subject::query()
            ->whereNotIn('name', self::OFFICIAL_SUBJECTS)
            ->orderBy('name')
            ->pluck('name')
            ->all();

        if (! empty($remaining)) {
            $this->warn('Remaining non-official subjects: '.implode(', ', $remaining));
        } else {
            $this->info('Subject normalization complete. Only official Federal Board subjects remain.');
        }

        return self::SUCCESS;
    }

    /**
     * @param array<string, int> $stats
     */
    private function mergeSubjectReferences(int $sourceId, int $targetId, array &$stats): void
    {
        if ($sourceId === $targetId) {
            return;
        }

        $stats['class_subject'] += $this->copyRowsToTargetSubject(
            'class_subject',
            $sourceId,
            $targetId,
            ['class_id', 'created_at', 'updated_at']
        );

        $stats['student_subject_assignments'] += $this->copyRowsToTargetSubject(
            'student_subject_assignments',
            $sourceId,
            $targetId,
            ['session', 'student_id', 'class_id', 'subject_group_id', 'assigned_by', 'created_at', 'updated_at']
        );

        $stats['student_subjects'] += $this->copyRowsToTargetSubject(
            'student_subjects',
            $sourceId,
            $targetId,
            ['student_id', 'session', 'created_at', 'updated_at']
        );

        $stats['student_subject'] += $this->copyRowsToTargetSubject(
            'student_subject',
            $sourceId,
            $targetId,
            ['student_id', 'created_at', 'updated_at']
        );

        $stats['teacher_subject_assignments'] += $this->copyRowsToTargetSubject(
            'teacher_subject_assignments',
            $sourceId,
            $targetId,
            ['session', 'class_id', 'teacher_id', 'group_name', 'class_section_id', 'lessons_per_week', 'created_at', 'updated_at']
        );

        $stats['subject_period_rules'] += $this->copyRowsToTargetSubject(
            'subject_period_rules',
            $sourceId,
            $targetId,
            ['session', 'class_section_id', 'periods_per_week', 'created_at', 'updated_at']
        );

        $stats['subject_group_subject'] += $this->copyRowsToTargetSubject(
            'subject_group_subject',
            $sourceId,
            $targetId,
            ['subject_group_id', 'created_at', 'updated_at']
        );

        $stats['teacher_assignments'] += $this->updateSubjectIdInTable('teacher_assignments', $sourceId, $targetId);
        $stats['timetable_entries'] += $this->updateSubjectIdInTable('timetable_entries', $sourceId, $targetId);
        $stats['student_results'] += $this->updateSubjectIdInTable('student_results', $sourceId, $targetId);

        $examStats = $this->mergeExams($sourceId, $targetId);
        $stats['exams_updated'] += $examStats['updated'];
        $stats['exams_merged'] += $examStats['merged'];
        $stats['marks_moved'] += $examStats['marks_moved'];
    }

    /**
     * @param array<int, string> $columns
     */
    private function copyRowsToTargetSubject(string $table, int $sourceId, int $targetId, array $columns): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'subject_id')) {
            return 0;
        }

        $rows = DB::table($table)
            ->where('subject_id', $sourceId)
            ->get($columns);

        if ($rows->isEmpty()) {
            return 0;
        }

        $payload = [];
        foreach ($rows as $row) {
            $record = ['subject_id' => $targetId];
            foreach ($columns as $column) {
                $record[$column] = $row->{$column};
            }
            $payload[] = $record;
        }

        DB::table($table)->insertOrIgnore($payload);
        DB::table($table)->where('subject_id', $sourceId)->delete();

        return $rows->count();
    }

    private function updateSubjectIdInTable(string $table, int $sourceId, int $targetId): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'subject_id')) {
            return 0;
        }

        $payload = ['subject_id' => $targetId];
        if (Schema::hasColumn($table, 'updated_at')) {
            $payload['updated_at'] = now();
        }

        return DB::table($table)
            ->where('subject_id', $sourceId)
            ->update($payload);
    }

    /**
     * @return array{updated:int,merged:int,marks_moved:int}
     */
    private function mergeExams(int $sourceId, int $targetId): array
    {
        if (! Schema::hasTable('exams') || ! Schema::hasColumn('exams', 'subject_id')) {
            return ['updated' => 0, 'merged' => 0, 'marks_moved' => 0];
        }

        $examRows = DB::table('exams')
            ->where('subject_id', $sourceId)
            ->get(['id', 'class_id', 'subject_id', 'exam_type', 'session', 'teacher_id', 'total_marks', 'updated_at']);

        $updated = 0;
        $merged = 0;
        $marksMoved = 0;
        $hasMarksTable = Schema::hasTable('marks');

        foreach ($examRows as $exam) {
            $targetExam = DB::table('exams')
                ->where('class_id', (int) $exam->class_id)
                ->where('subject_id', $targetId)
                ->where('exam_type', (string) $exam->exam_type)
                ->where('session', (string) $exam->session)
                ->first(['id']);

            if (! $targetExam) {
                $payload = ['subject_id' => $targetId];
                if (Schema::hasColumn('exams', 'updated_at')) {
                    $payload['updated_at'] = now();
                }

                DB::table('exams')
                    ->where('id', (int) $exam->id)
                    ->update($payload);

                $updated++;
                continue;
            }

            if ($hasMarksTable) {
                $marks = DB::table('marks')
                    ->where('exam_id', (int) $exam->id)
                    ->get(['student_id', 'obtained_marks', 'total_marks', 'teacher_id', 'session', 'created_at', 'updated_at']);

                if ($marks->isNotEmpty()) {
                    $payload = $marks->map(fn ($mark): array => [
                        'exam_id' => (int) $targetExam->id,
                        'student_id' => (int) $mark->student_id,
                        'obtained_marks' => (int) $mark->obtained_marks,
                        'total_marks' => (int) $mark->total_marks,
                        'teacher_id' => (int) $mark->teacher_id,
                        'session' => (string) $mark->session,
                        'created_at' => $mark->created_at,
                        'updated_at' => now(),
                    ])->all();

                    DB::table('marks')->upsert(
                        $payload,
                        ['exam_id', 'student_id'],
                        ['obtained_marks', 'total_marks', 'teacher_id', 'session', 'updated_at']
                    );

                    $marksMoved += count($payload);
                }
            }

            DB::table('exams')->where('id', (int) $exam->id)->delete();
            $merged++;
        }

        return [
            'updated' => $updated,
            'merged' => $merged,
            'marks_moved' => $marksMoved,
        ];
    }

    private function resolveCanonicalName(string $name): ?string
    {
        $cleanName = trim($name);
        if ($cleanName === '') {
            return null;
        }

        if (in_array($cleanName, self::OFFICIAL_SUBJECTS, true)) {
            return $cleanName;
        }

        $key = $this->normalizeKey($cleanName);

        return self::ALIAS_MAP[$key] ?? null;
    }

    private function normalizeKey(string $value): string
    {
        $compact = preg_replace('/[^a-z0-9]+/', '', Str::lower($value)) ?? '';

        return trim($compact);
    }
}

