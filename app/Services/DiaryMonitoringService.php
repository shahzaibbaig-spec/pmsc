<?php

namespace App\Services;

use App\Models\DailyDiary;
use App\Models\TeacherAssignment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DiaryMonitoringService
{
    /**
     * @var array<string, bool>
     */
    private array $visibilityCache = [];

    public function __construct(
        private readonly DailyDiaryService $dailyDiaryService,
        private readonly TeacherStudentVisibilityService $visibilityService
    ) {
    }

    /**
     * @return array{
     *     total_expected_postings:int,
     *     total_posted:int,
     *     missing_postings:int,
     *     completion_percentage:float,
     *     teachers_missing_count:int,
     *     fully_covered_classes_count:int,
     *     classes_with_missing_entries_count:int,
     *     missing_rows:array<int, array<string, mixed>>,
     *     rows:array<int, array<string, mixed>>,
     *     classwise_completion:array<int, array{
     *         class_id:int,
     *         class_name:string,
     *         expected_postings:int,
     *         posted:int,
     *         missing:int,
     *         completion_percentage:float
     *     }>
     * }
     */
    public function getPostingCompletionReport(string $session, string $date, array $filters = []): array
    {
        $rows = $this->getMonitoringRows($session, $date, $filters);
        $cards = $this->buildCompletionDashboardCards($rows);
        $classwise = $this->buildClasswiseCompletionRows($rows);

        return array_merge($cards, [
            'missing_rows' => collect($rows)
                ->where('posted', false)
                ->values()
                ->all(),
            'rows' => $rows,
            'classwise_completion' => $classwise,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getMissingDiaryTeachers(string $session, string $date, array $filters = []): array
    {
        return collect($this->getMonitoringRows($session, $date, $filters))
            ->where('posted', false)
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     total_expected_postings:int,
     *     total_posted:int,
     *     missing_postings:int,
     *     completion_percentage:float,
     *     teachers_missing_count:int,
     *     fully_covered_classes_count:int,
     *     classes_with_missing_entries_count:int
     * }
     */
    public function getCompletionDashboardCards(string $session, string $date, array $filters = []): array
    {
        $rows = $this->getMonitoringRows($session, $date, $filters);

        return $this->buildCompletionDashboardCards($rows);
    }

    /**
     * @return array<int, array{
     *     class_id:int,
     *     class_name:string,
     *     expected_postings:int,
     *     posted:int,
     *     missing:int,
     *     completion_percentage:float
     * }>
     */
    public function getClasswiseDiaryCompletion(string $session, string $date, array $filters = []): array
    {
        $rows = $this->getMonitoringRows($session, $date, $filters);

        return $this->buildClasswiseCompletionRows($rows);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function getMonitoringRows(string $session, string $date, array $filters = []): array
    {
        $this->visibilityCache = [];

        $resolvedSession = $this->dailyDiaryService->resolveSession($session);
        $resolvedDate = Carbon::parse(trim((string) $date) !== '' ? $date : now()->toDateString())->toDateString();
        $normalizedFilters = $this->normalizeFilters($filters);

        $assignments = $this->expectedAssignments($resolvedSession, $normalizedFilters);

        $postedDiaries = DailyDiary::query()
            ->with([
                'teacher.user:id,name',
                'classRoom:id,name,section',
                'subject:id,name',
                'attachments:id,daily_diary_id,file_path,file_name',
            ])
            ->where('session', $resolvedSession)
            ->whereDate('diary_date', $resolvedDate)
            ->when($normalizedFilters['teacher_id'] !== null, fn ($query) => $query->where('teacher_id', $normalizedFilters['teacher_id']))
            ->when($normalizedFilters['class_id'] !== null, fn ($query) => $query->where('class_id', $normalizedFilters['class_id']))
            ->when($normalizedFilters['subject_id'] !== null, fn ($query) => $query->where('subject_id', $normalizedFilters['subject_id']))
            ->get();

        $postedDiaryMap = $postedDiaries->keyBy(fn (DailyDiary $diary): string => $this->scopeKey(
            (int) $diary->teacher_id,
            (int) $diary->class_id,
            (int) $diary->subject_id,
            (string) $diary->session
        ));

        return $assignments
            ->map(function (TeacherAssignment $assignment) use ($postedDiaryMap, $resolvedDate): array {
                $scopeKey = $this->scopeKey(
                    (int) $assignment->teacher_id,
                    (int) $assignment->class_id,
                    (int) $assignment->subject_id,
                    (string) $assignment->session
                );
                /** @var DailyDiary|null $postedDiary */
                $postedDiary = $postedDiaryMap->get($scopeKey);
                $attachmentPath = $postedDiary
                    ? trim((string) ($postedDiary->attachment_path ?: data_get($postedDiary->attachments->first(), 'file_path')))
                    : '';
                $attachmentName = $postedDiary
                    ? trim((string) ($postedDiary->attachment_name
                        ?: data_get($postedDiary->attachments->first(), 'file_name')
                        ?: ($attachmentPath !== '' ? basename($attachmentPath) : '')))
                    : '';

                return [
                    'teacher_id' => (int) $assignment->teacher_id,
                    'teacher_name' => (string) ($assignment->teacher?->user?->name ?? 'Teacher'),
                    'teacher_code' => (string) ($assignment->teacher?->teacher_id ?? ''),
                    'class_id' => (int) $assignment->class_id,
                    'class_name' => trim((string) ($assignment->classRoom?->name ?? '').' '.(string) ($assignment->classRoom?->section ?? '')),
                    'subject_id' => (int) $assignment->subject_id,
                    'subject_name' => (string) ($assignment->subject?->name ?? 'Subject'),
                    'session' => (string) $assignment->session,
                    'diary_date' => $resolvedDate,
                    'posted' => $postedDiary !== null,
                    'daily_diary_id' => $postedDiary?->id,
                    'title' => $postedDiary?->title,
                    'homework_preview' => $postedDiary
                        ? Str::limit(trim((string) $postedDiary->homework_text), 110)
                        : null,
                    'instructions_preview' => $postedDiary && $postedDiary->instructions
                        ? Str::limit(trim((string) $postedDiary->instructions), 90)
                        : null,
                    'attachment_path' => $attachmentPath !== '' ? $attachmentPath : null,
                    'attachment_name' => $attachmentName !== '' ? $attachmentName : null,
                    'is_published' => $postedDiary?->is_published,
                    'updated_at' => $postedDiary?->updated_at,
                ];
            })
            ->sortBy(fn (array $row): string => (string) ($row['teacher_name'].'|'.$row['class_name'].'|'.$row['subject_name']))
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $filters
     * @return Collection<int, TeacherAssignment>
     */
    private function expectedAssignments(string $session, array $filters): Collection
    {
        return TeacherAssignment::query()
            ->with([
                'teacher.user:id,name',
                'classRoom:id,name,section',
                'subject:id,name',
            ])
            ->where('session', $session)
            ->whereNotNull('teacher_id')
            ->whereNotNull('class_id')
            ->whereNotNull('subject_id')
            ->when($filters['teacher_id'] !== null, fn ($query) => $query->where('teacher_id', $filters['teacher_id']))
            ->when($filters['class_id'] !== null, fn ($query) => $query->where('class_id', $filters['class_id']))
            ->when($filters['subject_id'] !== null, fn ($query) => $query->where('subject_id', $filters['subject_id']))
            ->get(['id', 'teacher_id', 'class_id', 'subject_id', 'session'])
            ->unique(fn (TeacherAssignment $assignment): string => $this->scopeKey(
                (int) $assignment->teacher_id,
                (int) $assignment->class_id,
                (int) $assignment->subject_id,
                (string) $assignment->session
            ))
            ->filter(fn (TeacherAssignment $assignment): bool => $this->hasValidLinkedModels($assignment))
            ->filter(fn (TeacherAssignment $assignment): bool => $this->assignmentHasVisibleStudents($assignment))
            ->values();
    }

    private function hasValidLinkedModels(TeacherAssignment $assignment): bool
    {
        return (int) $assignment->teacher_id > 0
            && (int) $assignment->class_id > 0
            && (int) ($assignment->subject_id ?? 0) > 0
            && $assignment->teacher !== null
            && $assignment->teacher?->user !== null
            && $assignment->classRoom !== null
            && $assignment->subject !== null;
    }

    private function assignmentHasVisibleStudents(TeacherAssignment $assignment): bool
    {
        $cacheKey = $this->scopeKey(
            (int) $assignment->teacher_id,
            (int) $assignment->class_id,
            (int) $assignment->subject_id,
            (string) $assignment->session
        );

        if (array_key_exists($cacheKey, $this->visibilityCache)) {
            return $this->visibilityCache[$cacheKey];
        }

        $hasStudents = $this->visibilityService
            ->getVisibleStudentsForSubjectTeacher(
                (int) $assignment->teacher_id,
                (int) $assignment->class_id,
                (int) $assignment->subject_id,
                (string) $assignment->session
            )
            ->isNotEmpty();

        $this->visibilityCache[$cacheKey] = $hasStudents;

        return $hasStudents;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{
     *     total_expected_postings:int,
     *     total_posted:int,
     *     missing_postings:int,
     *     completion_percentage:float,
     *     teachers_missing_count:int,
     *     fully_covered_classes_count:int,
     *     classes_with_missing_entries_count:int
     * }
     */
    private function buildCompletionDashboardCards(array $rows): array
    {
        $totalExpected = count($rows);
        $totalPosted = collect($rows)->where('posted', true)->count();
        $missing = max($totalExpected - $totalPosted, 0);
        $completionPercentage = $totalExpected > 0
            ? round(($totalPosted / $totalExpected) * 100, 2)
            : 0.0;

        $teachersMissingCount = collect($rows)
            ->where('posted', false)
            ->pluck('teacher_id')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->count();

        $classwiseRows = $this->buildClasswiseCompletionRows($rows);
        $fullyCoveredClassesCount = collect($classwiseRows)
            ->where('expected_postings', '>', 0)
            ->where('missing', 0)
            ->count();
        $classesWithMissingEntriesCount = collect($classwiseRows)
            ->where('missing', '>', 0)
            ->count();

        return [
            'total_expected_postings' => $totalExpected,
            'total_posted' => $totalPosted,
            'missing_postings' => $missing,
            'completion_percentage' => $completionPercentage,
            'teachers_missing_count' => $teachersMissingCount,
            'fully_covered_classes_count' => $fullyCoveredClassesCount,
            'classes_with_missing_entries_count' => $classesWithMissingEntriesCount,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array{
     *     class_id:int,
     *     class_name:string,
     *     expected_postings:int,
     *     posted:int,
     *     missing:int,
     *     completion_percentage:float
     * }>
     */
    private function buildClasswiseCompletionRows(array $rows): array
    {
        return collect($rows)
            ->groupBy(fn (array $row): int => (int) ($row['class_id'] ?? 0))
            ->map(function (Collection $classRows, int $classId): array {
                $expected = $classRows->count();
                $posted = $classRows->where('posted', true)->count();
                $missing = max($expected - $posted, 0);
                $completionPercentage = $expected > 0
                    ? round(($posted / $expected) * 100, 2)
                    : 0.0;

                return [
                    'class_id' => $classId,
                    'class_name' => (string) ($classRows->first()['class_name'] ?? 'Class'),
                    'expected_postings' => $expected,
                    'posted' => $posted,
                    'missing' => $missing,
                    'completion_percentage' => $completionPercentage,
                ];
            })
            ->sortBy('class_name')
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{teacher_id:?int,class_id:?int,subject_id:?int}
     */
    private function normalizeFilters(array $filters): array
    {
        return [
            'teacher_id' => isset($filters['teacher_id']) && $filters['teacher_id'] !== ''
                ? (int) $filters['teacher_id']
                : null,
            'class_id' => isset($filters['class_id']) && $filters['class_id'] !== ''
                ? (int) $filters['class_id']
                : null,
            'subject_id' => isset($filters['subject_id']) && $filters['subject_id'] !== ''
                ? (int) $filters['subject_id']
                : null,
        ];
    }

    /**
     * @return array{
     *     sessions:array<int, string>,
     *     teachers:array<int, array{id:int,name:string,teacher_code:string}>,
     *     classes:array<int, array{id:int,name:string}>,
     *     subjects:array<int, array{id:int,name:string}>
     * }
     */
    public function filterOptions(?string $session = null): array
    {
        $resolvedSession = $this->dailyDiaryService->resolveSession($session);

        $assignments = TeacherAssignment::query()
            ->with([
                'teacher.user:id,name',
                'classRoom:id,name,section',
                'subject:id,name',
            ])
            ->where('session', $resolvedSession)
            ->whereNotNull('subject_id')
            ->get(['teacher_id', 'class_id', 'subject_id', 'session']);

        return [
            'sessions' => collect(array_merge(
                TeacherAssignment::query()
                    ->pluck('session')
                    ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
                    ->values()
                    ->all(),
                DailyDiary::query()
                    ->pluck('session')
                    ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
                    ->values()
                    ->all(),
                $this->dailyDiaryService->sessionOptions()
            ))
                ->unique()
                ->sortDesc()
                ->values()
                ->all(),
            'teachers' => $assignments
                ->map(fn (TeacherAssignment $assignment): array => [
                    'id' => (int) $assignment->teacher_id,
                    'name' => (string) ($assignment->teacher?->user?->name ?? 'Teacher'),
                    'teacher_code' => (string) ($assignment->teacher?->teacher_id ?? ''),
                ])
                ->unique('id')
                ->sortBy('name')
                ->values()
                ->all(),
            'classes' => $assignments
                ->map(fn (TeacherAssignment $assignment): array => [
                    'id' => (int) $assignment->class_id,
                    'name' => trim((string) ($assignment->classRoom?->name ?? '').' '.(string) ($assignment->classRoom?->section ?? '')),
                ])
                ->unique('id')
                ->sortBy('name')
                ->values()
                ->all(),
            'subjects' => $assignments
                ->map(fn (TeacherAssignment $assignment): array => [
                    'id' => (int) $assignment->subject_id,
                    'name' => (string) ($assignment->subject?->name ?? 'Subject'),
                ])
                ->unique('id')
                ->sortBy('name')
                ->values()
                ->all(),
        ];
    }

    private function scopeKey(int $teacherId, int $classId, int $subjectId, string $session): string
    {
        return $teacherId.'|'.$classId.'|'.$subjectId.'|'.$session;
    }
}
