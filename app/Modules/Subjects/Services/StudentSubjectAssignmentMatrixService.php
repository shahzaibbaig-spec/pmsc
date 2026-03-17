<?php

namespace App\Modules\Subjects\Services;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentSubjectAssignment;
use App\Models\Subject;
use App\Models\SubjectGroup;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class StudentSubjectAssignmentMatrixService
{
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

    public function matrix(
        int $classId,
        string $session,
        ?string $search = null,
        int $page = 1,
        int $perPage = 20
    ): array
    {
        $classRoom = SchoolClass::query()->findOrFail($classId);

        $perPage = min(max($perPage, 10), 60);
        $search = $search !== null ? trim($search) : null;

        $studentsPaginator = Student::query()
            ->where('class_id', $classRoom->id)
            ->when($search !== null && $search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('student_id', 'like', '%'.$search.'%')
                        ->orWhere('father_name', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->orderBy('student_id')
            ->paginate(
                $perPage,
                ['id', 'student_id', 'name', 'father_name', 'class_id'],
                'page',
                max($page, 1)
            );

        /** @var Collection<int, Student> $students */
        $students = $studentsPaginator->getCollection();

        $subjects = $classRoom->subjects()
            ->where('subjects.status', 'active')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['subjects.id', 'subjects.name', 'subjects.code', 'subjects.is_default']);

        $studentIds = $students->pluck('id');
        $subjectIds = $subjects->pluck('id');

        $assignments = StudentSubjectAssignment::query()
            ->where('session', $session)
            ->where('class_id', $classRoom->id)
            ->when($studentIds->isNotEmpty(), fn ($query) => $query->whereIn('student_id', $studentIds))
            ->when($subjectIds->isNotEmpty(), fn ($query) => $query->whereIn('subject_id', $subjectIds))
            ->get(['student_id', 'subject_id', 'subject_group_id', 'updated_at']);

        $latestUpdates = StudentSubjectAssignment::query()
            ->where('session', $session)
            ->where('class_id', $classRoom->id)
            ->when($studentIds->isNotEmpty(), fn ($query) => $query->whereIn('student_id', $studentIds))
            ->selectRaw('student_id, MAX(updated_at) as latest_updated_at')
            ->groupBy('student_id')
            ->get()
            ->mapWithKeys(fn ($row): array => [
                (int) $row->student_id => $row->latest_updated_at ? (string) $row->latest_updated_at : '',
            ]);

        $commonAssignmentMap = $assignments
            ->whereNull('subject_group_id')
            ->groupBy('student_id')
            ->map(fn (Collection $rows): array => $rows
                ->pluck('subject_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all());

        $groupAssignmentMap = $assignments
            ->whereNotNull('subject_group_id')
            ->sortByDesc('updated_at')
            ->groupBy('student_id')
            ->map(function (Collection $rows): ?int {
                $groupId = $rows->first()?->subject_group_id;

                return $groupId !== null ? (int) $groupId : null;
            });

        return [
            'class' => [
                'id' => $classRoom->id,
                'name' => $classRoom->name,
                'section' => $classRoom->section,
                'display_name' => trim($classRoom->name.' '.($classRoom->section ?? '')),
            ],
            'session' => $session,
            'search' => $search ?? '',
            'subjects' => $subjects->map(fn ($subject): array => [
                'id' => $subject->id,
                'name' => $subject->name,
                'code' => $subject->code,
                'is_default' => (bool) $subject->is_default,
            ])->values()->all(),
            'students' => $students->map(fn (Student $student): array => [
                'id' => $student->id,
                'student_id' => $student->student_id,
                'name' => $student->name,
                'father_name' => $student->father_name,
                'assigned_subject_ids' => $commonAssignmentMap->get($student->id, []),
                'common_subject_ids' => $commonAssignmentMap->get($student->id, []),
                'subject_group_id' => $groupAssignmentMap->get((int) $student->id),
                'last_updated_at' => $latestUpdates->get((int) $student->id, ''),
            ])->values()->all(),
            'pagination' => [
                'current_page' => $studentsPaginator->currentPage(),
                'last_page' => $studentsPaginator->lastPage(),
                'per_page' => $studentsPaginator->perPage(),
                'total' => $studentsPaginator->total(),
                'from' => $studentsPaginator->firstItem(),
                'to' => $studentsPaginator->lastItem(),
            ],
        ];
    }

    /**
     * @param array<int, int|string> $subjectIds
     */
    public function replaceStudentAssignments(int $studentId, string $session, array $subjectIds, int $assignedBy): int
    {
        $student = Student::query()->findOrFail($studentId);
        $resolvedAssignedBy = $this->resolveAssignedBy($assignedBy);

        $allowedSubjectIds = $student->classRoom
            ? $student->classRoom->subjects()
                ->where('subjects.status', 'active')
                ->pluck('subjects.id')
                ->map(fn ($id) => (int) $id)
                ->all()
            : [];

        $normalized = $this->normalizeSubjectIds($subjectIds, $allowedSubjectIds);

        return $this->executeWithUserForeignKeyFallback(
            $resolvedAssignedBy,
            function (?int $safeAssignedBy) use ($student, $session, $normalized): int {
                return DB::transaction(function () use ($student, $session, $normalized, $safeAssignedBy): int {
                    $groupBasedSubjectIds = StudentSubjectAssignment::query()
                        ->where('session', $session)
                        ->where('student_id', (int) $student->id)
                        ->whereNotNull('subject_group_id')
                        ->pluck('subject_id')
                        ->map(fn ($id): int => (int) $id)
                        ->values()
                        ->all();

                    $conflicting = array_values(array_intersect($normalized, $groupBasedSubjectIds));
                    if (! empty($conflicting)) {
                        throw new RuntimeException('Some selected subjects are currently assigned via a subject group. Change the group first or remove conflicting subjects.');
                    }

                    StudentSubjectAssignment::query()
                        ->where('session', $session)
                        ->where('student_id', (int) $student->id)
                        ->whereNull('subject_group_id')
                        ->delete();

                    if (empty($normalized)) {
                        return 0;
                    }

                    $now = now();
                    $rows = collect($normalized)->map(fn (int $subjectId): array => [
                        'session' => $session,
                        'student_id' => (int) $student->id,
                        'class_id' => (int) $student->class_id,
                        'subject_id' => $subjectId,
                        'subject_group_id' => null,
                        'assigned_by' => $safeAssignedBy,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all();

                    StudentSubjectAssignment::query()->insert($rows);

                    return count($rows);
                });
            }
        );
    }

    /**
     * @param array<int, int|string> $subjectIds
     */
    public function replaceClassAssignments(int $classId, string $session, array $subjectIds, int $assignedBy): array
    {
        $classRoom = SchoolClass::query()->findOrFail($classId);
        $resolvedAssignedBy = $this->resolveAssignedBy($assignedBy);
        $students = Student::query()
            ->where('class_id', $classRoom->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->orderBy('student_id')
            ->get(['id', 'class_id', 'student_id']);

        $allowedSubjectIds = $classRoom->subjects()
            ->where('subjects.status', 'active')
            ->pluck('subjects.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $normalized = $this->normalizeSubjectIds($subjectIds, $allowedSubjectIds);

        $studentIds = $students->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();
        Log::info('Bulk class subject assignment students matched.', [
            'session' => $session,
            'class_id' => (int) $classRoom->id,
            'matched_students_count' => count($studentIds),
            'matched_student_ids' => $studentIds,
            'subject_ids' => $normalized,
        ]);

        return $this->executeWithUserForeignKeyFallback(
            $resolvedAssignedBy,
            function (?int $safeAssignedBy) use ($students, $session, $normalized): array {
                return DB::transaction(function () use ($students, $session, $normalized, $safeAssignedBy): array {
                    $studentsCount = $students->count();
                    $subjectsCount = count($normalized);
                    $assignmentsCreated = 0;

                    if ($studentsCount === 0 || $subjectsCount === 0) {
                        return [
                            'students_count' => $studentsCount,
                            'subjects_count' => $subjectsCount,
                            'assignments_created' => 0,
                        ];
                    }

                    foreach ($students as $student) {
                        foreach ($normalized as $subjectId) {
                            $assignment = StudentSubjectAssignment::query()->firstOrCreate(
                                [
                                    'session' => $session,
                                    'student_id' => (int) $student->id,
                                    'subject_id' => (int) $subjectId,
                                ],
                                [
                                    'class_id' => (int) $student->class_id,
                                    'subject_group_id' => null,
                                    'assigned_by' => $safeAssignedBy,
                                ]
                            );

                            if ($assignment->wasRecentlyCreated) {
                                $assignmentsCreated++;
                                continue;
                            }

                            // Ensure existing assignment behaves as common-subject assignment in matrix.
                            $needsUpdate = $assignment->subject_group_id !== null
                                || (int) $assignment->class_id !== (int) $student->class_id
                                || (int) ($assignment->assigned_by ?? 0) !== (int) ($safeAssignedBy ?? 0);

                            if ($needsUpdate) {
                                $assignment->forceFill([
                                    'class_id' => (int) $student->class_id,
                                    'subject_group_id' => null,
                                    'assigned_by' => $safeAssignedBy,
                                ])->save();
                            }
                        }
                    }

                    return [
                        'students_count' => $studentsCount,
                        'subjects_count' => $subjectsCount,
                        'assignments_created' => $assignmentsCreated,
                    ];
                });
            }
        );
    }

    public function subjectGroups(int $classId, string $session): array
    {
        SchoolClass::query()->findOrFail($classId);

        $groups = SubjectGroup::query()
            ->where('session', $session)
            ->where('class_id', $classId)
            ->with(['subjects' => function ($query): void {
                $query
                    ->select('subjects.id', 'subjects.name', 'subjects.code')
                    ->orderBy('subjects.name');
            }])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return $groups
            ->map(fn (SubjectGroup $group): array => $this->buildGroupPayload($group))
            ->values()
            ->all();
    }

    /**
     * @param array<int, int|string> $subjectIds
     */
    public function createSubjectGroup(
        string $session,
        int $classId,
        string $name,
        ?string $description,
        array $subjectIds,
        ?int $createdBy,
        bool $isActive = true
    ): array {
        $classRoom = SchoolClass::query()->findOrFail($classId);
        $allowedSubjectIds = $classRoom->subjects()
            ->where('subjects.status', 'active')
            ->pluck('subjects.id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $normalized = $this->normalizeSubjectIds($subjectIds, $allowedSubjectIds);
        if (empty($normalized)) {
            throw new RuntimeException('Please select at least one subject for the group.');
        }

        $cleanName = trim($name);
        if ($cleanName === '') {
            throw new RuntimeException('Group name is required.');
        }

        $cleanDescription = $description !== null ? trim($description) : null;
        if ($cleanDescription === '') {
            $cleanDescription = null;
        }

        try {
            $resolvedCreatedBy = $createdBy !== null ? $this->resolveAssignedBy($createdBy) : null;
            $group = $this->executeWithUserForeignKeyFallback(
                $resolvedCreatedBy,
                function (?int $safeCreatedBy) use (
                    $session,
                    $classRoom,
                    $cleanName,
                    $cleanDescription,
                    $normalized,
                    $isActive
                ): SubjectGroup {
                    return DB::transaction(function () use (
                        $session,
                        $classRoom,
                        $cleanName,
                        $cleanDescription,
                        $normalized,
                        $safeCreatedBy,
                        $isActive
                    ): SubjectGroup {
                        /** @var SubjectGroup $group */
                        $group = SubjectGroup::query()->create([
                            'session' => $session,
                            'class_id' => (int) $classRoom->id,
                            'name' => $cleanName,
                            'description' => $cleanDescription,
                            'is_active' => $isActive,
                            'created_by' => $safeCreatedBy,
                        ]);

                        $group->subjects()->sync($normalized);

                        return $group;
                    });
                }
            );
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                throw new RuntimeException('A subject group with this name already exists for the selected class and session.');
            }

            throw $exception;
        }

        $group->load(['subjects' => function ($query): void {
            $query
                ->select('subjects.id', 'subjects.name', 'subjects.code')
                ->orderBy('subjects.name');
        }]);

        return $this->buildGroupPayload($group);
    }

    /**
     * @param array<int, int|string> $subjectIds
     */
    public function updateSubjectGroup(
        SubjectGroup $subjectGroup,
        string $name,
        ?string $description,
        array $subjectIds,
        ?bool $isActive = null
    ): array {
        $classRoom = SchoolClass::query()->findOrFail((int) $subjectGroup->class_id);
        $allowedSubjectIds = $classRoom->subjects()
            ->where('subjects.status', 'active')
            ->pluck('subjects.id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $normalized = $this->normalizeSubjectIds($subjectIds, $allowedSubjectIds);
        if (empty($normalized)) {
            throw new RuntimeException('Please select at least one subject for the group.');
        }

        $cleanName = trim($name);
        if ($cleanName === '') {
            throw new RuntimeException('Group name is required.');
        }

        $cleanDescription = $description !== null ? trim($description) : null;
        if ($cleanDescription === '') {
            $cleanDescription = null;
        }

        try {
            DB::transaction(function () use ($subjectGroup, $cleanName, $cleanDescription, $normalized, $isActive): void {
                $updates = [
                    'name' => $cleanName,
                    'description' => $cleanDescription,
                ];

                if ($isActive !== null) {
                    $updates['is_active'] = $isActive;
                }

                $subjectGroup->forceFill($updates)->save();
                $subjectGroup->subjects()->sync($normalized);
            });
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                throw new RuntimeException('A subject group with this name already exists for the selected class and session.');
            }

            throw $exception;
        }

        $subjectGroup->refresh()->load(['subjects' => function ($query): void {
            $query
                ->select('subjects.id', 'subjects.name', 'subjects.code')
                ->orderBy('subjects.name');
        }]);

        return $this->buildGroupPayload($subjectGroup);
    }

    public function createCustomSubjectForClass(int $classId, string $subjectName): array
    {
        // Keep class validation because the UI action is class-scoped.
        SchoolClass::query()->findOrFail($classId);
        $normalizedName = preg_replace('/\s+/', ' ', trim($subjectName));
        if ($normalizedName === null || $normalizedName === '') {
            throw new RuntimeException('Please enter a custom subject name.');
        }

        return DB::transaction(function () use ($normalizedName): array {
            $lowerName = mb_strtolower($normalizedName, 'UTF-8');
            /** @var Subject|null $subject */
            $subject = Subject::query()
                ->whereRaw('LOWER(name) = ?', [$lowerName])
                ->first();

            $wasCreated = false;
            if (! $subject) {
                $subject = Subject::query()->create([
                    'name' => $normalizedName,
                    'code' => null,
                    'is_default' => false,
                    'status' => 'active',
                ]);
                $wasCreated = true;
            }

            if (($subject->status ?? 'active') !== 'active') {
                $subject->status = 'active';
                $subject->save();
            }

            $allClassIds = SchoolClass::query()
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all();

            $attachedClassIds = $subject->classRooms()
                ->pluck('school_classes.id')
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all();

            $alreadyAttached = empty(array_diff($allClassIds, $attachedClassIds));

            if (! $alreadyAttached && $allClassIds !== []) {
                $subject->classRooms()->syncWithoutDetaching($allClassIds);
            }

            return [
                'subject' => [
                    'id' => (int) $subject->id,
                    'name' => $subject->name,
                    'code' => $subject->code,
                    'is_default' => (bool) $subject->is_default,
                ],
                'was_created' => $wasCreated,
                'already_attached' => $alreadyAttached,
            ];
        });
    }

    public function assignSubjectGroupToStudent(
        int $studentId,
        string $session,
        ?int $groupId,
        int $assignedBy
    ): array {
        $student = Student::query()->findOrFail($studentId);
        $resolvedAssignedBy = $this->resolveAssignedBy($assignedBy);
        $subjectGroup = null;
        $groupSubjectIds = [];

        if ($groupId !== null) {
            $subjectGroup = SubjectGroup::query()
                ->where('id', $groupId)
                ->where('session', $session)
                ->where('class_id', (int) $student->class_id)
                ->where('is_active', true)
                ->first();

            if (! $subjectGroup) {
                throw new RuntimeException('Selected subject group is not available for this class and session.');
            }

            $groupSubjectIds = $subjectGroup->subjects()
                ->pluck('subjects.id')
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all();
        }

        return $this->executeWithUserForeignKeyFallback(
            $resolvedAssignedBy,
            function (?int $safeAssignedBy) use (
                $student,
                $session,
                $subjectGroup,
                $groupSubjectIds
            ): array {
                return DB::transaction(function () use (
                    $student,
                    $session,
                    $subjectGroup,
                    $groupSubjectIds,
                    $safeAssignedBy
                ): array {
                    $commonSubjectIds = StudentSubjectAssignment::query()
                        ->where('session', $session)
                        ->where('student_id', (int) $student->id)
                        ->whereNull('subject_group_id')
                        ->pluck('subject_id')
                        ->map(fn ($id): int => (int) $id)
                        ->values()
                        ->all();

                    $subjectsToAssign = array_values(array_diff($groupSubjectIds, $commonSubjectIds));
                    $skippedDueCommon = count($groupSubjectIds) - count($subjectsToAssign);

                    StudentSubjectAssignment::query()
                        ->where('session', $session)
                        ->where('student_id', (int) $student->id)
                        ->whereNotNull('subject_group_id')
                        ->delete();

                    if ($subjectGroup && ! empty($subjectsToAssign)) {
                        $now = now();
                        $rows = collect($subjectsToAssign)->map(fn (int $subjectId): array => [
                            'session' => $session,
                            'student_id' => (int) $student->id,
                            'class_id' => (int) $student->class_id,
                            'subject_id' => $subjectId,
                            'subject_group_id' => (int) $subjectGroup->id,
                            'assigned_by' => $safeAssignedBy,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])->all();

                        StudentSubjectAssignment::query()->insert($rows);
                    }

                    return [
                        'group_id' => $subjectGroup?->id,
                        'assigned_count' => count($subjectsToAssign),
                        'skipped_due_common' => $skippedDueCommon,
                        'updated_at' => now()->toDateTimeString(),
                    ];
                });
            }
        );
    }

    /**
     * @param array<int, int|string> $subjectIds
     * @param array<int, int> $allowedSubjectIds
     * @return array<int, int>
     */
    private function normalizeSubjectIds(array $subjectIds, array $allowedSubjectIds): array
    {
        $allowedLookup = collect($allowedSubjectIds)
            ->map(fn ($id) => (int) $id)
            ->flip()
            ->all();

        $normalized = collect($subjectIds)
            ->map(fn ($value) => (int) $value)
            ->filter(fn (int $value): bool => $value > 0)
            ->values()
            ->all();

        foreach ($normalized as $subjectId) {
            if (! isset($allowedLookup[$subjectId])) {
                throw new RuntimeException('One or more selected subjects are not defined for this class.');
            }
        }

        return array_values(array_unique($normalized));
    }

    private function buildGroupPayload(SubjectGroup $group): array
    {
        $subjects = $group->subjects
            ->sortBy('name')
            ->values();

        return [
            'id' => (int) $group->id,
            'session' => $group->session,
            'class_id' => (int) $group->class_id,
            'name' => $group->name,
            'description' => $group->description,
            'is_active' => (bool) $group->is_active,
            'subjects_count' => $subjects->count(),
            'subject_ids' => $subjects->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
            'subjects' => $subjects->map(fn ($subject): array => [
                'id' => (int) $subject->id,
                'name' => $subject->name,
                'code' => $subject->code,
            ])->all(),
            'created_at' => optional($group->created_at)?->toDateTimeString(),
            'updated_at' => optional($group->updated_at)?->toDateTimeString(),
        ];
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        if ($sqlState === '23000') {
            return true;
        }

        $message = strtolower($exception->getMessage());

        return str_contains($message, 'unique')
            || str_contains($message, 'duplicate')
            || str_contains($message, 'subject_groups_session_class_name_unique');
    }

    /**
     * @template TReturn
     * @param callable(?int): TReturn $callback
     * @return TReturn
     */
    private function executeWithUserForeignKeyFallback(?int $userId, callable $callback): mixed
    {
        try {
            return $callback($userId);
        } catch (QueryException $exception) {
            if ($userId !== null && $this->isUserForeignKeyViolation($exception)) {
                Log::warning('User FK failed during subject assignment write; retrying without user reference.', [
                    'user_id' => $userId,
                    'sql_state' => $exception->errorInfo[0] ?? null,
                    'driver_code' => $exception->errorInfo[1] ?? null,
                    'message' => $exception->getMessage(),
                ]);

                return $callback(null);
            }

            throw $exception;
        }
    }

    private function isUserForeignKeyViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);
        if ($sqlState !== '23000' || $driverCode !== 1452) {
            return false;
        }

        $message = strtolower($exception->getMessage());
        $referencesUsers = str_contains($message, 'references `users` (`id`)');
        if (! $referencesUsers) {
            return false;
        }

        $studentAssignmentViolation = str_contains($message, 'student_subject_assignments')
            && str_contains($message, 'assigned_by');
        $subjectGroupViolation = str_contains($message, 'subject_groups')
            && str_contains($message, 'created_by');

        return $studentAssignmentViolation || $subjectGroupViolation;
    }

    private function resolveAssignedBy(?int $assignedBy): ?int
    {
        if ($assignedBy === null || $assignedBy <= 0) {
            return null;
        }

        return User::query()->useWritePdo()->whereKey($assignedBy)->exists()
            ? $assignedBy
            : null;
    }
}
