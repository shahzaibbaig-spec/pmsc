<?php

namespace App\Modules\Subjects\Services;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentSubject;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SubjectMatrixService
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

    public function matrix(int $classId, string $session): array
    {
        $classRoom = SchoolClass::query()->findOrFail($classId);

        $students = Student::query()
            ->where('class_id', $classRoom->id)
            ->orderBy('name')
            ->orderBy('student_id')
            ->get(['id', 'student_id', 'name', 'father_name']);

        $subjects = $classRoom->subjects()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['subjects.id', 'subjects.name', 'subjects.code', 'subjects.is_default']);

        if ($subjects->isEmpty()) {
            $subjects = Subject::query()
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'is_default']);
        }

        $subjectIds = $subjects->pluck('id');
        $studentIds = $students->pluck('id');

        $assignments = StudentSubject::query()
            ->where('session', $session)
            ->when($studentIds->isNotEmpty(), fn ($query) => $query->whereIn('student_id', $studentIds))
            ->when($subjectIds->isNotEmpty(), fn ($query) => $query->whereIn('subject_id', $subjectIds))
            ->get(['student_id', 'subject_id']);

        $assignmentMap = $assignments
            ->groupBy('student_id')
            ->map(fn (Collection $rows): array => $rows->pluck('subject_id')->map(fn ($id) => (int) $id)->values()->all());

        $studentsPayload = $students->map(function (Student $student) use ($assignmentMap): array {
            return [
                'id' => $student->id,
                'student_id' => $student->student_id,
                'name' => $student->name,
                'father_name' => $student->father_name,
                'assigned_subject_ids' => $assignmentMap->get($student->id, []),
            ];
        })->values()->all();

        return [
            'class' => [
                'id' => $classRoom->id,
                'name' => $classRoom->name,
                'section' => $classRoom->section,
                'display_name' => trim($classRoom->name.' '.($classRoom->section ?? '')),
            ],
            'session' => $session,
            'subjects' => $subjects->map(fn (Subject $subject): array => [
                'id' => $subject->id,
                'name' => $subject->name,
                'code' => $subject->code,
                'is_default' => (bool) $subject->is_default,
            ])->values()->all(),
            'students' => $studentsPayload,
        ];
    }

    public function toggle(int $classId, int $studentId, int $subjectId, string $session, bool $assigned): void
    {
        $student = Student::query()
            ->where('id', $studentId)
            ->where('class_id', $classId)
            ->first();

        if (! $student) {
            throw new RuntimeException('Student does not belong to the selected class.');
        }

        $subjectExists = Subject::query()->whereKey($subjectId)->exists();
        if (! $subjectExists) {
            throw new RuntimeException('Invalid subject selected.');
        }

        DB::transaction(function () use ($studentId, $subjectId, $session, $assigned): void {
            if ($assigned) {
                StudentSubject::query()->firstOrCreate([
                    'student_id' => $studentId,
                    'subject_id' => $subjectId,
                    'session' => $session,
                ]);

                return;
            }

            StudentSubject::query()
                ->where('student_id', $studentId)
                ->where('subject_id', $subjectId)
                ->where('session', $session)
                ->delete();
        });
    }

    public function bulkAssign(int $classId, int $subjectId, string $session, bool $assigned): int
    {
        $studentIds = Student::query()
            ->where('class_id', $classId)
            ->pluck('id');

        if ($studentIds->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($studentIds, $subjectId, $session, $assigned): int {
            if ($assigned) {
                $now = now();
                $rows = $studentIds->map(fn ($studentId): array => [
                    'student_id' => (int) $studentId,
                    'subject_id' => $subjectId,
                    'session' => $session,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                StudentSubject::query()->upsert(
                    $rows,
                    ['student_id', 'subject_id', 'session'],
                    ['updated_at']
                );

                return count($rows);
            }

            return StudentSubject::query()
                ->whereIn('student_id', $studentIds)
                ->where('subject_id', $subjectId)
                ->where('session', $session)
                ->delete();
        });
    }
}

