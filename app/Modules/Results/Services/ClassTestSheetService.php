<?php

namespace App\Modules\Results\Services;

use App\Models\Exam;
use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Services\AssessmentMarkingModeService;
use Illuminate\Support\Collection;

class ClassTestSheetService
{
    public function students(int $classId, string $session): Collection
    {
        $ids = StudentClassHistory::query()->where('class_id', $classId)
            ->where('session', $session)->pluck('student_id');

        return ($ids->isNotEmpty()
            ? Student::query()->whereIn('id', $ids)
            : Student::query()->where('class_id', $classId)->where('status', 'active'))
            ->orderBy('name')->orderBy('student_id')->get(['id', 'name', 'student_id']);
    }

    public function sheet(int $classId, string $session, ?int $studentId = null): array
    {
        $class = SchoolClass::query()->findOrFail($classId);
        $students = $this->students($classId, $session);
        if ($studentId !== null) {
            abort_unless($students->contains('id', $studentId), 422, 'Select a student from this class and session.');
            $students = $students->where('id', $studentId)->values();
        }

        $exams = Exam::query()->with('subject:id,name')->where('class_id', $classId)
            ->where('session', $session)->where('exam_type', 'class_test')
            ->orderBy('exam_date')->orderBy('id')->get()
            ->sortBy(fn (Exam $exam) => $exam->subject?->name, SORT_NATURAL | SORT_FLAG_CASE)->values();
        $modeService = app(AssessmentMarkingModeService::class);
        $columns = $exams->map(fn (Exam $exam) => [
            'id' => $exam->id,
            'subject' => $exam->subject?->name ?? 'Subject',
            'label' => $exam->display_name,
            'topic' => $exam->topic,
            'date' => $exam->exam_date?->format('d M Y'),
            'total' => $exam->total_marks,
            'grade_mode' => $modeService->resolveMarkingMode($exam, $class) === AssessmentMarkingModeService::MODE_GRADE,
        ]);
        $marks = Mark::query()->whereIn('exam_id', $exams->pluck('id'))
            ->whereIn('student_id', $students->pluck('id'))->get()
            ->groupBy('student_id')->map(fn ($items) => $items->keyBy('exam_id'));

        $rows = $students->map(function (Student $student) use ($columns, $marks) {
            $cells = [];
            $obtained = $total = $recorded = 0;
            foreach ($columns as $column) {
                $mark = $marks->get($student->id)?->get($column['id']);
                $value = $column['grade_mode'] ? $mark?->grade : $mark?->obtained_marks;
                if ($value === null || $value === '') {
                    $cells[] = '—';
                    continue;
                }
                $recorded++;
                if ($column['grade_mode']) {
                    $cells[] = (string) $value;
                } else {
                    $maximum = (int) ($mark->total_marks ?? $column['total']);
                    $cells[] = $value.' / '.$maximum;
                    $obtained += (int) $value;
                    $total += $maximum;
                }
            }

            return ['student' => $student, 'cells' => $cells, 'recorded' => $recorded,
                'obtained' => $obtained, 'total' => $total,
                'percentage' => $total > 0 ? round($obtained / $total * 100, 2) : null];
        });

        return compact('class', 'session', 'columns', 'rows');
    }
}
