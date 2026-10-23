<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\Mark;
use App\Models\ReportComment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\TeacherAssignment;
use App\Modules\Results\Services\ResultService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SeniorProgressReportService
{
    public static function enabled(): bool
    {
        return (bool) config('senior_progress.enabled')
            && strtolower(trim((string) config('senior_progress.campus'))) === 'mirpur';
    }

    public static function seniorClass(string $name): bool
    {
        return (bool) preg_match('/^(?:(?:grade|class)\s*)?(?:7|8|9|10|11|12|seven|seventh|eight|eighth|nine|ninth|ten|tenth|eleven|eleventh|twelve|twelfth|vii|viii|ix|x|xi|xii|1st\s+year|2nd\s+year|first\s+year|second\s+year)(?:\s*[- ]\s*[a-z]+)?$/i', trim($name));
    }

    public function classes(): Collection
    {
        return SchoolClass::query()->orderBy('name')->orderBy('section')->get()
            ->filter(fn (SchoolClass $class) => self::seniorClass($class->name))->values();
    }

    public function scopes(int $classId, string $session): Collection
    {
        return Exam::query()->where('class_id', $classId)->where('session', $session)
            ->orderBy('id')->get()
            ->unique(fn (Exam $exam) => $this->scopeKey($exam))
            ->map(fn (Exam $exam) => [
                'id' => $exam->id,
                'label' => $exam->display_name.' ('.($exam->exam_type?->label() ?? '').')'
                    .($exam->exam_group ? ' · '.$exam->exam_group : ''),
            ])->values();
    }

    public function roster(int $classId, string $session): Collection
    {
        // Session history takes priority; current class is used only without history for that session.
        return Student::query()->where(function ($query) use ($classId, $session) {
            $query->whereIn('id', StudentClassHistory::query()->where('session', $session)
                ->where('class_id', $classId)->select('student_id'))
                ->orWhere(function ($query) use ($classId, $session) {
                    $query->where('class_id', $classId)->where('status', 'active')->whereNotIn('id',
                        StudentClassHistory::query()->where('session', $session)->select('student_id'));
                });
        })->orderBy('name')->get();
    }

    public function scope(int $classId, string $session, int $examId): Exam
    {
        $exam = Exam::query()->where('class_id', $classId)->where('session', $session)->findOrFail($examId);

        return Exam::query()->where('class_id', $classId)->where('session', $session)
            ->where('exam_type', $exam->getRawOriginal('exam_type'))
            ->where('exam_group', $exam->exam_group)
            ->where('exam_label', $exam->exam_label)->where('sequence_number', $exam->sequence_number)
            ->orderBy('id')->firstOrFail();
    }

    public function report(Student $student, SchoolClass $class, string $session, Exam $scope): array
    {
        $marks = Mark::query()->with('exam.subject')->where('student_id', $student->id)
            ->where('session', $session)->whereHas('exam', function ($query) use ($class, $session, $scope) {
                $query->where('class_id', $class->id)->where('session', $session)
                    ->where('exam_type', $scope->getRawOriginal('exam_type'))
                    ->where('exam_group', $scope->exam_group)
                    ->where('exam_label', $scope->exam_label)
                    ->where('sequence_number', $scope->sequence_number);
            })->get();

        if ($marks->groupBy('exam.subject_id')->contains(fn ($rows) => $rows->count() > 1)) {
            throw ValidationException::withMessages(['exam_id' => 'This assessment contains multiple entries for a subject. Resolve the duplicates before exporting.']);
        }

        $subjects = $marks->map(function (Mark $mark) {
            $grade = trim((string) $mark->grade);
            if ($grade === '' && $mark->obtained_marks !== null && $mark->total_marks > 0) {
                $grade = app(ResultService::class)->computeGrade(round($mark->obtained_marks / $mark->total_marks * 100, 2));
            }

            return ['name' => $mark->exam?->subject?->name ?? '', 'grade' => $grade];
        })->sortBy('name')->values()->all();

        if (count($subjects) > 10) {
            throw ValidationException::withMessages(['exam_id' => 'The supplied Word format has ten subject rows. This student has more than ten subjects; no subjects have been omitted.']);
        }

        $teacher = TeacherAssignment::query()->with('teacher.user')->where('class_id', $class->id)
            ->where('session', $session)->where('is_class_teacher', true)->first()?->teacher?->user?->name;
        $teacher ??= $class->loadMissing('classTeacher.user')->classTeacher?->user?->name;
        $remarks = '';
        if (Schema::hasTable('report_comments')) {
            $remarks = (string) (ReportComment::query()->where('student_id', $student->id)
                ->where('session', $session)->where('exam_type', $scope->getRawOriginal('exam_type'))
                ->latest('id')->first()?->final_comment ?? '');
        }

        return [
            'student_name' => $student->name,
            'student_number' => $student->student_id,
            'class_name' => trim($class->name.' '.$class->section),
            'age' => $student->date_of_birth ? (string) $student->date_of_birth->age : (string) ($student->age ?? ''),
            'teacher_name' => $teacher ?? '',
            'session' => $session,
            'assessment' => $scope->display_name,
            'subjects' => $subjects,
            'teacher_remarks' => $remarks,
            'head_remarks' => '',
            'skills' => [],
        ];
    }

    private function scopeKey(Exam $exam): string
    {
        return json_encode([$exam->getRawOriginal('exam_type'), $exam->exam_group, $exam->exam_label, $exam->sequence_number]);
    }
}
