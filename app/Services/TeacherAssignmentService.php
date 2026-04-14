<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
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
