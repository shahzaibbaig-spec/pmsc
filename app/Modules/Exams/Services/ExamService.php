<?php

namespace App\Modules\Exams\Services;

use App\Models\Exam;
use App\Models\Mark;
use App\Models\Student;
use App\Models\StudentSubject;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Modules\Exams\Enums\ExamType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ExamService
{
    public function optionsForTeacher(int $userId): array
    {
        $teacher = $this->resolveTeacher($userId);
        if (! $teacher) {
            return [
                'sessions' => [],
                'assignments' => [],
                'exam_types' => ExamType::options(),
            ];
        }

        $assignments = TeacherAssignment::query()
            ->with([
                'classRoom:id,name,section',
                'subject:id,name,code',
            ])
            ->where('teacher_id', $teacher->id)
            ->whereNotNull('subject_id')
            ->orderByDesc('session')
            ->orderBy('class_id')
            ->get(['id', 'class_id', 'subject_id', 'session']);

        $classIds = $assignments->pluck('class_id')->unique()->values();
        $classActiveStudentCount = Student::query()
            ->whereIn('class_id', $classIds)
            ->where('status', 'active')
            ->selectRaw('class_id, count(*) as total')
            ->groupBy('class_id')
            ->pluck('total', 'class_id');

        $subjectScopedCounts = StudentSubject::query()
            ->join('students', 'students.id', '=', 'student_subjects.student_id')
            ->whereIn('students.class_id', $classIds)
            ->where('students.status', 'active')
            ->selectRaw('students.class_id, student_subjects.subject_id, student_subjects.session, count(*) as total')
            ->groupBy('students.class_id', 'student_subjects.subject_id', 'student_subjects.session')
            ->get();

        $subjectScopedCountMap = $subjectScopedCounts
            ->mapWithKeys(function ($row): array {
                $key = $row->class_id.'|'.$row->subject_id.'|'.$row->session;

                return [$key => (int) $row->total];
            });

        return [
            'sessions' => $assignments->pluck('session')->unique()->values()->all(),
            'assignments' => $assignments->map(function (TeacherAssignment $assignment) use ($classActiveStudentCount, $subjectScopedCountMap): array {
                $subjectKey = $assignment->class_id.'|'.$assignment->subject_id.'|'.$assignment->session;

                return [
                    'class_id' => $assignment->class_id,
                    'class_name' => trim(($assignment->classRoom?->name ?? '').' '.($assignment->classRoom?->section ?? '')),
                    'subject_id' => $assignment->subject_id,
                    'subject_name' => $assignment->subject?->name ?? '',
                    'session' => $assignment->session,
                    'class_students' => (int) ($classActiveStudentCount->get($assignment->class_id) ?? 0),
                    'subject_students' => (int) ($subjectScopedCountMap->get($subjectKey) ?? 0),
                ];
            })->values()->all(),
            'exam_types' => ExamType::options(),
        ];
    }

    public function sheet(int $userId, int $classId, int $subjectId, string $session, string $examType): array
    {
        $teacher = $this->resolveTeacherOrFail($userId);
        $this->ensureTeacherAssignment($teacher->id, $classId, $subjectId, $session);

        $exam = Exam::query()
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('session', $session)
            ->where('exam_type', $examType)
            ->first();

        $students = $this->studentsForExam($classId, $subjectId, $session);
        $studentIds = $students->pluck('id');

        $marksMap = collect();
        if ($exam && $studentIds->isNotEmpty()) {
            $marksMap = Mark::query()
                ->where('exam_id', $exam->id)
                ->whereIn('student_id', $studentIds)
                ->get(['student_id', 'obtained_marks'])
                ->pluck('obtained_marks', 'student_id');
        }

        return [
            'exam' => [
                'id' => $exam?->id,
                'total_marks' => $exam?->total_marks,
                'locked' => $exam ? $this->isExamLocked($exam) : false,
                'locked_message' => ($exam && $this->isExamLocked($exam)) ? 'This exam is locked. Edit window (7 days) has expired.' : null,
            ],
            'students' => $students->map(function (Student $student) use ($marksMap): array {
                return [
                    'id' => $student->id,
                    'student_id' => $student->student_id,
                    'name' => $student->name,
                    'father_name' => $student->father_name,
                    'obtained_marks' => $marksMap->has($student->id) ? (int) $marksMap->get($student->id) : null,
                ];
            })->values()->all(),
        ];
    }

    public function saveMarks(
        int $userId,
        int $classId,
        int $subjectId,
        string $session,
        string $examType,
        int $totalMarks,
        array $records
    ): void {
        $teacher = $this->resolveTeacherOrFail($userId);
        $this->ensureTeacherAssignment($teacher->id, $classId, $subjectId, $session);

        $allowedStudents = $this->studentsForExam($classId, $subjectId, $session)->keyBy('id');
        if ($allowedStudents->isEmpty()) {
            throw new RuntimeException('No students found for this class/subject/session.');
        }

        $recordStudentIds = collect($records)->pluck('student_id')->map(fn ($id) => (int) $id)->unique();
        if ($recordStudentIds->diff($allowedStudents->keys())->isNotEmpty()) {
            throw new RuntimeException('Invalid student records submitted for this exam sheet.');
        }

        DB::transaction(function () use ($teacher, $classId, $subjectId, $session, $examType, $totalMarks, $records): void {
            $exam = Exam::query()
                ->where('class_id', $classId)
                ->where('subject_id', $subjectId)
                ->where('session', $session)
                ->where('exam_type', $examType)
                ->first();

            if (! $exam) {
                $exam = Exam::query()->create([
                    'class_id' => $classId,
                    'subject_id' => $subjectId,
                    'exam_type' => $examType,
                    'session' => $session,
                    'total_marks' => $totalMarks,
                    'teacher_id' => $teacher->id,
                ]);
            } else {
                if ($this->isExamLocked($exam)) {
                    throw new RuntimeException('Exam is locked after 7 days. You cannot edit marks.');
                }

                if ((int) $exam->total_marks !== $totalMarks) {
                    throw new RuntimeException('Total marks are already set for this exam and cannot be changed.');
                }
            }

            foreach ($records as $row) {
                $studentId = (int) $row['student_id'];
                $obtainedRaw = $row['obtained_marks'];

                if ($obtainedRaw === null || $obtainedRaw === '') {
                    Mark::query()
                        ->where('exam_id', $exam->id)
                        ->where('student_id', $studentId)
                        ->delete();
                    continue;
                }

                $obtained = (int) round((float) $obtainedRaw);
                if ($obtained < 0 || $obtained > $totalMarks) {
                    throw new RuntimeException('Obtained marks must be between 0 and total marks.');
                }

                $existingMark = Mark::query()
                    ->where('exam_id', $exam->id)
                    ->where('student_id', $studentId)
                    ->first();

                if ($existingMark && $existingMark->created_at && $existingMark->created_at->lt(now()->subDays(7))) {
                    throw new RuntimeException('Some marks are older than 7 days and cannot be edited.');
                }

                Mark::query()->updateOrCreate(
                    [
                        'exam_id' => $exam->id,
                        'student_id' => $studentId,
                    ],
                    [
                        'obtained_marks' => $obtained,
                        'total_marks' => $totalMarks,
                        'teacher_id' => $teacher->id,
                        'session' => $session,
                    ]
                );
            }

            if (! $exam->locked_at && $exam->created_at && $exam->created_at->lt(now()->subDays(7))) {
                $exam->forceFill(['locked_at' => now()])->save();
            }
        });
    }

    private function resolveTeacher(int $userId): ?Teacher
    {
        return Teacher::query()->where('user_id', $userId)->first();
    }

    private function resolveTeacherOrFail(int $userId): Teacher
    {
        $teacher = $this->resolveTeacher($userId);
        if (! $teacher) {
            throw new RuntimeException('Teacher profile not found.');
        }

        return $teacher;
    }

    private function ensureTeacherAssignment(int $teacherId, int $classId, int $subjectId, string $session): void
    {
        $allowed = TeacherAssignment::query()
            ->where('teacher_id', $teacherId)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('session', $session)
            ->exists();

        if (! $allowed) {
            throw new RuntimeException('You are not assigned to this class/subject/session.');
        }
    }

    private function studentsForExam(int $classId, int $subjectId, string $session): Collection
    {
        $subjectAssignedStudentIds = StudentSubject::query()
            ->where('subject_id', $subjectId)
            ->where('session', $session)
            ->whereHas('student', function ($query) use ($classId): void {
                $query->where('class_id', $classId)
                    ->where('status', 'active');
            })
            ->pluck('student_id');

        $query = Student::query()
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->orderBy('name')
            ->orderBy('student_id')
            ->select(['id', 'student_id', 'name', 'father_name']);

        // If class-specific subject assignments exist for this session, limit to those students.
        // Otherwise, fall back to all active students of the class to keep marks entry usable.
        if ($subjectAssignedStudentIds->isNotEmpty()) {
            $query->whereIn('id', $subjectAssignedStudentIds);
        }

        return $query->get();
    }

    private function isExamLocked(Exam $exam): bool
    {
        if ($exam->locked_at !== null) {
            return true;
        }

        $createdAt = $exam->created_at instanceof Carbon ? $exam->created_at : Carbon::parse($exam->created_at);

        return $createdAt->lt(now()->subDays(7));
    }
}
