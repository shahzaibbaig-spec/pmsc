<?php

namespace App\Services;

use App\Models\{Exam, Mark, SchoolClass, Student, StudentSubject, StudentSubjectAssignment, TeacherAssignment};
use Illuminate\Support\Facades\Schema;

class ResultCompletionService
{
    public function __construct(
        private readonly TeacherStudentVisibilityService $visibility,
        private readonly AssessmentMarkingModeService $modes,
        private readonly ClassAssessmentModeService $grades,
    ) {}

    public function report(array $filters): array
    {
        $session = $filters['session'];
        $assignments = TeacherAssignment::with(['classRoom', 'subject', 'teacher.user'])->where('session', $session)
            ->whereNotNull('subject_id')->get()->unique(fn ($item) => $item->teacher_id.':'.$item->class_id.':'.$item->subject_id);
        $students = Student::where('status', 'active')->whereIn('class_id', $assignments->pluck('class_id'))->get(['id', 'class_id']);
        $rosters = $students->groupBy('class_id');
        $matrix = Schema::hasTable('student_subject_assignments')
            ? StudentSubjectAssignment::where('session', $session)->get(['student_id', 'class_id', 'subject_id'])
                ->groupBy(fn ($item) => $item->class_id.':'.$item->subject_id) : collect();
        $legacy = Schema::hasTable('student_subjects')
            ? StudentSubject::where('session', $session)->whereIn('student_id', $students->pluck('id'))->get(['student_id', 'subject_id'])->groupBy('subject_id')
            : collect();
        $exams = Exam::where('session', $session)->where('exam_type', $filters['exam_type'])
            ->when($filters['exam_type'] === 'bimonthly_test', fn ($query) => $query->where('sequence_number', $filters['sequence_number']))
            ->when($filters['exam_type'] === 'class_test', fn ($query) => $query
                ->whereRaw('LOWER(topic) = ?', [mb_strtolower($filters['topic'])])->whereDate('exam_date', $filters['exam_date']))
            ->get()->groupBy(fn ($item) => $item->teacher_id.':'.$item->class_id.':'.$item->subject_id);
        $marks = Mark::where('session', $session)->whereIn('exam_id', $exams->flatten()->pluck('id'))->get()->groupBy('exam_id');
        $rows = $assignments->map(function ($assignment) use ($rosters, $matrix, $legacy, $exams, $marks) {
            $eligible = $rosters->get($assignment->class_id, collect())->pluck('id');
            if ($this->visibility->classRequiresSubjectFiltering($assignment->classRoom)) {
                $subjectRoster = $matrix->get($assignment->class_id.':'.$assignment->subject_id, collect());
                $subjectIds = $subjectRoster->isNotEmpty() ? $subjectRoster->pluck('student_id')
                    : $legacy->get($assignment->subject_id, collect())->pluck('student_id');
                $eligible = $eligible->intersect($subjectIds)->unique();
            }
            $scope = $exams->get($assignment->teacher_id.':'.$assignment->class_id.':'.$assignment->subject_id, collect());
            $completed = collect();
            $latest = null;
            foreach ($scope as $exam) {
                $gradeMode = $this->modes->resolveMarkingMode($exam, $assignment->classRoom) === AssessmentMarkingModeService::MODE_GRADE;
                foreach ($marks->get($exam->id, collect()) as $mark) {
                    if (! $eligible->contains($mark->student_id)) {
                        continue;
                    }
                    $total = $mark->total_marks ?? $exam->total_marks;
                    $valid = $gradeMode ? $this->grades->isValidGrade($mark->grade)
                        : $mark->obtained_marks !== null && $total > 0 && $mark->obtained_marks >= 0 && $mark->obtained_marks <= $total;
                    if ($valid) {
                        $completed->push($mark->student_id);
                        if ($mark->updated_at !== null && ($latest === null || $mark->updated_at->gt($latest))) {
                            $latest = $mark->updated_at;
                        }
                    }
                }
            }
            $expected = $eligible->count();
            $entered = $completed->unique()->count();
            return [
                'class_id' => $assignment->class_id,
                'class' => trim(($assignment->classRoom?->name ?? '').' '.($assignment->classRoom?->section ?? '')),
                'subject' => $assignment->subject?->name ?? 'Subject unavailable',
                'teacher' => $assignment->teacher?->user?->name ?? 'Teacher unavailable',
                'teacher_id' => $assignment->teacher_id,
                'expected' => $expected, 'entered' => $entered, 'missing' => $expected - $entered,
                'percentage' => $expected > 0 ? round($entered / $expected * 100, 2) : null,
                'status' => $expected === 0 ? 'No eligible students' : ($entered === 0 ? 'Not started' : ($entered < $expected ? 'Incomplete' : 'Complete')),
                'latest' => $latest,
            ];
        })->sortBy(fn ($row) => $row['class'].' '.$row['subject'].' '.$row['teacher'])->values();
        $classes = SchoolClass::orderBy('name')->orderBy('section')->get()->map(function ($class) use ($rows) {
            $items = $rows->where('class_id', $class->id);
            $expected = $items->sum('expected');
            $entered = $items->sum('entered');
            return ['id' => $class->id, 'name' => trim($class->name.' '.$class->section), 'expected' => $expected,
                'entered' => $entered, 'missing' => $expected - $entered,
                'percentage' => $expected > 0 ? round($entered / $expected * 100, 2) : null,
                'assignment_count' => $items->count(), 'pending' => $items->where('missing', '>', 0)->count()];
        });
        $expected = $rows->sum('expected');
        $entered = $rows->sum('entered');
        return ['classes' => $classes, 'rows' => $rows, 'overall' => [
            'expected' => $expected, 'entered' => $entered, 'missing' => $expected - $entered,
            'percentage' => $expected > 0 ? round($entered / $expected * 100, 2) : null,
        ]];
    }
}
