<?php

namespace App\Modules\Results\Services;

use App\Models\{Exam, Mark, ReportComment, SchoolSetting, StudentResult};
use App\Services\FederalBoardGradingService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

class ResultCheckerCardService
{
    public function build(string $source, int $id, ?Collection $providedRecords = null, ?Collection $cohortRecords = null, ?Collection $comments = null): array
    {
        $published = $source === 'published';
        $record = $providedRecords?->first() ?? ($published
            ? StudentResult::with(['student', 'classRoom', 'exam'])->findOrFail($id)
            : Mark::with(['student', 'exam.classRoom'])->findOrFail($id));
        $exam = $record->exam;
        $classRoom = $published ? $record->classRoom : $exam?->classRoom;
        $type = $exam?->exam_type?->value;

        if ($providedRecords !== null) {
            $records = $providedRecords;
        } elseif ($published) {
            $records = StudentResult::with(['subject', 'exam.teacher.user'])
                ->where('student_id', $record->student_id)->where('session', $record->session)
                ->where('class_id', $record->class_id)->where('exam_name', $record->exam_name)
                ->when($type, fn ($query) => $query->whereHas('exam', fn ($examQuery) => $examQuery->where('exam_type', $type)))
                ->when(in_array($type, ['class_test', 'bimonthly_test'], true), fn ($query) => $query->where('exam_id', $record->exam_id))->get();
        } elseif ($exam) {
            // A class test or bimonthly is an individual assessment. Term cards
            // combine subjects only within the same class, session and label.
            $examIds = in_array($type, ['class_test', 'bimonthly_test'], true)
                ? collect([$exam->id])
                : Exam::where('class_id', $exam->class_id)->where('session', $exam->session)
                    ->where('exam_type', $type)->where('exam_label', $exam->exam_label)->pluck('id');
            $records = Mark::with(['exam.subject', 'exam.teacher.user'])->whereIn('exam_id', $examIds)
                ->where('session', $record->session)->where('student_id', $record->student_id)->get();
        } else {
            $records = collect([$record]);
        }

        $threshold = SchoolSetting::examPassPercentage();
        $grading = new FederalBoardGradingService();
        $rows = $records->map(function ($row) use ($published, $threshold, $grading) {
            $total = $row->total_marks ?? $row->exam?->total_marks;
            $gradeOnly = $row->exam?->marking_mode === 'grade'
                || ($row->obtained_marks === null && filled($row->grade));
            $numeric = ! $gradeOnly && $row->obtained_marks !== null && $total > 0;
            $percentage = $numeric ? ($row->obtained_marks / $total * 100) : null;
            $subject = $published ? $row->subject?->name : $row->exam?->subject?->name;
            $suggestion = match (true) {
                $percentage === null => 'Review the recorded grade with the teacher; numeric analysis is unavailable.',
                $percentage < $threshold => 'Review prerequisite skills and errors in the marked work. Use guided practice and reassess the same objectives in one to two weeks.',
                $percentage < 70 => 'Practise lower-scoring questions, explain each step, and check understanding with a short follow-up test.',
                default => 'Consolidate strengths with application questions and gradually increase challenge.',
            };
            return [
                'subject' => $subject ?? 'Subject unavailable',
                'subject_id' => $published ? $row->subject_id : $row->exam?->subject_id,
                'teacher' => $row->exam?->teacher?->user?->name ?? 'Teacher not recorded',
                'exam_id' => $row->exam_id,
                'total' => $numeric ? (float) $total : null,
                'obtained' => $numeric ? (float) $row->obtained_marks : null,
                'percentage' => $percentage,
                'grade' => $published || $gradeOnly ? ($row->grade ?: '—') : ($numeric ? $grading->grade($percentage) : '—'),
                'status' => $percentage === null ? 'Grade / incomplete record' : ($percentage >= $threshold ? 'Pass' : 'Below school threshold'),
                'suggestion' => $suggestion,
            ];
        })->sortBy('subject')->values();

        $numeric = $rows->whereNotNull('percentage');
        $total = $numeric->sum('total');
        $overall = $total > 0 ? $numeric->sum('obtained') / $total * 100 : null;
        $examIds = $rows->pluck('exam_id')->filter()->unique();
        // Use the same source as the displayed card: published snapshots are
        // never silently compared against marks that may since have changed.
        $cohort = $published ? StudentResult::query()->where('session', $record->session)
            ->where('class_id', $record->class_id)->where('exam_name', $record->exam_name)
            : Mark::query()->where('session', $record->session);
        $outcomes = ($cohortRecords !== null
            ? $cohortRecords->whereIn('exam_id', $examIds)->where('session', $record->session)
            : $cohort->whereIn('exam_id', $examIds)->get())->groupBy('exam_id');
        $teaching = $rows->filter(fn ($row) => $row['exam_id'])->unique('exam_id')->map(function ($row) use ($outcomes, $threshold) {
            $scores = collect($outcomes->get($row['exam_id'], collect()))
                ->filter(fn ($mark) => $row['percentage'] !== null && $mark->obtained_marks !== null && ($mark->total_marks ?? $row['total']) > 0)
                ->map(fn ($mark) => $mark->obtained_marks / ($mark->total_marks ?? $row['total']) * 100);
            $average = $scores->isNotEmpty() ? $scores->avg() : null;
            return [
                'subject' => $row['subject'], 'teacher' => $row['teacher'], 'exam_id' => $row['exam_id'], 'count' => $scores->count(),
                'average' => $average,
                'pass_rate' => $scores->isNotEmpty() ? $scores->filter(fn ($score) => $score >= $threshold)->count() / $scores->count() * 100 : null,
                'follow_up' => $average === null ? 'Numeric class outcomes are unavailable. Discuss the grade evidence with the teacher.'
                    : ($average < $threshold ? 'Review common misconceptions across the class; plan reteaching and a follow-up assessment.'
                        : ($average < 70 ? 'Use question-level error analysis to plan targeted practice for the class.'
                            : 'Continue effective practice and provide additional support to students below the threshold.')),
            ];
        })->values();

        $comment = $comments !== null
            ? $comments->first(fn ($item) => $item->student_id === $record->student_id && $item->session === $record->session && $item->exam_type === $type)
            : ($type && Schema::hasTable('report_comments')
            ? ReportComment::with('generatedBy.roles')->where('student_id', $record->student_id)
                ->where('session', $record->session)->where('exam_type', $type)->first()
            : null);
        $teacherComment = $comment?->is_edited && $comment->generatedBy?->hasRole('Teacher');
        $savedComment = trim((string) $comment?->final_comment);
        $commentGenerated = $savedComment === '';
        $displayComment = $commentGenerated ? $rows->map(function ($row) use ($teaching, $threshold) {
            $text = $row['subject'].': ';
            if ($row['percentage'] === null) {
                $text .= $row['grade'] !== '—' ? 'Recorded grade '.$row['grade'].'. ' : 'The result is incomplete. ';
                $text .= 'A numeric performance judgement cannot be made from this record. '.$row['suggestion'];
            } else {
                $text .= 'The student achieved '.number_format($row['percentage'], 2).'%';
                $text .= $row['percentage'] < $threshold
                    ? ', below the school pass threshold of '.number_format($threshold, 2).'%. '
                    : ', meeting the school pass threshold of '.number_format($threshold, 2).'%. ';
                $text .= $row['suggestion'];
            }
            $context = $teaching->firstWhere('exam_id', $row['exam_id']);
            if ($context && $context['count'] >= 2 && $row['percentage'] !== null) {
                $text .= "\nClass context: average ".number_format($context['average'], 2).'% and pass rate '
                    .number_format($context['pass_rate'], 2).'% across '.$context['count'].' numeric records for this assessment.';
                $text .= "\nSuggested teacher action: ".$context['follow_up'];
            } else {
                $text .= "\nThere are insufficient numeric class records for a class comparison. Review the marked work with the teacher before drawing broader conclusions.";
            }
            return $text;
        })->implode("\n\n") : $savedComment;

        return [
            'student' => $record->student, 'classRoom' => $classRoom,
            'session' => $record->session,
            'title' => $published ? $record->exam_name : ($exam?->exam_label ?: $exam?->exam_type?->label() ?? 'Assessment'),
            'date' => $exam?->exam_date?->format('d M Y'),
            'published' => $published, 'rows' => $rows, 'threshold' => $threshold,
            'overall' => $overall, 'total' => $total, 'obtained' => $numeric->sum('obtained'),
            'numericCount' => $numeric->count(), 'teaching' => $teaching,
            'comment' => $displayComment,
            'commentGenerated' => $commentGenerated,
            'commentLabel' => $commentGenerated ? 'Automatic comments for teacher review'
                : ($teacherComment ? 'Teacher comment' : ($comment?->is_edited ? 'Staff comment' : 'Saved automatic comment')),
            'commentAuthor' => ! $commentGenerated && $comment?->is_edited ? $comment->generatedBy?->name : null,
            'school' => SchoolSetting::cached(),
        ];
    }
}
