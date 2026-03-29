<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Models\TeacherAssignment;
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

        $cleanClassIds = collect($classIds)
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $cleanSubjectIds = collect($subjectIds)
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        DB::transaction(function () use (
            $teacherId,
            $session,
            $cleanClassIds,
            $cleanSubjectIds,
            $classTeacherClassId
        ): void {
            foreach ($cleanClassIds as $classId) {
                $this->assignSubjectsToClass($teacherId, $session, $classId, $cleanSubjectIds);
            }

            if ($classTeacherClassId !== null) {
                $this->assignClassTeacher($teacherId, $session, $classTeacherClassId);
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
        foreach ($subjectIds as $subjectId) {
            $subjectId = (int) $subjectId;
            if ($subjectId <= 0) {
                continue;
            }

            $assignment = TeacherAssignment::query()->firstOrCreate([
                'teacher_id' => $teacherId,
                'class_id' => $classId,
                'subject_id' => $subjectId,
                'session' => $session,
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
        $class = SchoolClass::query()->findOrFail($classId);

        $existingClassTeacher = TeacherAssignment::query()
            ->where('class_id', $classId)
            ->where('session', $session)
            ->where('is_class_teacher', true)
            ->first();

        if ($existingClassTeacher !== null) {
            if ((int) $existingClassTeacher->teacher_id === $teacherId) {
                return;
            }

            $classLabel = $class->name ?? ('Class '.$classId);

            throw ValidationException::withMessages([
                'class_teacher_class_id' => $classLabel.' already has a class teacher for session '.$session.'.',
            ]);
        }

        TeacherAssignment::query()->create([
            'teacher_id' => $teacherId,
            'class_id' => $classId,
            'subject_id' => null,
            'is_class_teacher' => true,
            'session' => $session,
        ]);

        SchoolClass::query()
            ->whereKey($classId)
            ->update(['class_teacher_id' => $teacherId]);

        $this->classTeacherAssigned = true;
    }

    public function removeAssignment(int $assignmentId): void
    {
        $assignment = TeacherAssignment::query()->findOrFail($assignmentId);
        $assignment->delete();
    }
}
