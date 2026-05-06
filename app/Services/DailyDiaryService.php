<?php

namespace App\Services;

use App\Models\DailyDiary;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Models\StudentSubject;
use App\Models\StudentSubjectAssignment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DailyDiaryService
{
    public function __construct(
        private readonly TeacherStudentVisibilityService $visibilityService,
        private readonly StudentUserResolverService $studentUserResolver
    ) {
    }

    public function createOrUpdateDiary(
        array $data,
        int $teacherUserId,
        ?UploadedFile $attachment = null,
        ?DailyDiary $dailyDiary = null
    ): object
    {
        $teacher = Teacher::query()
            ->where('user_id', $teacherUserId)
            ->first();

        if (! $teacher) {
            throw new RuntimeException('Teacher profile not found for this account.');
        }

        $classId = (int) ($data['class_id'] ?? 0);
        $subjectId = (int) ($data['subject_id'] ?? 0);
        $session = $this->resolveSession(isset($data['session']) ? (string) $data['session'] : null);
        $diaryDate = Carbon::parse((string) ($data['diary_date'] ?? now()->toDateString()))->toDateString();
        $diaryId = isset($data['diary_id']) ? (int) $data['diary_id'] : null;
        $removeAttachment = array_key_exists('remove_attachment', $data)
            ? (bool) $data['remove_attachment']
            : false;

        if (! $this->teacherCanPostDiary((int) $teacher->id, $classId, $subjectId, $session)) {
            throw new RuntimeException('You are not assigned to this class and subject for the selected session.');
        }

        $payload = [
            'title' => $this->normalizedNullableString($data['title'] ?? null),
            'homework_text' => trim((string) ($data['homework_text'] ?? '')),
            'instructions' => $this->normalizedNullableString($data['instructions'] ?? null),
            'is_published' => array_key_exists('is_published', $data)
                ? (bool) $data['is_published']
                : true,
        ];

        /** @var DailyDiary $diary */
        $diary = DB::transaction(function () use (
            $teacher,
            $teacherUserId,
            $classId,
            $subjectId,
            $session,
            $diaryDate,
            $diaryId,
            $payload,
            $attachment,
            $removeAttachment,
            $dailyDiary
        ): DailyDiary {
            $existingScopeEntry = DailyDiary::query()
                ->where('class_id', $classId)
                ->where('subject_id', $subjectId)
                ->where('session', $session)
                ->whereDate('diary_date', $diaryDate)
                ->lockForUpdate()
                ->first();

            $editingDiary = null;
            if ($dailyDiary instanceof DailyDiary) {
                $editingDiary = DailyDiary::query()
                    ->lockForUpdate()
                    ->findOrFail((int) $dailyDiary->id);

                if ((int) $editingDiary->teacher_id !== (int) $teacher->id) {
                    throw new RuntimeException('You can only edit your own diary entries.');
                }
            } elseif ($diaryId !== null && $diaryId > 0) {
                $editingDiary = DailyDiary::query()
                    ->lockForUpdate()
                    ->findOrFail($diaryId);

                if ((int) $editingDiary->teacher_id !== (int) $teacher->id) {
                    throw new RuntimeException('You can only edit your own diary entries.');
                }
            }

            if ($editingDiary instanceof DailyDiary) {
                if (
                    $existingScopeEntry instanceof DailyDiary
                    && (int) $existingScopeEntry->id !== (int) $editingDiary->id
                ) {
                    $existingScopeEntry->forceFill([
                        'teacher_id' => (int) $teacher->id,
                        'class_id' => $classId,
                        'subject_id' => $subjectId,
                        'session' => $session,
                        'diary_date' => $diaryDate,
                        ...$payload,
                    ])->save();

                    $this->deleteAttachmentFileIfExists($editingDiary);
                    $editingDiary->delete();
                    $this->replaceAttachmentIfNeeded($existingScopeEntry, $attachment, $removeAttachment);

                    return $existingScopeEntry;
                }

                $editingDiary->forceFill([
                    'teacher_id' => (int) $teacher->id,
                    'class_id' => $classId,
                    'subject_id' => $subjectId,
                    'session' => $session,
                    'diary_date' => $diaryDate,
                    ...$payload,
                ])->save();

                $this->replaceAttachmentIfNeeded($editingDiary, $attachment, $removeAttachment);

                return $editingDiary;
            }

            if ($existingScopeEntry instanceof DailyDiary) {
                $existingScopeEntry->forceFill([
                    'teacher_id' => (int) $teacher->id,
                    'class_id' => $classId,
                    'subject_id' => $subjectId,
                    'session' => $session,
                    'diary_date' => $diaryDate,
                    ...$payload,
                ])->save();

                $this->replaceAttachmentIfNeeded($existingScopeEntry, $attachment, $removeAttachment);

                return $existingScopeEntry;
            }

            $newDiary = DailyDiary::query()->create([
                'teacher_id' => (int) $teacher->id,
                'class_id' => $classId,
                'subject_id' => $subjectId,
                'session' => $session,
                'diary_date' => $diaryDate,
                ...$payload,
                'created_by' => $teacherUserId,
            ]);

            $this->replaceAttachmentIfNeeded($newDiary, $attachment, $removeAttachment);

            return $newDiary;
        });

        return $diary->fresh([
            'teacher.user:id,name',
            'classRoom:id,name,section',
            'subject:id,name',
            'createdBy:id,name',
            'attachments:id,daily_diary_id,file_path,file_name',
        ]);
    }

    /**
     * @return array{path:string,name:string,mime:?string,size:?int}
     */
    public function storeAttachment(UploadedFile $file): array
    {
        $storedPath = $file->store('daily-diary', 'public');
        if (! is_string($storedPath) || trim($storedPath) === '') {
            throw new RuntimeException('Unable to store attachment file.');
        }

        return [
            'path' => $storedPath,
            'name' => trim((string) $file->getClientOriginalName()) !== ''
                ? trim((string) $file->getClientOriginalName())
                : basename($storedPath),
            'mime' => $file->getClientMimeType() ?: $file->getMimeType(),
            'size' => $file->getSize() !== false ? (int) $file->getSize() : null,
        ];
    }

    public function replaceAttachmentIfNeeded(
        DailyDiary $dailyDiary,
        ?UploadedFile $file = null,
        bool $remove = false
    ): void {
        if ($file instanceof UploadedFile) {
            $this->deleteAttachmentFileIfExists($dailyDiary);
            $stored = $this->storeAttachment($file);

            $dailyDiary->forceFill([
                'attachment_path' => $stored['path'],
                'attachment_name' => $stored['name'],
                'attachment_mime' => $stored['mime'],
                'attachment_size' => $stored['size'],
            ])->save();

            return;
        }

        if (! $remove) {
            return;
        }

        $this->deleteAttachmentFileIfExists($dailyDiary);
        $dailyDiary->forceFill([
            'attachment_path' => null,
            'attachment_name' => null,
            'attachment_mime' => null,
            'attachment_size' => null,
        ])->save();
    }

    public function deleteAttachmentFileIfExists(DailyDiary $dailyDiary): void
    {
        $path = trim((string) $dailyDiary->attachment_path);
        if ($path === '') {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @return array{path:string,name:string,mime:?string,size:?int}|null
     */
    public function resolveAttachmentMeta(DailyDiary $dailyDiary): ?array
    {
        $currentPath = trim((string) $dailyDiary->attachment_path);
        if ($currentPath !== '') {
            return [
                'path' => $currentPath,
                'name' => $this->attachmentDisplayName($dailyDiary->attachment_name, $currentPath),
                'mime' => $this->normalizedNullableString($dailyDiary->attachment_mime),
                'size' => $dailyDiary->attachment_size !== null ? (int) $dailyDiary->attachment_size : null,
            ];
        }

        $legacyAttachment = $dailyDiary->relationLoaded('attachments')
            ? $dailyDiary->attachments->sortBy('id')->first()
            : $dailyDiary->attachments()->orderBy('id')->first();

        if ($legacyAttachment === null || trim((string) $legacyAttachment->file_path) === '') {
            return null;
        }

        return [
            'path' => trim((string) $legacyAttachment->file_path),
            'name' => $this->attachmentDisplayName($legacyAttachment->file_name, (string) $legacyAttachment->file_path),
            'mime' => null,
            'size' => null,
        ];
    }

    public function userCanViewDiary(User $user, DailyDiary $dailyDiary): bool
    {
        if ($user->can('view_all_daily_diary') || $user->can('monitor_daily_diary')) {
            return true;
        }

        if ($user->hasRole('Teacher')) {
            $teacher = $user->teacher;

            return $teacher !== null
                && (int) $dailyDiary->teacher_id === (int) $teacher->id
                && ($user->can('view_own_daily_diary_entries') || $user->can('edit_own_daily_diary'));
        }

        if (! $user->hasRole('Student') || ! $user->can('view_student_daily_diary')) {
            return false;
        }

        if (! $dailyDiary->is_published) {
            return false;
        }

        $student = $this->resolveStudentFromUser($user);
        if (! $student) {
            return false;
        }

        if ((int) $student->class_id !== (int) $dailyDiary->class_id) {
            return false;
        }

        if ($this->visibilityService->classRequiresSubjectFiltering($student->classRoom)) {
            $subjectIds = $this->subjectIdsForStudent(
                (int) $student->id,
                (int) $student->class_id,
                (string) $dailyDiary->session
            );

            return in_array((int) $dailyDiary->subject_id, $subjectIds, true);
        }

        return true;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *     entries:LengthAwarePaginator,
     *     filters:array<string, mixed>,
     *     sessions:array<int, string>,
     *     classes:array<int, array{id:int,name:string}>,
     *     subjects:array<int, array{id:int,name:string}>
     * }
     */
    public function getTeacherDiaryEntries(int $teacherId, array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);
        $normalized['session'] = $this->resolveSession(
            isset($normalized['session']) ? (string) $normalized['session'] : null
        );

        $entries = DailyDiary::query()
            ->with([
                'classRoom:id,name,section',
                'subject:id,name',
                'teacher.user:id,name',
                'attachments:id,daily_diary_id,file_path,file_name',
            ])
            ->where('teacher_id', $teacherId)
            ->where('session', (string) $normalized['session'])
            ->when(isset($normalized['class_id']) && $normalized['class_id'] !== null, function ($query) use ($normalized): void {
                $query->where('class_id', (int) $normalized['class_id']);
            })
            ->when(isset($normalized['subject_id']) && $normalized['subject_id'] !== null, function ($query) use ($normalized): void {
                $query->where('subject_id', (int) $normalized['subject_id']);
            })
            ->when(isset($normalized['is_published']) && $normalized['is_published'] !== null, function ($query) use ($normalized): void {
                $query->where('is_published', (bool) $normalized['is_published']);
            })
            ->when(isset($normalized['diary_date']) && $normalized['diary_date'] !== null, function ($query) use ($normalized): void {
                $query->whereDate('diary_date', (string) $normalized['diary_date']);
            })
            ->when(isset($normalized['date_from']) && $normalized['date_from'] !== null, function ($query) use ($normalized): void {
                $query->whereDate('diary_date', '>=', (string) $normalized['date_from']);
            })
            ->when(isset($normalized['date_to']) && $normalized['date_to'] !== null, function ($query) use ($normalized): void {
                $query->whereDate('diary_date', '<=', (string) $normalized['date_to']);
            })
            ->orderByDesc('diary_date')
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return [
            'entries' => $entries,
            'filters' => $normalized,
            'sessions' => $this->teacherSessions($teacherId),
            'classes' => $this->teacherClassOptions($teacherId, (string) $normalized['session']),
            'subjects' => $this->teacherSubjectOptions(
                $teacherId,
                (string) $normalized['session'],
                isset($normalized['class_id']) && $normalized['class_id'] !== null
                    ? (int) $normalized['class_id']
                    : null
            ),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *     student:Student,
     *     entries:EloquentCollection<int, DailyDiary>,
     *     filters:array<string, mixed>,
     *     sessions:array<int, string>,
     *     class_requires_subject_filtering:bool,
     *     allowed_subject_ids:array<int, int>
     * }
     */
    public function getStudentVisibleDiaryEntries(int $studentId, array $filters = []): array
    {
        $student = Student::query()
            ->with('classRoom:id,name,section')
            ->findOrFail($studentId);

        $normalized = $this->normalizeFilters($filters);
        $normalized['session'] = $this->resolveSession(
            isset($normalized['session']) ? (string) $normalized['session'] : null
        );

        $requiresSubjectFiltering = $this->visibilityService->classRequiresSubjectFiltering($student->classRoom);
        $allowedSubjectIds = $requiresSubjectFiltering
            ? $this->subjectIdsForStudent((int) $student->id, (int) $student->class_id, (string) $normalized['session'])
            : [];

        $entries = DailyDiary::query()
            ->with([
                'teacher.user:id,name',
                'classRoom:id,name,section',
                'subject:id,name',
                'attachments:id,daily_diary_id,file_path,file_name',
            ])
            ->where('class_id', (int) $student->class_id)
            ->where('session', (string) $normalized['session'])
            ->where('is_published', true)
            ->when($requiresSubjectFiltering, function ($query) use ($allowedSubjectIds): void {
                if ($allowedSubjectIds === []) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->whereIn('subject_id', $allowedSubjectIds);
            })
            ->when(isset($normalized['subject_id']) && $normalized['subject_id'] !== null, function ($query) use ($normalized): void {
                $query->where('subject_id', (int) $normalized['subject_id']);
            })
            ->when(isset($normalized['diary_date']) && $normalized['diary_date'] !== null, function ($query) use ($normalized): void {
                $query->whereDate('diary_date', (string) $normalized['diary_date']);
            })
            ->when(isset($normalized['date_from']) && $normalized['date_from'] !== null, function ($query) use ($normalized): void {
                $query->whereDate('diary_date', '>=', (string) $normalized['date_from']);
            })
            ->when(isset($normalized['date_to']) && $normalized['date_to'] !== null, function ($query) use ($normalized): void {
                $query->whereDate('diary_date', '<=', (string) $normalized['date_to']);
            })
            ->orderByDesc('diary_date')
            ->orderByDesc('updated_at')
            ->get();

        return [
            'student' => $student,
            'entries' => $entries,
            'filters' => $normalized,
            'sessions' => $this->studentSessions((int) $student->id),
            'class_requires_subject_filtering' => $requiresSubjectFiltering,
            'allowed_subject_ids' => $allowedSubjectIds,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *     entries:LengthAwarePaginator,
     *     filters:array<string, mixed>,
     *     sessions:array<int, string>,
     *     teachers:array<int, array{id:int,name:string,teacher_code:string}>,
     *     classes:array<int, array{id:int,name:string}>,
     *     subjects:array<int, array{id:int,name:string}>
     * }
     */
    public function getPrincipalDiaryEntries(array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);
        $normalized['session'] = $this->resolveSession(
            isset($normalized['session']) ? (string) $normalized['session'] : null
        );

        $entries = DailyDiary::query()
            ->with([
                'teacher.user:id,name',
                'classRoom:id,name,section',
                'subject:id,name',
                'createdBy:id,name',
                'attachments:id,daily_diary_id,file_path,file_name',
            ])
            ->where('session', (string) $normalized['session'])
            ->when(isset($normalized['teacher_id']) && $normalized['teacher_id'] !== null, function ($query) use ($normalized): void {
                $query->where('teacher_id', (int) $normalized['teacher_id']);
            })
            ->when(isset($normalized['class_id']) && $normalized['class_id'] !== null, function ($query) use ($normalized): void {
                $query->where('class_id', (int) $normalized['class_id']);
            })
            ->when(isset($normalized['subject_id']) && $normalized['subject_id'] !== null, function ($query) use ($normalized): void {
                $query->where('subject_id', (int) $normalized['subject_id']);
            })
            ->when(isset($normalized['is_published']) && $normalized['is_published'] !== null, function ($query) use ($normalized): void {
                $query->where('is_published', (bool) $normalized['is_published']);
            })
            ->when(isset($normalized['diary_date']) && $normalized['diary_date'] !== null, function ($query) use ($normalized): void {
                $query->whereDate('diary_date', (string) $normalized['diary_date']);
            })
            ->when(isset($normalized['date_from']) && $normalized['date_from'] !== null, function ($query) use ($normalized): void {
                $query->whereDate('diary_date', '>=', (string) $normalized['date_from']);
            })
            ->when(isset($normalized['date_to']) && $normalized['date_to'] !== null, function ($query) use ($normalized): void {
                $query->whereDate('diary_date', '<=', (string) $normalized['date_to']);
            })
            ->orderByDesc('diary_date')
            ->orderBy('class_id')
            ->orderBy('subject_id')
            ->orderByDesc('updated_at')
            ->paginate(25)
            ->withQueryString();

        return [
            'entries' => $entries,
            'filters' => $normalized,
            'sessions' => $this->principalSessions(),
            'teachers' => $this->principalTeacherOptions((string) $normalized['session']),
            'classes' => $this->principalClassOptions((string) $normalized['session']),
            'subjects' => $this->principalSubjectOptions((string) $normalized['session']),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *     entries:LengthAwarePaginator,
     *     filters:array<string, mixed>,
     *     sessions:array<int, string>,
     *     teachers:array<int, array{id:int,name:string,teacher_code:string}>,
     *     classes:array<int, array{id:int,name:string}>,
     *     subjects:array<int, array{id:int,name:string}>
     * }
     */
    public function getWardenDiaryEntries(User $user, array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);
        $normalized['session'] = $this->resolveSession(
            isset($normalized['session']) ? (string) $normalized['session'] : null
        );

        $hostelClassIds = Student::query()
            ->forWarden($user)
            ->distinct()
            ->pluck('class_id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        $entries = DailyDiary::query()
            ->with([
                'teacher.user:id,name',
                'classRoom:id,name,section',
                'subject:id,name',
                'createdBy:id,name',
                'attachments:id,daily_diary_id,file_path,file_name',
            ])
            ->where('session', (string) $normalized['session'])
            ->when($hostelClassIds !== [], fn ($query) => $query->whereIn('class_id', $hostelClassIds))
            ->when($hostelClassIds === [], fn ($query) => $query->whereRaw('1 = 0'))
            ->when(isset($normalized['teacher_id']) && $normalized['teacher_id'] !== null, function ($query) use ($normalized): void {
                $query->where('teacher_id', (int) $normalized['teacher_id']);
            })
            ->when(isset($normalized['class_id']) && $normalized['class_id'] !== null, function ($query) use ($normalized): void {
                $query->where('class_id', (int) $normalized['class_id']);
            })
            ->when(isset($normalized['subject_id']) && $normalized['subject_id'] !== null, function ($query) use ($normalized): void {
                $query->where('subject_id', (int) $normalized['subject_id']);
            })
            ->when(isset($normalized['is_published']) && $normalized['is_published'] !== null, function ($query) use ($normalized): void {
                $query->where('is_published', (bool) $normalized['is_published']);
            })
            ->when(isset($normalized['diary_date']) && $normalized['diary_date'] !== null, function ($query) use ($normalized): void {
                $query->whereDate('diary_date', (string) $normalized['diary_date']);
            })
            ->when(isset($normalized['date_from']) && $normalized['date_from'] !== null, function ($query) use ($normalized): void {
                $query->whereDate('diary_date', '>=', (string) $normalized['date_from']);
            })
            ->when(isset($normalized['date_to']) && $normalized['date_to'] !== null, function ($query) use ($normalized): void {
                $query->whereDate('diary_date', '<=', (string) $normalized['date_to']);
            })
            ->orderByDesc('diary_date')
            ->orderBy('class_id')
            ->orderBy('subject_id')
            ->orderByDesc('updated_at')
            ->paginate(25)
            ->withQueryString();

        return [
            'entries' => $entries,
            'filters' => $normalized,
            'sessions' => $this->principalSessions(),
            'teachers' => $this->wardenTeacherOptions((string) $normalized['session'], $hostelClassIds),
            'classes' => $this->wardenClassOptions((string) $normalized['session'], $hostelClassIds),
            'subjects' => $this->wardenSubjectOptions((string) $normalized['session'], $hostelClassIds),
        ];
    }

    public function getDiaryForWarden(DailyDiary $dailyDiary, User $user): DailyDiary
    {
        $allowedClassIds = Student::query()
            ->forWarden($user)
            ->distinct()
            ->pluck('class_id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        if (! in_array((int) $dailyDiary->class_id, $allowedClassIds, true)) {
            throw new RuntimeException('You are not allowed to access this diary entry.');
        }

        return $dailyDiary->load([
            'teacher.user:id,name',
            'classRoom:id,name,section',
            'subject:id,name',
            'createdBy:id,name',
            'attachments:id,daily_diary_id,file_path,file_name,created_at',
        ]);
    }

    public function teacherCanPostDiary(int $teacherId, int $classId, int $subjectId, string $session): bool
    {
        return TeacherAssignment::query()
            ->where('teacher_id', $teacherId)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('session', $session)
            ->exists();
    }

    /**
     * @return array{
     *     sessions:array<int, string>,
     *     selected_session:string,
     *     assignment_matrix:array<string, array<int, array{
     *          class_id:int,
     *          class_name:string,
     *          subjects:array<int, array{id:int,name:string}>
     *     }>>,
     *     classes:array<int, array{id:int,name:string}>,
     *     subjects_by_class:array<int, array<int, array{id:int,name:string}>>
     * }
     */
    public function getTeacherPostingOptions(int $teacherId, ?string $session = null): array
    {
        $assignments = TeacherAssignment::query()
            ->with([
                'classRoom:id,name,section',
                'subject:id,name',
            ])
            ->where('teacher_id', $teacherId)
            ->whereNotNull('subject_id')
            ->orderByDesc('session')
            ->orderBy('class_id')
            ->orderBy('subject_id')
            ->get(['id', 'class_id', 'subject_id', 'session', 'teacher_id']);

        $sessions = $assignments->pluck('session')
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->unique()
            ->values()
            ->all();

        if ($sessions === []) {
            $sessions = $this->sessionOptions();
        }

        $selectedSession = trim((string) $session);
        if ($selectedSession === '' || ! in_array($selectedSession, $sessions, true)) {
            $selectedSession = $sessions[0] ?? $this->resolveSession(null);
        }

        $assignmentMatrix = [];
        foreach ($assignments->groupBy('session') as $sessionKey => $sessionAssignments) {
            $rows = [];
            foreach ($sessionAssignments->groupBy('class_id') as $classAssignments) {
                $classItem = $classAssignments->first();
                if (! $classItem) {
                    continue;
                }

                $subjects = $classAssignments
                    ->filter(fn (TeacherAssignment $assignment): bool => (int) ($assignment->subject_id ?? 0) > 0)
                    ->map(function (TeacherAssignment $assignment): array {
                        return [
                            'id' => (int) $assignment->subject_id,
                            'name' => (string) ($assignment->subject?->name ?? 'Subject'),
                        ];
                    })
                    ->unique('id')
                    ->sortBy('name')
                    ->values()
                    ->all();

                if ($subjects === []) {
                    continue;
                }

                $rows[] = [
                    'class_id' => (int) $classItem->class_id,
                    'class_name' => trim((string) ($classItem->classRoom?->name ?? '').' '.(string) ($classItem->classRoom?->section ?? '')),
                    'subjects' => $subjects,
                ];
            }

            $assignmentMatrix[$sessionKey] = collect($rows)
                ->sortBy('class_name')
                ->values()
                ->all();
        }

        $selectedRows = $assignmentMatrix[$selectedSession] ?? [];
        $classes = collect($selectedRows)
            ->map(fn (array $row): array => [
                'id' => (int) $row['class_id'],
                'name' => (string) $row['class_name'],
            ])
            ->values()
            ->all();

        $subjectsByClass = collect($selectedRows)
            ->mapWithKeys(fn (array $row): array => [
                (int) $row['class_id'] => array_values($row['subjects']),
            ])
            ->all();

        return [
            'sessions' => $sessions,
            'selected_session' => $selectedSession,
            'assignment_matrix' => $assignmentMatrix,
            'classes' => $classes,
            'subjects_by_class' => $subjectsByClass,
        ];
    }

    public function resolveSession(?string $session): string
    {
        $candidate = trim((string) $session);
        if ($candidate !== '') {
            return $candidate;
        }

        $sessions = $this->sessionOptions();

        return $sessions[1] ?? ($sessions[0] ?? now()->year.'-'.(now()->year + 1));
    }

    /**
     * @return array<int, string>
     */
    public function sessionOptions(int $backward = 1, int $forward = 3): array
    {
        $now = now();
        $currentStartYear = $now->month >= 7 ? $now->year : ($now->year - 1);
        $sessions = [];

        for ($year = $currentStartYear - $backward; $year <= $currentStartYear + $forward; $year++) {
            $sessions[] = $year.'-'.($year + 1);
        }

        return array_reverse($sessions);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function normalizeFilters(array $filters): array
    {
        $normalized = [
            'session' => trim((string) ($filters['session'] ?? '')) ?: null,
            'teacher_id' => isset($filters['teacher_id']) && $filters['teacher_id'] !== ''
                ? (int) $filters['teacher_id']
                : null,
            'class_id' => isset($filters['class_id']) && $filters['class_id'] !== ''
                ? (int) $filters['class_id']
                : null,
            'subject_id' => isset($filters['subject_id']) && $filters['subject_id'] !== ''
                ? (int) $filters['subject_id']
                : null,
            'diary_date' => trim((string) ($filters['diary_date'] ?? '')) ?: null,
            'date_from' => trim((string) ($filters['date_from'] ?? '')) ?: null,
            'date_to' => trim((string) ($filters['date_to'] ?? '')) ?: null,
            'is_published' => null,
        ];

        if (array_key_exists('is_published', $filters)) {
            $raw = $filters['is_published'];
            if ($raw === '' || $raw === null) {
                $normalized['is_published'] = null;
            } else {
                $normalized['is_published'] = (bool) (int) $raw;
            }
        }

        return $normalized;
    }

    /**
     * @return array<int, string>
     */
    private function teacherSessions(int $teacherId): array
    {
        $sessions = TeacherAssignment::query()
            ->where('teacher_id', $teacherId)
            ->pluck('session')
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        if ($sessions !== []) {
            return $sessions;
        }

        return $this->sessionOptions();
    }

    /**
     * @return array<int, string>
     */
    private function studentSessions(int $studentId): array
    {
        $matrixSessions = StudentSubjectAssignment::query()
            ->where('student_id', $studentId)
            ->pluck('session')
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->values()
            ->all();

        $legacySessions = StudentSubject::query()
            ->where('student_id', $studentId)
            ->pluck('session')
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->values()
            ->all();

        $diarySessions = DailyDiary::query()
            ->whereHas('classRoom.students', fn ($query) => $query->where('students.id', $studentId))
            ->pluck('session')
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->values()
            ->all();

        return collect(array_merge($matrixSessions, $legacySessions, $diarySessions, $this->sessionOptions()))
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function principalSessions(): array
    {
        return collect(array_merge(
            DailyDiary::query()
                ->pluck('session')
                ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
                ->values()
                ->all(),
            TeacherAssignment::query()
                ->pluck('session')
                ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
                ->values()
                ->all(),
            $this->sessionOptions()
        ))
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    private function teacherClassOptions(int $teacherId, string $session): array
    {
        return TeacherAssignment::query()
            ->with('classRoom:id,name,section')
            ->where('teacher_id', $teacherId)
            ->where('session', $session)
            ->whereNotNull('subject_id')
            ->get(['class_id'])
            ->map(function (TeacherAssignment $assignment): array {
                return [
                    'id' => (int) $assignment->class_id,
                    'name' => trim((string) ($assignment->classRoom?->name ?? '').' '.(string) ($assignment->classRoom?->section ?? '')),
                ];
            })
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    private function teacherSubjectOptions(int $teacherId, string $session, ?int $classId = null): array
    {
        return TeacherAssignment::query()
            ->with('subject:id,name')
            ->where('teacher_id', $teacherId)
            ->where('session', $session)
            ->whereNotNull('subject_id')
            ->when($classId !== null, fn ($query) => $query->where('class_id', $classId))
            ->get(['subject_id'])
            ->map(function (TeacherAssignment $assignment): array {
                return [
                    'id' => (int) $assignment->subject_id,
                    'name' => (string) ($assignment->subject?->name ?? 'Subject'),
                ];
            })
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int,name:string,teacher_code:string}>
     */
    private function principalTeacherOptions(string $session): array
    {
        return TeacherAssignment::query()
            ->with('teacher.user:id,name')
            ->where('session', $session)
            ->whereNotNull('subject_id')
            ->get(['teacher_id'])
            ->map(function (TeacherAssignment $assignment): array {
                return [
                    'id' => (int) $assignment->teacher_id,
                    'name' => (string) ($assignment->teacher?->user?->name ?? 'Teacher'),
                    'teacher_code' => (string) ($assignment->teacher?->teacher_id ?? ''),
                ];
            })
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    private function principalClassOptions(string $session): array
    {
        return TeacherAssignment::query()
            ->with('classRoom:id,name,section')
            ->where('session', $session)
            ->whereNotNull('subject_id')
            ->get(['class_id'])
            ->map(function (TeacherAssignment $assignment): array {
                return [
                    'id' => (int) $assignment->class_id,
                    'name' => trim((string) ($assignment->classRoom?->name ?? '').' '.(string) ($assignment->classRoom?->section ?? '')),
                ];
            })
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    private function principalSubjectOptions(string $session): array
    {
        return TeacherAssignment::query()
            ->with('subject:id,name')
            ->where('session', $session)
            ->whereNotNull('subject_id')
            ->get(['subject_id'])
            ->map(function (TeacherAssignment $assignment): array {
                return [
                    'id' => (int) $assignment->subject_id,
                    'name' => (string) ($assignment->subject?->name ?? 'Subject'),
                ];
            })
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->all();
    }

    /**
     * @param array<int, int> $classIds
     * @return array<int, array{id:int,name:string,teacher_code:string}>
     */
    private function wardenTeacherOptions(string $session, array $classIds): array
    {
        return DailyDiary::query()
            ->with('teacher.user:id,name')
            ->where('session', $session)
            ->when($classIds !== [], fn ($query) => $query->whereIn('class_id', $classIds))
            ->when($classIds === [], fn ($query) => $query->whereRaw('1 = 0'))
            ->get(['teacher_id'])
            ->map(function (DailyDiary $diary): array {
                return [
                    'id' => (int) $diary->teacher_id,
                    'name' => (string) ($diary->teacher?->user?->name ?? 'Teacher'),
                    'teacher_code' => (string) ($diary->teacher?->teacher_id ?? ''),
                ];
            })
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->all();
    }

    /**
     * @param array<int, int> $classIds
     * @return array<int, array{id:int,name:string}>
     */
    private function wardenClassOptions(string $session, array $classIds): array
    {
        return DailyDiary::query()
            ->with('classRoom:id,name,section')
            ->where('session', $session)
            ->when($classIds !== [], fn ($query) => $query->whereIn('class_id', $classIds))
            ->when($classIds === [], fn ($query) => $query->whereRaw('1 = 0'))
            ->get(['class_id'])
            ->map(function (DailyDiary $diary): array {
                return [
                    'id' => (int) $diary->class_id,
                    'name' => trim((string) ($diary->classRoom?->name ?? '').' '.(string) ($diary->classRoom?->section ?? '')),
                ];
            })
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->all();
    }

    /**
     * @param array<int, int> $classIds
     * @return array<int, array{id:int,name:string}>
     */
    private function wardenSubjectOptions(string $session, array $classIds): array
    {
        return DailyDiary::query()
            ->with('subject:id,name')
            ->where('session', $session)
            ->when($classIds !== [], fn ($query) => $query->whereIn('class_id', $classIds))
            ->when($classIds === [], fn ($query) => $query->whereRaw('1 = 0'))
            ->get(['subject_id'])
            ->map(function (DailyDiary $diary): array {
                return [
                    'id' => (int) $diary->subject_id,
                    'name' => (string) ($diary->subject?->name ?? 'Subject'),
                ];
            })
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function subjectIdsForStudent(int $studentId, int $classId, string $session): array
    {
        $subjectIdsFromMatrix = StudentSubjectAssignment::query()
            ->where('student_id', $studentId)
            ->where('class_id', $classId)
            ->where('session', $session)
            ->pluck('subject_id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($subjectIdsFromMatrix !== []) {
            return $subjectIdsFromMatrix;
        }

        return StudentSubject::query()
            ->where('student_id', $studentId)
            ->where('session', $session)
            ->pluck('subject_id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function normalizedNullableString(mixed $value): ?string
    {
        $stringValue = trim((string) $value);

        return $stringValue !== '' ? $stringValue : null;
    }

    private function attachmentDisplayName(?string $fileName, string $path): string
    {
        $resolvedName = trim((string) $fileName);

        return $resolvedName !== '' ? $resolvedName : basename($path);
    }

    private function resolveStudentFromUser(User $user): ?Student
    {
        return $this->studentUserResolver->resolveForUser($user);
    }
}
