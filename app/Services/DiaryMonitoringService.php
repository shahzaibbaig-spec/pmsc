<?php

namespace App\Services;

use App\Models\DailyDiary;
use App\Models\TeacherAssignment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DiaryMonitoringService
{
    public function __construct(
        private readonly DailyDiaryService $dailyDiaryService
    ) {
    }

    /**
     * @return array{
     *     total_expected_postings:int,
     *     total_posted:int,
     *     missing_postings:int,
     *     completion_percentage:float,
     *     missing_rows:array<int, array<string, mixed>>,
     *     rows:array<int, array<string, mixed>>
     * }
     */
    public function getPostingCompletionReport(string $session, string $date): array
    {
        $rows = $this->getMonitoringRows($session, $date);

        $totalExpected = count($rows);
        $totalPosted = collect($rows)->where('posted', true)->count();
        $missing = max($totalExpected - $totalPosted, 0);
        $completionPercentage = $totalExpected > 0
            ? round(($totalPosted / $totalExpected) * 100, 2)
            : 100.0;

        return [
            'total_expected_postings' => $totalExpected,
            'total_posted' => $totalPosted,
            'missing_postings' => $missing,
            'completion_percentage' => $completionPercentage,
            'missing_rows' => collect($rows)
                ->where('posted', false)
                ->values()
                ->all(),
            'rows' => $rows,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getMissingDiaryTeachers(string $session, string $date): array
    {
        return collect($this->getMonitoringRows($session, $date))
            ->where('posted', false)
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function getMonitoringRows(string $session, string $date, array $filters = []): array
    {
        $resolvedSession = $this->dailyDiaryService->resolveSession($session);
        $resolvedDate = Carbon::parse(trim((string) $date) !== '' ? $date : now()->toDateString())->toDateString();

        $teacherId = isset($filters['teacher_id']) && $filters['teacher_id'] !== ''
            ? (int) $filters['teacher_id']
            : null;
        $classId = isset($filters['class_id']) && $filters['class_id'] !== ''
            ? (int) $filters['class_id']
            : null;
        $subjectId = isset($filters['subject_id']) && $filters['subject_id'] !== ''
            ? (int) $filters['subject_id']
            : null;

        $assignments = TeacherAssignment::query()
            ->with([
                'teacher.user:id,name',
                'classRoom:id,name,section',
                'subject:id,name',
            ])
            ->where('session', $resolvedSession)
            ->whereNotNull('subject_id')
            ->when($teacherId !== null, fn ($query) => $query->where('teacher_id', $teacherId))
            ->when($classId !== null, fn ($query) => $query->where('class_id', $classId))
            ->when($subjectId !== null, fn ($query) => $query->where('subject_id', $subjectId))
            ->get(['id', 'teacher_id', 'class_id', 'subject_id', 'session'])
            ->unique(fn (TeacherAssignment $assignment): string => $this->scopeKey(
                (int) $assignment->teacher_id,
                (int) $assignment->class_id,
                (int) $assignment->subject_id,
                (string) $assignment->session
            ))
            ->values();

        $postedDiaries = DailyDiary::query()
            ->with([
                'teacher.user:id,name',
                'classRoom:id,name,section',
                'subject:id,name',
            ])
            ->where('session', $resolvedSession)
            ->whereDate('diary_date', $resolvedDate)
            ->when($teacherId !== null, fn ($query) => $query->where('teacher_id', $teacherId))
            ->when($classId !== null, fn ($query) => $query->where('class_id', $classId))
            ->when($subjectId !== null, fn ($query) => $query->where('subject_id', $subjectId))
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
                    'is_published' => $postedDiary?->is_published,
                    'updated_at' => $postedDiary?->updated_at,
                ];
            })
            ->sortBy(fn (array $row): string => (string) ($row['teacher_name'].'|'.$row['class_name'].'|'.$row['subject_name']))
            ->values()
            ->all();
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

