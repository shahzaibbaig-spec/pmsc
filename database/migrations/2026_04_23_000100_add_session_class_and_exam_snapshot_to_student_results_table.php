<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const FALLBACK_SESSION = '2025-2026';

    public function up(): void
    {
        Schema::table('student_results', function (Blueprint $table): void {
            if (! Schema::hasColumn('student_results', 'session')) {
                $table->string('session', 20)->nullable()->after('student_id');
            }

            if (! Schema::hasColumn('student_results', 'class_id')) {
                $table->foreignId('class_id')
                    ->nullable()
                    ->after('session')
                    ->constrained('school_classes')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('student_results', 'exam_id')) {
                $table->foreignId('exam_id')
                    ->nullable()
                    ->after('class_id')
                    ->constrained('exams')
                    ->nullOnDelete();
            }
        });

        Schema::table('student_results', function (Blueprint $table): void {
            if (! $this->hasIndex('student_results', 'student_results_session_index')) {
                $table->index('session');
            }

            if (! $this->hasIndex('student_results', 'student_results_class_id_index')) {
                $table->index('class_id');
            }

            if (! $this->hasIndex('student_results', 'student_results_exam_id_index')) {
                $table->index('exam_id');
            }

            if (! $this->hasIndex('student_results', 'student_results_student_id_session_index')) {
                $table->index(['student_id', 'session']);
            }

            if (! $this->hasIndex('student_results', 'student_results_class_id_session_index')) {
                $table->index(['class_id', 'session']);
            }
        });

        $this->backfillSnapshots();
    }

    public function down(): void
    {
        Schema::table('student_results', function (Blueprint $table): void {
            if ($this->hasIndex('student_results', 'student_results_class_id_session_index')) {
                $table->dropIndex('student_results_class_id_session_index');
            }

            if ($this->hasIndex('student_results', 'student_results_student_id_session_index')) {
                $table->dropIndex('student_results_student_id_session_index');
            }

            if ($this->hasIndex('student_results', 'student_results_exam_id_index')) {
                $table->dropIndex('student_results_exam_id_index');
            }

            if ($this->hasIndex('student_results', 'student_results_class_id_index')) {
                $table->dropIndex('student_results_class_id_index');
            }

            if ($this->hasIndex('student_results', 'student_results_session_index')) {
                $table->dropIndex('student_results_session_index');
            }
        });

        Schema::table('student_results', function (Blueprint $table): void {
            if (Schema::hasColumn('student_results', 'exam_id')) {
                $table->dropConstrainedForeignId('exam_id');
            }

            if (Schema::hasColumn('student_results', 'class_id')) {
                $table->dropConstrainedForeignId('class_id');
            }

            if (Schema::hasColumn('student_results', 'session')) {
                $table->dropColumn('session');
            }
        });
    }

    private function backfillSnapshots(): void
    {
        DB::transaction(function (): void {
            DB::table('student_results')
                ->where(function ($query): void {
                    $query->whereNull('session')
                        ->orWhere('session', '');
                })
                ->update([
                    'session' => self::FALLBACK_SESSION,
                    'updated_at' => now(),
                ]);

            /** @var Collection<int, object> $results */
            $results = DB::table('student_results')
                ->select('id', 'student_id', 'subject_id', 'exam_name', 'result_date', 'session', 'class_id', 'exam_id')
                ->orderBy('id')
                ->get();

            if ($results->isEmpty()) {
                return;
            }

            $studentIds = $results
                ->pluck('student_id')
                ->filter()
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values();

            $studentClassMap = DB::table('students')
                ->whereIn('id', $studentIds)
                ->pluck('class_id', 'id');

            $classHistories = Schema::hasTable('student_class_histories')
                ? DB::table('student_class_histories')
                    ->whereIn('student_id', $studentIds)
                    ->orderBy('joined_on')
                    ->orderBy('id')
                    ->get(['student_id', 'class_id', 'session', 'joined_on', 'left_on'])
                    ->groupBy('student_id')
                : collect();

            foreach ($results as $result) {
                $resolvedSession = trim((string) ($result->session ?? '')) ?: self::FALLBACK_SESSION;
                $resolvedClassId = $result->class_id !== null
                    ? (int) $result->class_id
                    : $this->resolveClassIdForResult($result, $classHistories->get((int) $result->student_id, collect()), $studentClassMap);

                $updates = [];

                if (trim((string) ($result->session ?? '')) === '') {
                    $updates['session'] = $resolvedSession;
                }

                if ($result->class_id === null && $resolvedClassId !== null) {
                    $updates['class_id'] = $resolvedClassId;
                }

                if ($result->exam_id === null && $resolvedClassId !== null && Schema::hasTable('exams')) {
                    $resolvedExamId = $this->resolveExamIdForResult(
                        $resolvedClassId,
                        $resolvedSession,
                        (int) $result->subject_id,
                        is_string($result->exam_name) ? $result->exam_name : null
                    );

                    if ($resolvedExamId !== null) {
                        $updates['exam_id'] = $resolvedExamId;
                    }
                }

                if ($updates !== []) {
                    $updates['updated_at'] = now();

                    DB::table('student_results')
                        ->where('id', (int) $result->id)
                        ->update($updates);
                }
            }
        });
    }

    private function resolveClassIdForResult(object $result, Collection $histories, Collection $studentClassMap): ?int
    {
        $resolvedSession = trim((string) ($result->session ?? '')) ?: self::FALLBACK_SESSION;
        $resultDate = $this->parseDate($result->result_date);

        $exactSessionHistory = $histories
            ->first(fn (object $history): bool => (string) $history->session === $resolvedSession);

        if ($exactSessionHistory && $exactSessionHistory->class_id !== null) {
            return (int) $exactSessionHistory->class_id;
        }

        if ($resultDate !== null) {
            $datedHistory = $histories->first(function (object $history) use ($resultDate): bool {
                $joinedOn = $this->parseDate($history->joined_on);
                $leftOn = $this->parseDate($history->left_on);

                if ($joinedOn !== null && $resultDate->lt($joinedOn)) {
                    return false;
                }

                if ($leftOn !== null && $resultDate->gt($leftOn)) {
                    return false;
                }

                return $history->class_id !== null;
            });

            if ($datedHistory && $datedHistory->class_id !== null) {
                return (int) $datedHistory->class_id;
            }
        }

        $latestHistory = $histories->last();
        if ($latestHistory && $latestHistory->class_id !== null) {
            return (int) $latestHistory->class_id;
        }

        $currentClassId = $studentClassMap->get((int) $result->student_id);

        return $currentClassId !== null ? (int) $currentClassId : null;
    }

    private function resolveExamIdForResult(int $classId, string $session, int $subjectId, ?string $examName): ?int
    {
        $query = DB::table('exams')
            ->where('class_id', $classId)
            ->where('session', $session)
            ->where('subject_id', $subjectId);

        $normalizedExamName = $this->normalizeExamName($examName);

        if ($normalizedExamName !== null) {
            $query->where(function ($inner) use ($normalizedExamName): void {
                $inner->whereRaw('LOWER(REPLACE(exam_type, "_", " ")) = ?', [$normalizedExamName])
                    ->orWhereRaw('LOWER(exam_type) = ?', [str_replace(' ', '_', $normalizedExamName)]);
            });
        }

        $examId = $query
            ->orderByDesc('id')
            ->value('id');

        return $examId !== null ? (int) $examId : null;
    }

    private function normalizeExamName(?string $examName): ?string
    {
        $normalized = strtolower(trim((string) $examName));

        return $normalized !== '' ? str_replace(['-', '_'], ' ', $normalized) : null;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        return match ($driver) {
            'sqlite' => collect($connection->select("PRAGMA index_list('{$table}')"))
                ->contains(fn (object $row): bool => (string) ($row->name ?? '') === $index),
            'mysql', 'mariadb' => $connection->select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]) !== [],
            'pgsql' => $connection->select(
                'SELECT indexname FROM pg_indexes WHERE schemaname = current_schema() AND tablename = ? AND indexname = ?',
                [$table, $index]
            ) !== [],
            default => false,
        };
    }
};
