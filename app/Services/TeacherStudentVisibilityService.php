<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentSubject;
use App\Models\StudentSubjectAssignment;
use App\Models\TeacherAssignment;
use Illuminate\Support\Collection;

class TeacherStudentVisibilityService
{
    public function getVisibleStudentsForSubjectTeacher(
        int $teacherId,
        int $classId,
        int $subjectId,
        string $session
    ): Collection {
        if (! $this->teacherHasAssignment($teacherId, $classId, $subjectId, $session)) {
            return collect();
        }

        $class = SchoolClass::query()->find($classId);
        if (! $class) {
            return collect();
        }

        $query = Student::query()
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->orderBy('name')
            ->orderBy('student_id')
            ->select(['id', 'student_id', 'name', 'father_name', 'class_id', 'status']);

        if ($this->classRequiresSubjectFiltering($class)) {
            $studentIds = $this->subjectStudentIdsForClassSession($classId, $subjectId, $session);
            if ($studentIds->isEmpty()) {
                return collect();
            }

            $query->whereIn('id', $studentIds);
        }

        return $query->get();
    }

    public function teacherCanAccessStudentForSubject(
        int $teacherId,
        int $studentId,
        int $subjectId,
        string $session
    ): bool {
        $student = Student::query()->find($studentId);
        if (! $student) {
            return false;
        }

        $classId = (int) $student->class_id;
        if ($classId <= 0) {
            return false;
        }

        if (! $this->teacherHasAssignment($teacherId, $classId, $subjectId, $session)) {
            return false;
        }

        if (! $this->classRequiresSubjectFiltering($classId)) {
            return true;
        }

        return $this->subjectStudentIdsForClassSession($classId, $subjectId, $session)
            ->contains($studentId);
    }

    public function classRequiresSubjectFiltering($class): bool
    {
        $classModel = $class;

        if (is_numeric($class)) {
            $classModel = SchoolClass::query()->find((int) $class);
        }

        if (! $classModel instanceof SchoolClass) {
            return false;
        }

        $name = strtoupper(trim((string) $classModel->name));
        if ($name === '') {
            return false;
        }

        if (preg_match('/\b(XII|XI|IX|X)\b/', $name, $romanMatch) === 1) {
            $romanToNumber = [
                'IX' => 9,
                'X' => 10,
                'XI' => 11,
                'XII' => 12,
            ];
            $romanValue = $romanToNumber[$romanMatch[1]] ?? null;

            return in_array($romanValue, [9, 10, 11, 12], true);
        }

        if (preg_match('/\d+/', $name, $digitMatch) === 1) {
            $level = (int) $digitMatch[0];

            return in_array($level, [9, 10, 11, 12], true);
        }

        return false;
    }

    private function teacherHasAssignment(int $teacherId, int $classId, int $subjectId, string $session): bool
    {
        return TeacherAssignment::query()
            ->where('teacher_id', $teacherId)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('session', $session)
            ->exists();
    }

    private function subjectStudentIdsForClassSession(int $classId, int $subjectId, string $session): Collection
    {
        $fromMatrixAssignments = StudentSubjectAssignment::query()
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('session', $session)
            ->pluck('student_id')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($fromMatrixAssignments->isNotEmpty()) {
            return $fromMatrixAssignments;
        }

        return StudentSubject::query()
            ->where('subject_id', $subjectId)
            ->where('session', $session)
            ->whereHas('student', function ($query) use ($classId): void {
                $query->where('class_id', $classId);
            })
            ->pluck('student_id')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values();
    }
}
