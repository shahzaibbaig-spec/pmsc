<?php

namespace App\Services;

use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\TeacherResultEntryLog;
use App\Modules\Exams\Enums\ExamType;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TeacherResultEntryReportService
{
    /**
     * @var array<string, array<int, int>>
     */
    private array $eligibleStudentCache = [];

    public function __construct(
        private readonly TeacherStudentVisibilityService $visibilityService,
        private readonly TeacherRankingService $teacherRankingService,
    ) {
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function getTeacherEntrySummary(array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);
        $assignments = $this->assignmentRows($normalized);

        if ($assignments->isEmpty()) {
            return [];
        }

        $examTypes = $normalized['exam_type'] !== null
            ? [(string) $normalized['exam_type']]
            : array_column(ExamType::options(), 'value');
        $marksGrouped = $this->groupedMarksByScope($normalized, $assignments);

        $rows = [];
        foreach ($assignments as $assignment) {
            $eligibleStudentIds = $this->eligibleStudentIds(
                (int) $assignment['teacher_id'],
                (int) $assignment['class_id'],
                (int) $assignment['subject_id'],
                (string) $normalized['session']
            );
            $eligibleLookup = array_flip($eligibleStudentIds);
            $totalEligible = count($eligibleStudentIds);

            foreach ($examTypes as $examType) {
                $key = $this->scopeKey(
                    (int) $assignment['teacher_id'],
                    (int) $assignment['class_id'],
                    (int) $assignment['subject_id'],
                    $examType
                );
                $group = $marksGrouped[$key] ?? [
                    'student_ids' => [],
                    'latest_by_student' => [],
                ];

                $enteredStudentIds = array_values(array_filter(
                    $group['student_ids'],
                    static fn (int $studentId): bool => array_key_exists($studentId, $eligibleLookup)
                ));
                $enteredCount = count($enteredStudentIds);
                $pendingEntries = max(0, $totalEligible - $enteredCount);
                $completion = $totalEligible > 0
                    ? round(($enteredCount * 100.0) / $totalEligible, 2)
                    : 0.0;

                $lastUpdated = collect($group['latest_by_student'] ?? [])
                    ->only($enteredStudentIds)
                    ->filter()
                    ->max();

                $rows[] = [
                    'teacher_id' => (int) $assignment['teacher_id'],
                    'teacher_name' => (string) $assignment['teacher_name'],
                    'teacher_code' => (string) $assignment['teacher_code'],
                    'class_id' => (int) $assignment['class_id'],
                    'class_name' => (string) $assignment['class_name'],
                    'subject_id' => (int) $assignment['subject_id'],
                    'subject_name' => (string) $assignment['subject_name'],
                    'session' => (string) $normalized['session'],
                    'exam_type' => (string) $examType,
                    'exam_type_label' => $this->examTypeLabel((string) $examType),
                    'entered_student_count' => $enteredCount,
                    'total_eligible_student_count' => $totalEligible,
                    'pending_entries' => $pendingEntries,
                    'completion_percentage' => $completion,
                    'last_updated_at' => $lastUpdated instanceof Carbon
                        ? $lastUpdated
                        : ($lastUpdated ? Carbon::parse((string) $lastUpdated) : null),
                ];
            }
        }

        return collect($rows)
            ->sort(function (array $left, array $right): int {
                $teacherComparison = strcasecmp((string) $left['teacher_name'], (string) $right['teacher_name']);
                if ($teacherComparison !== 0) {
                    return $teacherComparison;
                }

                $classComparison = strnatcasecmp((string) $left['class_name'], (string) $right['class_name']);
                if ($classComparison !== 0) {
                    return $classComparison;
                }

                $subjectComparison = strcasecmp((string) $left['subject_name'], (string) $right['subject_name']);
                if ($subjectComparison !== 0) {
                    return $subjectComparison;
                }

                return strcasecmp((string) $left['exam_type'], (string) $right['exam_type']);
            })
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function getTeacherSubjectEntries(int $teacherId, array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);

        $query = Mark::query()
            ->with([
                'student:id,name,student_id',
                'exam:id,class_id,subject_id,exam_type,total_marks',
                'exam.classRoom:id,name,section',
                'exam.subject:id,name,code',
            ])
            ->where('teacher_id', $teacherId)
            ->where('session', (string) $normalized['session'])
            ->when(
                $normalized['exam_type'] !== null,
                fn ($builder) => $builder->whereHas('exam', fn ($examQuery) => $examQuery->where('exam_type', (string) $normalized['exam_type']))
            )
            ->when(
                $normalized['class_id'] !== null,
                fn ($builder) => $builder->whereHas('exam', fn ($examQuery) => $examQuery->where('class_id', (int) $normalized['class_id']))
            )
            ->when(
                $normalized['subject_id'] !== null,
                fn ($builder) => $builder->whereHas('exam', fn ($examQuery) => $examQuery->where('subject_id', (int) $normalized['subject_id']))
            )
            ->when(
                $normalized['date_from'] !== null,
                fn ($builder) => $builder->whereDate('updated_at', '>=', (string) $normalized['date_from'])
            )
            ->when(
                $normalized['date_to'] !== null,
                fn ($builder) => $builder->whereDate('updated_at', '<=', (string) $normalized['date_to'])
            )
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        $marks = $query->get();

        $items = $marks->map(function (Mark $mark): array {
            $classLabel = trim((string) ($mark->exam?->classRoom?->name ?? '').' '.(string) ($mark->exam?->classRoom?->section ?? ''));
            $subjectName = (string) ($mark->exam?->subject?->name ?? 'Subject');
            $examType = $mark->exam?->exam_type instanceof ExamType
                ? $mark->exam->exam_type->value
                : (string) ($mark->exam?->exam_type ?? '');

            return [
                'student_id' => (int) $mark->student_id,
                'student_name' => (string) ($mark->student?->name ?? 'Student'),
                'student_code' => (string) ($mark->student?->student_id ?? ''),
                'class_id' => (int) ($mark->exam?->class_id ?? 0),
                'class_name' => $classLabel !== '' ? $classLabel : 'Class',
                'subject_id' => (int) ($mark->exam?->subject_id ?? 0),
                'subject_name' => $subjectName,
                'exam_type' => $examType,
                'exam_type_label' => $this->examTypeLabel($examType),
                'obtained_marks' => $mark->obtained_marks !== null ? (float) $mark->obtained_marks : null,
                'grade' => $mark->grade,
                'created_at' => $mark->created_at,
                'updated_at' => $mark->updated_at,
            ];
        })->values();

        $groups = $items
            ->groupBy(fn (array $row): string => $row['class_id'].'|'.$row['subject_id'].'|'.$row['exam_type'])
            ->map(function (Collection $rows): array {
                $first = $rows->first();

                return [
                    'class_id' => (int) ($first['class_id'] ?? 0),
                    'class_name' => (string) ($first['class_name'] ?? 'Class'),
                    'subject_id' => (int) ($first['subject_id'] ?? 0),
                    'subject_name' => (string) ($first['subject_name'] ?? 'Subject'),
                    'exam_type' => (string) ($first['exam_type'] ?? ''),
                    'exam_type_label' => (string) ($first['exam_type_label'] ?? ''),
                    'entry_count' => $rows->count(),
                    'entries' => $rows->values()->all(),
                ];
            })
            ->sort(fn (array $left, array $right): int => strnatcasecmp($left['class_name'].' '.$left['subject_name'], $right['class_name'].' '.$right['subject_name']))
            ->values()
            ->all();

        return [
            'items' => $items->all(),
            'groups' => $groups,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function getTeacherEntryLogs(int $teacherId, array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);

        return TeacherResultEntryLog::query()
            ->with([
                'student:id,name,student_id',
                'classRoom:id,name,section',
                'subject:id,name',
                'actedBy:id,name',
            ])
            ->where('teacher_id', $teacherId)
            ->where('session', (string) $normalized['session'])
            ->when($normalized['exam_type'] !== null, fn ($builder) => $builder->where('exam_type', (string) $normalized['exam_type']))
            ->when($normalized['class_id'] !== null, fn ($builder) => $builder->where('class_id', (int) $normalized['class_id']))
            ->when($normalized['subject_id'] !== null, fn ($builder) => $builder->where('subject_id', (int) $normalized['subject_id']))
            ->when($normalized['date_from'] !== null, fn ($builder) => $builder->whereDate('action_at', '>=', (string) $normalized['date_from']))
            ->when($normalized['date_to'] !== null, fn ($builder) => $builder->whereDate('action_at', '<=', (string) $normalized['date_to']))
            ->orderByDesc('action_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (TeacherResultEntryLog $log): array {
                return [
                    'student_name' => (string) ($log->student?->name ?? 'Student'),
                    'student_code' => (string) ($log->student?->student_id ?? ''),
                    'class_name' => trim((string) ($log->classRoom?->name ?? '').' '.(string) ($log->classRoom?->section ?? '')),
                    'subject_name' => (string) ($log->subject?->name ?? 'Subject'),
                    'exam_type' => (string) $log->exam_type,
                    'exam_type_label' => $this->examTypeLabel((string) $log->exam_type),
                    'old_marks' => $log->old_marks,
                    'new_marks' => $log->new_marks,
                    'old_grade' => $log->old_grade,
                    'new_grade' => $log->new_grade,
                    'action_type' => (string) $log->action_type,
                    'action_at' => $log->action_at,
                    'acted_by' => (string) ($log->actedBy?->name ?? 'System'),
                    'remarks' => $log->remarks,
                ];
            })
            ->values()
            ->all();
    }

    public function getTeacherCompletionStatus(int $teacherId, string $session, string $examType): array
    {
        $summary = $this->getTeacherEntrySummary([
            'teacher_id' => $teacherId,
            'session' => $session,
            'exam_type' => $examType,
        ]);

        $totalEligible = (int) collect($summary)->sum('total_eligible_student_count');
        $entered = (int) collect($summary)->sum('entered_student_count');
        $pending = (int) collect($summary)->sum('pending_entries');

        return [
            'teacher_id' => $teacherId,
            'session' => $this->resolveSession($session),
            'exam_type' => $examType,
            'exam_type_label' => $this->examTypeLabel($examType),
            'entered_student_count' => $entered,
            'total_eligible_student_count' => $totalEligible,
            'pending_entries' => $pending,
            'completion_percentage' => $totalEligible > 0
                ? round(($entered * 100.0) / $totalEligible, 2)
                : 0.0,
            'is_complete' => $totalEligible > 0 && $pending === 0,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, int>
     */
    public function getTeacherEntryDashboardCards(array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);
        $summary = collect($this->getTeacherEntrySummary($normalized));

        if ($summary->isEmpty()) {
            return [
                'total_pending_entries' => 0,
                'completed_teachers' => 0,
                'incomplete_teachers' => 0,
                'latest_updates' => 0,
            ];
        }

        $teacherStatus = $summary
            ->groupBy('teacher_id')
            ->map(function (Collection $rows): array {
                $pending = (int) $rows->sum('pending_entries');
                $eligible = (int) $rows->sum('total_eligible_student_count');

                return [
                    'pending' => $pending,
                    'eligible' => $eligible,
                    'is_complete' => $eligible > 0 && $pending === 0,
                ];
            });

        $dateFrom = $normalized['date_from'];
        $dateTo = $normalized['date_to'];
        if ($dateFrom === null && $dateTo === null) {
            $dateFrom = now()->subDays(7)->toDateString();
            $dateTo = now()->toDateString();
        }

        $latestUpdates = TeacherResultEntryLog::query()
            ->where('session', (string) $normalized['session'])
            ->when($normalized['teacher_id'] !== null, fn ($builder) => $builder->where('teacher_id', (int) $normalized['teacher_id']))
            ->when($normalized['class_id'] !== null, fn ($builder) => $builder->where('class_id', (int) $normalized['class_id']))
            ->when($normalized['subject_id'] !== null, fn ($builder) => $builder->where('subject_id', (int) $normalized['subject_id']))
            ->when($normalized['exam_type'] !== null, fn ($builder) => $builder->where('exam_type', (string) $normalized['exam_type']))
            ->when($dateFrom !== null, fn ($builder) => $builder->whereDate('action_at', '>=', $dateFrom))
            ->when($dateTo !== null, fn ($builder) => $builder->whereDate('action_at', '<=', $dateTo))
            ->count();

        return [
            'total_pending_entries' => (int) $summary->sum('pending_entries'),
            'completed_teachers' => (int) $teacherStatus->filter(fn (array $row): bool => $row['is_complete'])->count(),
            'incomplete_teachers' => (int) $teacherStatus->filter(fn (array $row): bool => ! $row['is_complete'])->count(),
            'latest_updates' => (int) $latestUpdates,
        ];
    }

    /**
     * @return array<int, array{id:int,name:string,teacher_id:string}>
     */
    public function teacherOptions(string $session): array
    {
        return Teacher::query()
            ->with('user:id,name')
            ->whereHas('assignments', fn ($query) => $query->where('session', $this->resolveSession($session)))
            ->orderBy('teacher_id')
            ->get(['id', 'teacher_id', 'user_id'])
            ->map(fn (Teacher $teacher): array => [
                'id' => (int) $teacher->id,
                'name' => (string) ($teacher->user?->name ?? ('Teacher '.$teacher->teacher_id)),
                'teacher_id' => (string) $teacher->teacher_id,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    public function classOptions(string $session): array
    {
        $classIds = TeacherAssignment::query()
            ->where('session', $this->resolveSession($session))
            ->pluck('class_id')
            ->unique()
            ->values();

        return SchoolClass::query()
            ->whereIn('id', $classIds)
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section'])
            ->map(fn (SchoolClass $class): array => [
                'id' => (int) $class->id,
                'name' => trim((string) $class->name.' '.(string) ($class->section ?? '')),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    public function subjectOptions(string $session): array
    {
        $subjectIds = TeacherAssignment::query()
            ->where('session', $this->resolveSession($session))
            ->whereNotNull('subject_id')
            ->pluck('subject_id')
            ->unique()
            ->values();

        return Subject::query()
            ->whereIn('id', $subjectIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Subject $subject): array => [
                'id' => (int) $subject->id,
                'name' => (string) $subject->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function sessionOptions(): array
    {
        return $this->teacherRankingService->sessionOptions();
    }

    public function resolveSession(?string $session): string
    {
        return $this->teacherRankingService->resolveSession($session);
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    public function examTypeOptions(): array
    {
        return ExamType::options();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function normalizeFilters(array $filters): array
    {
        $session = $this->resolveSession(isset($filters['session']) ? (string) $filters['session'] : null);
        $examType = $this->teacherRankingService->normalizeExamType(isset($filters['exam_type']) ? (string) $filters['exam_type'] : null);

        return [
            'teacher_id' => isset($filters['teacher_id']) && $filters['teacher_id'] !== '' ? (int) $filters['teacher_id'] : null,
            'class_id' => isset($filters['class_id']) && $filters['class_id'] !== '' ? (int) $filters['class_id'] : null,
            'subject_id' => isset($filters['subject_id']) && $filters['subject_id'] !== '' ? (int) $filters['subject_id'] : null,
            'session' => $session,
            'exam_type' => $examType,
            'date_from' => $this->normalizeDateValue($filters['date_from'] ?? null),
            'date_to' => $this->normalizeDateValue($filters['date_to'] ?? null),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function assignmentRows(array $filters): Collection
    {
        return TeacherAssignment::query()
            ->with([
                'teacher:id,teacher_id,user_id',
                'teacher.user:id,name',
                'classRoom:id,name,section',
                'subject:id,name',
            ])
            ->where('session', (string) $filters['session'])
            ->whereNotNull('subject_id')
            ->when($filters['teacher_id'] !== null, fn ($builder) => $builder->where('teacher_id', (int) $filters['teacher_id']))
            ->when($filters['class_id'] !== null, fn ($builder) => $builder->where('class_id', (int) $filters['class_id']))
            ->when($filters['subject_id'] !== null, fn ($builder) => $builder->where('subject_id', (int) $filters['subject_id']))
            ->orderBy('teacher_id')
            ->orderBy('class_id')
            ->orderBy('subject_id')
            ->get()
            ->map(function (TeacherAssignment $assignment): array {
                return [
                    'teacher_id' => (int) $assignment->teacher_id,
                    'teacher_name' => (string) ($assignment->teacher?->user?->name ?? ('Teacher '.$assignment->teacher_id)),
                    'teacher_code' => (string) ($assignment->teacher?->teacher_id ?? ''),
                    'class_id' => (int) $assignment->class_id,
                    'class_name' => trim((string) ($assignment->classRoom?->name ?? '').' '.(string) ($assignment->classRoom?->section ?? '')),
                    'subject_id' => (int) $assignment->subject_id,
                    'subject_name' => (string) ($assignment->subject?->name ?? 'Subject'),
                ];
            })
            ->unique(fn (array $row): string => $row['teacher_id'].'|'.$row['class_id'].'|'.$row['subject_id'])
            ->values();
    }

    /**
     * @param array<string, mixed> $filters
     * @param Collection<int, array<string, mixed>> $assignments
     * @return array<string, array{student_ids:array<int, int>, latest_by_student:array<int, Carbon>}>
     */
    private function groupedMarksByScope(array $filters, Collection $assignments): array
    {
        $teacherIds = $assignments->pluck('teacher_id')->unique()->values()->all();
        $classIds = $assignments->pluck('class_id')->unique()->values()->all();
        $subjectIds = $assignments->pluck('subject_id')->unique()->values()->all();

        $marks = Mark::query()
            ->join('exams as e', 'e.id', '=', 'marks.exam_id')
            ->where('marks.session', (string) $filters['session'])
            ->whereIn('marks.teacher_id', $teacherIds)
            ->whereIn('e.class_id', $classIds)
            ->whereIn('e.subject_id', $subjectIds)
            ->when($filters['exam_type'] !== null, fn ($builder) => $builder->where('e.exam_type', (string) $filters['exam_type']))
            ->when($filters['date_from'] !== null, fn ($builder) => $builder->whereDate('marks.updated_at', '>=', (string) $filters['date_from']))
            ->when($filters['date_to'] !== null, fn ($builder) => $builder->whereDate('marks.updated_at', '<=', (string) $filters['date_to']))
            ->select([
                'marks.teacher_id',
                'e.class_id',
                'e.subject_id',
                'e.exam_type',
                'marks.student_id',
                'marks.updated_at',
            ])
            ->get();

        $groups = [];
        foreach ($marks as $row) {
            $key = $this->scopeKey((int) $row->teacher_id, (int) $row->class_id, (int) $row->subject_id, (string) $row->exam_type);
            $groups[$key] ??= [
                'student_ids' => [],
                'latest_by_student' => [],
            ];

            $studentId = (int) $row->student_id;
            $groups[$key]['student_ids'][$studentId] = $studentId;
            $updatedAt = Carbon::parse((string) $row->updated_at);

            if (
                ! isset($groups[$key]['latest_by_student'][$studentId])
                || $updatedAt->gt($groups[$key]['latest_by_student'][$studentId])
            ) {
                $groups[$key]['latest_by_student'][$studentId] = $updatedAt;
            }
        }

        return collect($groups)
            ->map(function (array $group): array {
                return [
                    'student_ids' => array_values($group['student_ids']),
                    'latest_by_student' => $group['latest_by_student'],
                ];
            })
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function eligibleStudentIds(int $teacherId, int $classId, int $subjectId, string $session): array
    {
        $cacheKey = $teacherId.'|'.$classId.'|'.$subjectId.'|'.$session;

        if (array_key_exists($cacheKey, $this->eligibleStudentCache)) {
            return $this->eligibleStudentCache[$cacheKey];
        }

        $ids = $this->visibilityService
            ->getVisibleStudentsForSubjectTeacher($teacherId, $classId, $subjectId, $session)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $this->eligibleStudentCache[$cacheKey] = $ids;

        return $ids;
    }

    private function scopeKey(int $teacherId, int $classId, int $subjectId, string $examType): string
    {
        return $teacherId.'|'.$classId.'|'.$subjectId.'|'.$examType;
    }

    private function examTypeLabel(string $examType): string
    {
        $type = ExamType::tryFrom($examType);

        return $type?->label() ?? str_replace('_', ' ', ucfirst($examType));
    }

    private function normalizeDateValue(mixed $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}

