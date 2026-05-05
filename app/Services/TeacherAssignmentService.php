<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeacherAssignmentService
{
    private int $createdSubjectAssignments = 0;

    private int $skippedDuplicates = 0;

    private bool $classTeacherAssigned = false;

    public function assignBulk(
        int $teacherId,
        string $session,
        array $classIds,
        array $subjectIds,
        ?int $classTeacherClassId = null
    ): array {
        $this->createdSubjectAssignments = 0;
        $this->skippedDuplicates = 0;
        $this->classTeacherAssigned = false;

        $cleanClassIds = $this->normalizeIdArray($classIds);
        $cleanSubjectIds = $this->normalizeIdArray($subjectIds);
        $resolvedSession = trim($session);
        $resolvedClassTeacherClassId = $classTeacherClassId !== null ? (int) $classTeacherClassId : null;

        DB::transaction(function () use (
            $teacherId,
            $resolvedSession,
            $cleanClassIds,
            $cleanSubjectIds,
            $resolvedClassTeacherClassId
        ): void {
            foreach ($cleanClassIds as $classId) {
                $this->assignSubjectsToClass($teacherId, $resolvedSession, $classId, $cleanSubjectIds);
            }

            if ($resolvedClassTeacherClassId !== null) {
                if (! in_array($resolvedClassTeacherClassId, $cleanClassIds, true)) {
                    throw ValidationException::withMessages([
                        'class_teacher_class_id' => 'The class teacher class must be one of the selected classes.',
                    ]);
                }

                $this->assignClassTeacher($teacherId, $resolvedSession, $resolvedClassTeacherClassId);
            }
        });

        return [
            'created_subject_assignments' => $this->createdSubjectAssignments,
            'skipped_duplicates' => $this->skippedDuplicates,
            'class_teacher_assigned' => $this->classTeacherAssigned,
        ];
    }

    public function assignSubjectsToClass(int $teacherId, string $session, int $classId, array $subjectIds): void
    {
        $resolvedSession = trim($session);

        foreach ($subjectIds as $subjectId) {
            $subjectId = (int) $subjectId;
            if ($subjectId <= 0) {
                continue;
            }

            $assignment = TeacherAssignment::query()->firstOrCreate([
                'teacher_id' => $teacherId,
                'class_id' => $classId,
                'subject_id' => $subjectId,
                'session' => $resolvedSession,
                'is_class_teacher' => false,
            ]);

            if ($assignment->wasRecentlyCreated) {
                $this->createdSubjectAssignments++;
            } else {
                $this->skippedDuplicates++;
            }
        }
    }

    public function assignClassTeacher(int $teacherId, string $session, int $classId): void
    {
        $resolvedSession = trim($session);
        $class = SchoolClass::query()->findOrFail($classId);

        $existingClassTeacher = TeacherAssignment::query()
            ->where('class_id', $classId)
            ->where('session', $resolvedSession)
            ->where('is_class_teacher', true)
            ->first();

        if ($existingClassTeacher !== null) {
            if ((int) $existingClassTeacher->teacher_id === $teacherId) {
                return;
            }

            $classLabel = $class->name ?? ('Class '.$classId);

            throw ValidationException::withMessages([
                'class_teacher_class_id' => $classLabel.' already has a class teacher for session '.$resolvedSession.'.',
            ]);
        }

        TeacherAssignment::query()->create([
            'teacher_id' => $teacherId,
            'class_id' => $classId,
            'subject_id' => null,
            'is_class_teacher' => true,
            'session' => $resolvedSession,
        ]);

        SchoolClass::query()
            ->whereKey($classId)
            ->update(['class_teacher_id' => $teacherId]);

        $this->classTeacherAssigned = true;
    }

    /**
     * @return array<int, array{
     *   class_id:int,
     *   class_name:string,
     *   current_teacher_id:int|null,
     *   current_teacher_name:string|null,
     *   current_teacher_email:string|null,
     *   current_teacher_code:string|null,
     *   current_employee_code:string|null
     * }>
     */
    public function getClassTeacherAssignmentsBySession(string $session): array
    {
        $resolvedSession = trim($session);

        $classes = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        $classTeacherAssignments = TeacherAssignment::query()
            ->with([
                'teacher:id,teacher_id,user_id,employee_code',
                'teacher.user:id,name,email',
            ])
            ->where('session', $resolvedSession)
            ->where('is_class_teacher', true)
            ->orderByDesc('id')
            ->get(['id', 'teacher_id', 'class_id', 'subject_id', 'is_class_teacher', 'session'])
            ->groupBy('class_id');

        return $classes
            ->map(function (SchoolClass $class) use ($classTeacherAssignments): array {
                /** @var TeacherAssignment|null $current */
                $current = $classTeacherAssignments->get((int) $class->id)?->first();

                return [
                    'class_id' => (int) $class->id,
                    'class_name' => trim((string) $class->name.' '.(string) ($class->section ?? '')),
                    'current_teacher_id' => $current !== null ? (int) $current->teacher_id : null,
                    'current_teacher_name' => $current?->teacher?->user?->name,
                    'current_teacher_email' => $current?->teacher?->user?->email,
                    'current_teacher_code' => $current?->teacher?->teacher_id,
                    'current_employee_code' => $current?->teacher?->employee_code,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{
     *   status:string,
     *   replaced:bool,
     *   previous_teacher_id:int|null
     * }
     */
    public function assignOrReplaceClassTeacher(int $teacherId, int $classId, string $session): array
    {
        $resolvedSession = trim($session);

        return DB::transaction(function () use ($teacherId, $classId, $resolvedSession): array {
            $existingRows = TeacherAssignment::query()
                ->where('class_id', $classId)
                ->where('session', $resolvedSession)
                ->where('is_class_teacher', true)
                ->orderByDesc('id')
                ->get(['id', 'teacher_id', 'class_id', 'session', 'is_class_teacher']);

            /** @var TeacherAssignment|null $existing */
            $existing = $existingRows->first();
            $previousTeacherId = $existing !== null ? (int) $existing->teacher_id : null;

            if (
                $existing !== null
                && (int) $existing->teacher_id === $teacherId
                && $existingRows->count() === 1
            ) {
                return [
                    'status' => 'unchanged',
                    'replaced' => false,
                    'previous_teacher_id' => $previousTeacherId,
                ];
            }

            $replaced = $existing !== null && (int) $existing->teacher_id !== $teacherId;

            if ($existingRows->isNotEmpty()) {
                $this->removeExistingClassTeacher($classId, $resolvedSession);
            }

            TeacherAssignment::query()->create([
                'teacher_id' => $teacherId,
                'class_id' => $classId,
                'subject_id' => null,
                'is_class_teacher' => true,
                'session' => $resolvedSession,
            ]);

            SchoolClass::query()
                ->whereKey($classId)
                ->update(['class_teacher_id' => $teacherId]);

            return [
                'status' => $replaced ? 'replaced' : 'assigned',
                'replaced' => $replaced,
                'previous_teacher_id' => $previousTeacherId,
            ];
        });
    }

    public function removeExistingClassTeacher(int $classId, string $session): void
    {
        $resolvedSession = trim($session);

        $existing = TeacherAssignment::query()
            ->where('class_id', $classId)
            ->where('session', $resolvedSession)
            ->where('is_class_teacher', true)
            ->get(['id', 'teacher_id']);

        if ($existing->isEmpty()) {
            return;
        }

        $teacherIds = $existing
            ->pluck('teacher_id')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        TeacherAssignment::query()
            ->whereIn('id', $existing->pluck('id')->all())
            ->delete();

        if (! empty($teacherIds)) {
            SchoolClass::query()
                ->whereKey($classId)
                ->whereIn('class_teacher_id', $teacherIds)
                ->update(['class_teacher_id' => null]);
        }
    }

    /**
     * @return Collection<int, array{id:int,name:string,email:string,employee_code:string|null,teacher_code:string}>
     */
    public function searchTeachers(string $query, int $limit = 15): Collection
    {
        $needle = trim($query);
        if (mb_strlen($needle) < 2) {
            return collect();
        }

        $safeLimit = max(1, min($limit, 50));
        $contains = '%'.$needle.'%';

        return Teacher::query()
            ->with('user:id,name,email')
            ->where(function (Builder $builder) use ($contains): void {
                $builder->where('teacher_id', 'like', $contains)
                    ->orWhere('employee_code', 'like', $contains)
                    ->orWhereHas('user', function (Builder $userQuery) use ($contains): void {
                        $userQuery->where('name', 'like', $contains)
                            ->orWhere('email', 'like', $contains);
                    });
            })
            ->orderBy('teacher_id')
            ->limit($safeLimit)
            ->get(['id', 'teacher_id', 'user_id', 'employee_code'])
            ->map(static function (Teacher $teacher): array {
                return [
                    'id' => (int) $teacher->id,
                    'name' => (string) ($teacher->user?->name ?? 'Unknown Teacher'),
                    'email' => (string) ($teacher->user?->email ?? ''),
                    'employee_code' => $teacher->employee_code,
                    'teacher_code' => (string) ($teacher->teacher_id ?? ''),
                ];
            })
            ->values();
    }

    /**
     * @return array{
     *   total_assignments:int,
     *   sessions:Collection<int, array{
     *      session:string,
     *      class_teacher_assignments:Collection<int, TeacherAssignment>,
     *      subject_assignments_by_class:Collection<int, array{class:\App\Models\SchoolClass|null,assignments:Collection<int, TeacherAssignment>}>
     *   }>
     * }
     */
    public function getTeacherAssignmentSummary(int $teacherId, ?string $session = null): array
    {
        $resolvedSession = trim((string) $session);

        $assignments = TeacherAssignment::query()
            ->with([
                'teacher:id,teacher_id,user_id,designation,employee_code',
                'teacher.user:id,name,email',
                'classRoom:id,name,section',
                'subject:id,name',
            ])
            ->where('teacher_id', $teacherId)
            ->when($resolvedSession !== '', fn (Builder $query) => $query->where('session', $resolvedSession))
            ->orderByDesc('session')
            ->orderBy('class_id')
            ->orderByDesc('is_class_teacher')
            ->orderBy('subject_id')
            ->get();

        return [
            'total_assignments' => $assignments->count(),
            'sessions' => $this->groupAssignmentsBySession($assignments),
        ];
    }

    public function replaceTeacherAssignmentsForSession(
        int $teacherId,
        string $session,
        array $classIds,
        array $subjectIds,
        ?int $classTeacherClassId = null
    ): array {
        $cleanClassIds = $this->normalizeIdArray($classIds);
        $cleanSubjectIds = $this->normalizeIdArray($subjectIds);
        $resolvedSession = trim($session);
        $resolvedClassTeacherClassId = $classTeacherClassId !== null ? (int) $classTeacherClassId : null;

        if ($resolvedClassTeacherClassId !== null && ! in_array($resolvedClassTeacherClassId, $cleanClassIds, true)) {
            throw ValidationException::withMessages([
                'class_teacher_class_id' => 'The class teacher class must be one of the selected classes.',
            ]);
        }

        return DB::transaction(function () use (
            $teacherId,
            $resolvedSession,
            $cleanClassIds,
            $cleanSubjectIds,
            $resolvedClassTeacherClassId
        ): array {
            $existing = TeacherAssignment::query()
                ->where('teacher_id', $teacherId)
                ->where('session', $resolvedSession)
                ->get(['id', 'class_id', 'is_class_teacher']);

            $overwrittenCount = $existing->count();
            $classTeacherClassIds = $existing
                ->where('is_class_teacher', true)
                ->pluck('class_id')
                ->map(static fn ($id): int => (int) $id)
                ->filter(static fn (int $id): bool => $id > 0)
                ->unique()
                ->values();

            TeacherAssignment::query()
                ->where('teacher_id', $teacherId)
                ->where('session', $resolvedSession)
                ->delete();

            if ($classTeacherClassIds->isNotEmpty()) {
                SchoolClass::query()
                    ->whereIn('id', $classTeacherClassIds->all())
                    ->where('class_teacher_id', $teacherId)
                    ->update(['class_teacher_id' => null]);
            }

            $summary = $this->assignBulk(
                $teacherId,
                $resolvedSession,
                $cleanClassIds,
                $cleanSubjectIds,
                $resolvedClassTeacherClassId
            );

            $summary['overwritten_count'] = $overwrittenCount;

            return $summary;
        });
    }

    /**
     * @return array{
     *   total_source_allocations:int,
     *   copied_count:int,
     *   skipped_count:int,
     *   replaced_count:int,
     *   errors:array<int, string>
     * }
     */
    public function copySectionAllocations(
        SchoolClass $sourceClass,
        SchoolClass $targetClass,
        string $session,
        string $copyMode,
        User $user
    ): array {
        $resolvedSession = trim($session);
        $resolvedCopyMode = trim($copyMode);

        $this->validateSectionCopyRequest($sourceClass, $targetClass, $resolvedSession, $resolvedCopyMode);

        return DB::transaction(function () use (
            $sourceClass,
            $targetClass,
            $resolvedSession,
            $resolvedCopyMode,
            $user
        ): array {
            $sourceRows = TeacherAssignment::query()
                ->where('class_id', (int) $sourceClass->id)
                ->where('session', $resolvedSession)
                ->orderByDesc('is_class_teacher')
                ->orderBy('subject_id')
                ->orderBy('id')
                ->get(['id', 'teacher_id', 'class_id', 'subject_id', 'is_class_teacher', 'session']);

            if ($sourceRows->isEmpty()) {
                throw ValidationException::withMessages([
                    'source_class_id' => 'The source section has no teacher assignments for the selected session.',
                ]);
            }

            $summary = [
                'total_source_allocations' => $sourceRows->count(),
                'copied_count' => 0,
                'skipped_count' => 0,
                'replaced_count' => 0,
                'errors' => [],
            ];

            if ($resolvedCopyMode === 'replace_target_allocations') {
                $targetRows = TeacherAssignment::query()
                    ->where('class_id', (int) $targetClass->id)
                    ->where('session', $resolvedSession)
                    ->lockForUpdate()
                    ->get(['id', 'teacher_id', 'class_id', 'subject_id', 'is_class_teacher']);

                $summary['replaced_count'] = $targetRows->count();

                $targetClassTeacherIds = $targetRows
                    ->where('is_class_teacher', true)
                    ->pluck('teacher_id')
                    ->map(static fn ($id): int => (int) $id)
                    ->filter(static fn (int $id): bool => $id > 0)
                    ->unique()
                    ->values();

                if ($targetRows->isNotEmpty()) {
                    TeacherAssignment::query()
                        ->whereIn('id', $targetRows->pluck('id')->all())
                        ->delete();
                }

                if ($targetClassTeacherIds->isNotEmpty()) {
                    SchoolClass::query()
                        ->whereKey((int) $targetClass->id)
                        ->whereIn('class_teacher_id', $targetClassTeacherIds->all())
                        ->update(['class_teacher_id' => null]);
                }
            }

            $targetRows = TeacherAssignment::query()
                ->where('class_id', (int) $targetClass->id)
                ->where('session', $resolvedSession)
                ->lockForUpdate()
                ->get(['id', 'teacher_id', 'class_id', 'subject_id', 'is_class_teacher']);

            $targetState = $this->sectionAssignmentState($targetRows);
            $copiedAt = now();

            foreach ($sourceRows as $sourceRow) {
                if ($this->shouldSkipSectionCopy($sourceRow, $targetState)) {
                    $summary['skipped_count']++;
                    continue;
                }

                TeacherAssignment::query()->create([
                    'teacher_id' => (int) $sourceRow->teacher_id,
                    'class_id' => (int) $targetClass->id,
                    'subject_id' => $sourceRow->subject_id !== null ? (int) $sourceRow->subject_id : null,
                    'is_class_teacher' => (bool) $sourceRow->is_class_teacher,
                    'session' => $resolvedSession,
                    'copied_from_assignment_id' => (int) $sourceRow->id,
                    'copied_by' => (int) $user->id,
                    'copied_at' => $copiedAt,
                ]);

                $this->rememberSectionAssignment($targetState, $sourceRow);
                $summary['copied_count']++;

                if ((bool) $sourceRow->is_class_teacher) {
                    SchoolClass::query()
                        ->whereKey((int) $targetClass->id)
                        ->update(['class_teacher_id' => (int) $sourceRow->teacher_id]);
                }
            }

            return $summary;
        });
    }

    public function ensureTeacherProfileForUser(int $userId, ?string $designation = 'Teacher'): Teacher
    {
        $existing = Teacher::query()->where('user_id', $userId)->first();
        if ($existing instanceof Teacher) {
            return $existing;
        }

        return Teacher::query()->create([
            'teacher_id' => $this->nextTeacherCode(),
            'user_id' => $userId,
            'designation' => trim((string) $designation) !== '' ? trim((string) $designation) : 'Teacher',
            'employee_code' => null,
        ]);
    }

    public function sessionOptions(int $backward = 1, int $forward = 3): array
    {
        $now = now();
        $currentStartYear = $now->month >= 7 ? $now->year : ($now->year - 1);
        $sessions = [];

        for ($year = $currentStartYear - $backward; $year <= $currentStartYear + $forward; $year++) {
            $sessions[] = $year.'-'.($year + 1);
        }

        return $sessions;
    }

    public function availableSessions(): array
    {
        $storedSessions = TeacherAssignment::query()
            ->select('session')
            ->distinct()
            ->orderByDesc('session')
            ->pluck('session')
            ->filter(static fn ($value): bool => is_string($value) && trim($value) !== '')
            ->values()
            ->all();

        return collect(array_merge($storedSessions, $this->sessionOptions()))
            ->filter(static fn ($value): bool => is_string($value) && trim($value) !== '')
            ->unique()
            ->values()
            ->all();
    }

    public function removeAssignment(int $assignmentId): void
    {
        $assignment = TeacherAssignment::query()->findOrFail($assignmentId);
        if ($assignment->is_class_teacher) {
            SchoolClass::query()
                ->whereKey((int) $assignment->class_id)
                ->where('class_teacher_id', (int) $assignment->teacher_id)
                ->update(['class_teacher_id' => null]);
        }

        $assignment->delete();
    }

    /**
     * @param array<int, mixed> $ids
     * @return array<int, int>
     */
    private function normalizeIdArray(array $ids): array
    {
        return collect($ids)
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function validateSectionCopyRequest(
        SchoolClass $sourceClass,
        SchoolClass $targetClass,
        string $session,
        string $copyMode
    ): void {
        $errors = [];

        if ($session === '') {
            $errors['session'] = 'The session field is required.';
        }

        if (! in_array($copyMode, ['copy_missing_only', 'replace_target_allocations'], true)) {
            $errors['copy_mode'] = 'The selected copy mode is invalid.';
        }

        if ((int) $sourceClass->id === (int) $targetClass->id) {
            $errors['target_class_id'] = 'The source and target sections must be different.';
        }

        if ($this->normalizeClassName((string) $sourceClass->name) !== $this->normalizeClassName((string) $targetClass->name)) {
            $errors['target_class_id'] = 'Allocations can only be copied between sections of the same class.';
        }

        if ($this->normalizeSectionName($sourceClass->section) === $this->normalizeSectionName($targetClass->section)) {
            $errors['target_class_id'] = 'The source and target sections must be different.';
        }

        if (strtolower(trim((string) $targetClass->status)) !== 'active') {
            $errors['target_class_id'] = 'The target section must be active.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param Collection<int, TeacherAssignment> $rows
     * @return array{subject_ids:array<int, bool>, has_class_teacher:bool, assignment_keys:array<string, bool>}
     */
    private function sectionAssignmentState(Collection $rows): array
    {
        $state = [
            'subject_ids' => [],
            'has_class_teacher' => false,
            'assignment_keys' => [],
        ];

        foreach ($rows as $row) {
            $this->rememberSectionAssignment($state, $row);
        }

        return $state;
    }

    /**
     * @param array{subject_ids:array<int, bool>, has_class_teacher:bool, assignment_keys:array<string, bool>} $state
     */
    private function shouldSkipSectionCopy(TeacherAssignment $sourceRow, array $state): bool
    {
        if ((bool) $sourceRow->is_class_teacher && (bool) $state['has_class_teacher']) {
            return true;
        }

        if (! (bool) $sourceRow->is_class_teacher && $sourceRow->subject_id !== null) {
            return isset($state['subject_ids'][(int) $sourceRow->subject_id]);
        }

        return isset($state['assignment_keys'][$this->sectionAssignmentKey($sourceRow)]);
    }

    /**
     * @param array{subject_ids:array<int, bool>, has_class_teacher:bool, assignment_keys:array<string, bool>} $state
     */
    private function rememberSectionAssignment(array &$state, TeacherAssignment $row): void
    {
        if ((bool) $row->is_class_teacher) {
            $state['has_class_teacher'] = true;
        }

        if (! (bool) $row->is_class_teacher && $row->subject_id !== null) {
            $state['subject_ids'][(int) $row->subject_id] = true;
        }

        $state['assignment_keys'][$this->sectionAssignmentKey($row)] = true;
    }

    private function sectionAssignmentKey(TeacherAssignment $row): string
    {
        return (int) $row->teacher_id.'|'.($row->subject_id !== null ? (int) $row->subject_id : 0).'|'.((bool) $row->is_class_teacher ? 1 : 0);
    }

    private function normalizeClassName(string $name): string
    {
        return mb_strtolower(trim($name));
    }

    private function normalizeSectionName(?string $section): string
    {
        return mb_strtolower(trim((string) $section));
    }

    /**
     * @param Collection<int, TeacherAssignment> $assignments
     * @return Collection<int, array{
     *   session:string,
     *   class_teacher_assignments:Collection<int, TeacherAssignment>,
     *   subject_assignments_by_class:Collection<int, array{class:\App\Models\SchoolClass|null,assignments:Collection<int, TeacherAssignment>}>
     * }>
     */
    private function groupAssignmentsBySession(Collection $assignments): Collection
    {
        return $assignments
            ->groupBy(static fn (TeacherAssignment $assignment): string => (string) $assignment->session)
            ->map(function (Collection $group, string $session): array {
                $classTeacherAssignments = $group
                    ->where('is_class_teacher', true)
                    ->sortBy(static fn (TeacherAssignment $assignment): string => trim(
                        ($assignment->classRoom?->name ?? '').' '.($assignment->classRoom?->section ?? '')
                    ))
                    ->values();

                $subjectAssignmentsByClass = $group
                    ->where('is_class_teacher', false)
                    ->whereNotNull('subject_id')
                    ->groupBy('class_id')
                    ->map(function (Collection $classGroup): array {
                        /** @var TeacherAssignment|null $classFirst */
                        $classFirst = $classGroup->first();

                        return [
                            'class' => $classFirst?->classRoom,
                            'assignments' => $classGroup
                                ->sortBy(static fn (TeacherAssignment $assignment): string => $assignment->subject?->name ?? '')
                                ->values(),
                        ];
                    })
                    ->values();

                return [
                    'session' => $session,
                    'class_teacher_assignments' => $classTeacherAssignments,
                    'subject_assignments_by_class' => $subjectAssignmentsByClass,
                ];
            })
            ->sortByDesc(static fn (array $group): string => (string) ($group['session'] ?? ''))
            ->values();
    }

    private function nextTeacherCode(): string
    {
        $seed = (int) Teacher::query()->max('id') + 1;
        $next = max($seed, 1);

        while (true) {
            $code = 'T-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $exists = Teacher::query()->where('teacher_id', $code)->exists();
            if (! $exists) {
                return $code;
            }

            $next++;
        }
    }
}
