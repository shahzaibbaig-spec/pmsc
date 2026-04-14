<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TeacherAssignmentRolloverService
{
    /**
     * @param array<int, int>|null $teacherIds
     * @return array{
     *   from_session:string,
     *   to_session:string,
     *   teachers_count:int,
     *   assignment_count:int,
     *   duplicate_count:int,
     *   class_teacher_conflicts:int,
     *   target_existing_assignments:int,
     *   teacher_rows:Collection<int, array{
     *      teacher_id:int,
     *      teacher_name:string,
     *      source_assignments:int,
     *      duplicates:int,
     *      class_teacher_conflicts:int
     *   }>
     * }
     */
    public function previewRollover(string $fromSession, string $toSession, ?array $teacherIds = null): array
    {
        $normalizedTeacherIds = $this->normalizeTeacherIds($teacherIds);
        $sourceRows = $this->sourceRows($fromSession, $normalizedTeacherIds);

        if ($sourceRows->isEmpty()) {
            return [
                'from_session' => trim($fromSession),
                'to_session' => trim($toSession),
                'teachers_count' => 0,
                'assignment_count' => 0,
                'duplicate_count' => 0,
                'class_teacher_conflicts' => 0,
                'target_existing_assignments' => 0,
                'teacher_rows' => collect(),
            ];
        }

        $sourceTeacherIds = $sourceRows->pluck('teacher_id')->map(static fn ($id): int => (int) $id)->unique()->values();
        $targetRows = TeacherAssignment::query()
            ->where('session', trim($toSession))
            ->whereIn('teacher_id', $sourceTeacherIds->all())
            ->get(['teacher_id', 'class_id', 'subject_id', 'is_class_teacher']);

        $targetClassTeachersByClass = TeacherAssignment::query()
            ->where('session', trim($toSession))
            ->where('is_class_teacher', true)
            ->get(['class_id', 'teacher_id'])
            ->keyBy(fn (TeacherAssignment $row): int => (int) $row->class_id);

        $targetKeys = $this->assignmentKeySet($targetRows);
        $teachers = Teacher::query()
            ->with('user:id,name')
            ->whereIn('id', $sourceTeacherIds->all())
            ->get(['id', 'user_id'])
            ->keyBy('id');

        $teacherRows = $sourceRows
            ->groupBy('teacher_id')
            ->map(function (Collection $rows, int|string $teacherId) use ($targetKeys, $targetClassTeachersByClass, $teachers): array {
                $id = (int) $teacherId;
                $duplicates = 0;
                $classTeacherConflicts = 0;

                foreach ($rows as $row) {
                    $key = $this->assignmentKey(
                        (int) $row->teacher_id,
                        (int) $row->class_id,
                        $row->subject_id !== null ? (int) $row->subject_id : null,
                        (bool) $row->is_class_teacher
                    );

                    if (isset($targetKeys[$key])) {
                        $duplicates++;
                    }

                    if ((bool) $row->is_class_teacher) {
                        $targetClassTeacher = $targetClassTeachersByClass->get((int) $row->class_id);
                        if (
                            $targetClassTeacher instanceof TeacherAssignment
                            && (int) $targetClassTeacher->teacher_id !== (int) $row->teacher_id
                        ) {
                            $classTeacherConflicts++;
                        }
                    }
                }

                return [
                    'teacher_id' => $id,
                    'teacher_name' => (string) ($teachers->get($id)?->user?->name ?? 'Unknown Teacher'),
                    'source_assignments' => $rows->count(),
                    'duplicates' => $duplicates,
                    'class_teacher_conflicts' => $classTeacherConflicts,
                ];
            })
            ->sortBy('teacher_name')
            ->values();

        return [
            'from_session' => trim($fromSession),
            'to_session' => trim($toSession),
            'teachers_count' => $sourceTeacherIds->count(),
            'assignment_count' => $sourceRows->count(),
            'duplicate_count' => (int) $teacherRows->sum('duplicates'),
            'class_teacher_conflicts' => (int) $teacherRows->sum('class_teacher_conflicts'),
            'target_existing_assignments' => $targetRows->count(),
            'teacher_rows' => $teacherRows,
        ];
    }

    /**
     * @param array<int, int>|null $teacherIds
     * @return array{
     *   copied_count:int,
     *   skipped_duplicates:int,
     *   overwritten_count:int,
     *   class_teacher_conflicts:int,
     *   affected_teachers_count:int
     * }
     */
    public function copyAssignmentsToNextSession(
        string $fromSession,
        string $toSession,
        ?array $teacherIds = null,
        bool $overwrite = false
    ): array {
        $normalizedTeacherIds = $this->normalizeTeacherIds($teacherIds);
        $sourceRows = $this->sourceRows($fromSession, $normalizedTeacherIds);

        $teacherIdsToProcess = $sourceRows
            ->pluck('teacher_id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        $summary = [
            'copied_count' => 0,
            'skipped_duplicates' => 0,
            'overwritten_count' => 0,
            'class_teacher_conflicts' => 0,
            'affected_teachers_count' => 0,
        ];

        DB::transaction(function () use (
            &$summary,
            $teacherIdsToProcess,
            $fromSession,
            $toSession,
            $overwrite
        ): void {
            foreach ($teacherIdsToProcess as $teacherId) {
                $result = $this->copySingleTeacherAssignments(
                    (int) $teacherId,
                    $fromSession,
                    $toSession,
                    $overwrite
                );

                $summary['copied_count'] += (int) ($result['copied_count'] ?? 0);
                $summary['skipped_duplicates'] += (int) ($result['skipped_duplicates'] ?? 0);
                $summary['overwritten_count'] += (int) ($result['overwritten_count'] ?? 0);
                $summary['class_teacher_conflicts'] += (int) ($result['class_teacher_conflicts'] ?? 0);
                $summary['affected_teachers_count'] += (int) ($result['had_source_assignments'] ?? false ? 1 : 0);
            }
        });

        return $summary;
    }

    /**
     * @return array{
     *   copied_count:int,
     *   skipped_duplicates:int,
     *   overwritten_count:int,
     *   class_teacher_conflicts:int,
     *   had_source_assignments:bool
     * }
     */
    public function copySingleTeacherAssignments(
        int $teacherId,
        string $fromSession,
        string $toSession,
        bool $overwrite = false
    ): array {
        $sourceRows = TeacherAssignment::query()
            ->where('teacher_id', $teacherId)
            ->where('session', trim($fromSession))
            ->orderByDesc('is_class_teacher')
            ->orderBy('class_id')
            ->orderBy('subject_id')
            ->get(['teacher_id', 'class_id', 'subject_id', 'is_class_teacher']);

        if ($sourceRows->isEmpty()) {
            return [
                'copied_count' => 0,
                'skipped_duplicates' => 0,
                'overwritten_count' => 0,
                'class_teacher_conflicts' => 0,
                'had_source_assignments' => false,
            ];
        }

        $resolvedToSession = trim($toSession);
        $copiedCount = 0;
        $skippedDuplicates = 0;
        $classTeacherConflicts = 0;
        $overwrittenCount = 0;

        if ($overwrite) {
            $targetRows = TeacherAssignment::query()
                ->where('teacher_id', $teacherId)
                ->where('session', $resolvedToSession)
                ->get(['id', 'class_id', 'is_class_teacher']);

            $overwrittenCount = $targetRows->count();
            $targetClassTeacherClassIds = $targetRows
                ->where('is_class_teacher', true)
                ->pluck('class_id')
                ->map(static fn ($id): int => (int) $id)
                ->filter(static fn (int $id): bool => $id > 0)
                ->unique()
                ->values();

            TeacherAssignment::query()
                ->where('teacher_id', $teacherId)
                ->where('session', $resolvedToSession)
                ->delete();

            if ($targetClassTeacherClassIds->isNotEmpty()) {
                SchoolClass::query()
                    ->whereIn('id', $targetClassTeacherClassIds->all())
                    ->where('class_teacher_id', $teacherId)
                    ->update(['class_teacher_id' => null]);
            }
        }

        $existingForTeacher = TeacherAssignment::query()
            ->where('teacher_id', $teacherId)
            ->where('session', $resolvedToSession)
            ->get(['teacher_id', 'class_id', 'subject_id', 'is_class_teacher']);
        $existingKeySet = $this->assignmentKeySet($existingForTeacher);

        $targetClassTeachersByClass = TeacherAssignment::query()
            ->where('session', $resolvedToSession)
            ->where('is_class_teacher', true)
            ->get(['class_id', 'teacher_id'])
            ->keyBy(fn (TeacherAssignment $row): int => (int) $row->class_id);

        foreach ($sourceRows as $row) {
            $classId = (int) $row->class_id;
            $subjectId = $row->subject_id !== null ? (int) $row->subject_id : null;
            $isClassTeacher = (bool) $row->is_class_teacher;

            $key = $this->assignmentKey($teacherId, $classId, $subjectId, $isClassTeacher);
            if (isset($existingKeySet[$key])) {
                $skippedDuplicates++;
                continue;
            }

            if ($isClassTeacher) {
                $targetClassTeacher = $targetClassTeachersByClass->get($classId);
                if (
                    $targetClassTeacher instanceof TeacherAssignment
                    && (int) $targetClassTeacher->teacher_id !== $teacherId
                ) {
                    $classTeacherConflicts++;
                    continue;
                }
            }

            TeacherAssignment::query()->create([
                'teacher_id' => $teacherId,
                'class_id' => $classId,
                'subject_id' => $subjectId,
                'is_class_teacher' => $isClassTeacher,
                'session' => $resolvedToSession,
            ]);

            $existingKeySet[$key] = true;
            $copiedCount++;

            if ($isClassTeacher) {
                SchoolClass::query()->whereKey($classId)->update(['class_teacher_id' => $teacherId]);
                $targetClassTeachersByClass->put($classId, new TeacherAssignment([
                    'class_id' => $classId,
                    'teacher_id' => $teacherId,
                    'is_class_teacher' => true,
                ]));
            }
        }

        return [
            'copied_count' => $copiedCount,
            'skipped_duplicates' => $skippedDuplicates,
            'overwritten_count' => $overwrittenCount,
            'class_teacher_conflicts' => $classTeacherConflicts,
            'had_source_assignments' => true,
        ];
    }

    /**
     * @param array<int, int>|null $teacherIds
     * @return Collection<int, TeacherAssignment>
     */
    private function sourceRows(string $fromSession, ?array $teacherIds = null): Collection
    {
        return TeacherAssignment::query()
            ->where('session', trim($fromSession))
            ->when(
                is_array($teacherIds) && ! empty($teacherIds),
                fn ($query) => $query->whereIn('teacher_id', $teacherIds)
            )
            ->get(['teacher_id', 'class_id', 'subject_id', 'is_class_teacher', 'session']);
    }

    /**
     * @param array<int, int>|null $teacherIds
     * @return array<int, int>|null
     */
    private function normalizeTeacherIds(?array $teacherIds): ?array
    {
        if (! is_array($teacherIds) || empty($teacherIds)) {
            return null;
        }

        $normalized = collect($teacherIds)
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        return empty($normalized) ? null : $normalized;
    }

    /**
     * @param Collection<int, TeacherAssignment> $rows
     * @return array<string, bool>
     */
    private function assignmentKeySet(Collection $rows): array
    {
        $set = [];
        foreach ($rows as $row) {
            $set[$this->assignmentKey(
                (int) $row->teacher_id,
                (int) $row->class_id,
                $row->subject_id !== null ? (int) $row->subject_id : null,
                (bool) $row->is_class_teacher
            )] = true;
        }

        return $set;
    }

    private function assignmentKey(int $teacherId, int $classId, ?int $subjectId, bool $isClassTeacher): string
    {
        return $teacherId.'|'.$classId.'|'.($subjectId ?? 0).'|'.($isClassTeacher ? 1 : 0);
    }
}

