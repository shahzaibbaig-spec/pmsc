<?php

namespace App\Modules\Results\Services;

use App\Models\Exam;
use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Services\AssessmentMarkingModeService;
use Illuminate\Support\Collection;
use Carbon\CarbonImmutable;

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

    public function sheet(int $classId, string $session, ?int $studentId = null, ?string $month = null): array
    {
        $class = SchoolClass::query()->findOrFail($classId);
        $students = $this->students($classId, $session);
        if ($studentId !== null) {
            abort_unless($students->contains('id', $studentId), 422, 'Select a student from this class and session.');
            $students = $students->where('id', $studentId)->values();
        }

        $allExams = Exam::query()->with('subject:id,name')->where('class_id', $classId)
            ->where('session', $session)->where('exam_type', 'class_test')
            ->orderBy('exam_date')->orderBy('id')->get()
            ->sortBy(fn (Exam $exam) => $exam->subject?->name, SORT_NATURAL | SORT_FLAG_CASE)->values();
        $months = $allExams->filter(fn (Exam $exam) => $exam->exam_date !== null)
            ->map(fn (Exam $exam) => $exam->exam_date->format('Y-m'))->unique()->sortDesc()->values()
            ->map(fn (string $value) => ['value' => $value, 'label' => CarbonImmutable::parse($value.'-01')->format('F Y')]);
        $exams = $month ? $allExams->filter(fn (Exam $exam) => $exam->exam_date?->format('Y-m') === $month)->values() : $allExams;
        $modeService = app(AssessmentMarkingModeService::class);
        $numericExamIds = $allExams->filter(fn (Exam $exam) => $modeService->resolveMarkingMode($exam, $class) === AssessmentMarkingModeService::MODE_NUMERIC)->pluck('id')->all();
        $columns = $exams->map(fn (Exam $exam) => [
            'id' => $exam->id,
            'subject' => $exam->subject?->name ?? 'Subject',
            'label' => $exam->display_name,
            'topic' => $exam->topic,
            'date' => $exam->exam_date?->format('d M Y'),
            'total' => $exam->total_marks,
            'grade_mode' => $modeService->resolveMarkingMode($exam, $class) === AssessmentMarkingModeService::MODE_GRADE,
        ]);
        $marks = Mark::query()->whereIn('exam_id', $allExams->pluck('id'))
            ->whereIn('student_id', $students->pluck('id'))->get()
            ->groupBy('student_id')->map(fn ($items) => $items->keyBy('exam_id'));

        $rows = $students->map(function (Student $student) use ($columns, $marks, $allExams, $numericExamIds, $month) {
            $cells = [];
            $percentageCells = [];
            $obtained = $total = $recorded = 0;
            foreach ($columns as $column) {
                $mark = $marks->get($student->id)?->get($column['id']);
                $value = $column['grade_mode'] ? $mark?->grade : $mark?->obtained_marks;
                if ($value === null || $value === '') {
                    $cells[] = '—';
                    $percentageCells[] = '—';
                    continue;
                }
                $recorded++;
                if ($column['grade_mode']) {
                    $cells[] = (string) $value;
                    $percentageCells[] = (string) $value;
                } else {
                    $maximum = (int) ($mark->total_marks ?? $column['total']);
                    $cells[] = $value.' / '.$maximum;
                    $percentageCells[] = $maximum > 0 ? number_format($value / $maximum * 100, 2).'%' : 'N/A';
                    $obtained += (int) $value;
                    $total += $maximum;
                }
            }

            return ['student' => $student, 'cells' => $cells, 'percentage_cells' => $percentageCells, 'recorded' => $recorded,
                'obtained' => $obtained, 'total' => $total,
                'percentage' => $total > 0 ? round($obtained / $total * 100, 2) : null,
                'progress' => $this->progress($allExams, $marks->get($student->id, collect()), $numericExamIds, $month)];
        });

        $periodLabel = $month ? CarbonImmutable::parse($month.'-01')->format('F Y') : 'All tests';

        return compact('class', 'session', 'columns', 'rows', 'months', 'periodLabel');
    }

    private function progress(Collection $exams, Collection $marks, array $numericExamIds, ?string $month): array
    {
        $scores = [];
        $names = [];
        foreach ($exams as $exam) {
            $mark = $marks->get($exam->id);
            $total = $mark?->total_marks ?? $exam->total_marks;
            if (! $exam->exam_date || ! in_array($exam->id, $numericExamIds, true) || $mark?->obtained_marks === null || $total <= 0) {
                continue;
            }
            $period = $exam->exam_date->format('Y-m');
            $scores[$period][$exam->subject_id][] = $mark->obtained_marks / $total * 100;
            $names[$exam->subject_id] = $exam->subject?->name ?? 'Subject';
        }
        ksort($scores);
        $periods = array_keys($scores);
        $current = $month ?? ($periods[count($periods) - 1] ?? null);
        $previous = $month ? CarbonImmutable::parse($month.'-01')->subMonth()->format('Y-m') : ($periods[count($periods) - 2] ?? null);
        $subjects = [];
        foreach ($scores[$current] ?? [] as $subjectId => $values) {
            if (! isset($scores[$previous][$subjectId])) {
                continue;
            }
            $before = array_sum($scores[$previous][$subjectId]) / count($scores[$previous][$subjectId]);
            $after = array_sum($values) / count($values);
            $subjects[] = ['name' => $names[$subjectId], 'before' => round($before, 2), 'after' => round($after, 2),
                'change' => round($after - $before, 2)];
        }
        $change = count($subjects) ? round(collect($subjects)->avg('change'), 2) : null;

        return [
            'status' => $change === null ? 'Not enough data' : ($change > 0 ? 'Improved' : ($change < 0 ? 'Declined' : 'Steady')),
            'change' => $change,
            'previous' => $previous ? CarbonImmutable::parse($previous.'-01')->format('M Y') : null,
            'current' => $current ? CarbonImmutable::parse($current.'-01')->format('M Y') : null,
            'subjects' => $subjects,
        ];
    }
}
