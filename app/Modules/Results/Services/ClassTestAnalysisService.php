<?php

namespace App\Modules\Results\Services;

use Illuminate\Support\Collection;

class ClassTestAnalysisService
{
    public function analyse(Collection $columns, Collection $marks, array $progress): array
    {
        $subjects = [];
        $missing = 0;
        $gradeResults = 0;
        $numericResults = 0;
        foreach ($columns->groupBy('subject_id') as $subjectId => $tests) {
            $scores = [];
            $topics = [];
            foreach ($tests as $test) {
                $mark = $marks->get($test['id']);
                $value = $test['grade_mode'] ? $mark?->grade : $mark?->obtained_marks;
                if ($value === null || $value === '') {
                    $missing++;
                    continue;
                }
                if ($test['grade_mode']) {
                    $gradeResults++;
                    continue;
                }
                $total = $mark->total_marks ?? $test['total'];
                if ($total <= 0) {
                    continue;
                }
                $score = $value / $total * 100;
                $scores[] = $score;
                $numericResults++;
                if ($score < 70) {
                    $topic = trim((string) ($test['topic'] ?: $test['label']));
                    $topics[] = $topic.' ('.number_format($score, 1).'%)';
                }
            }
            if ($scores === []) {
                continue;
            }
            $average = array_sum($scores) / count($scores);
            $trend = collect($progress['subjects'])->firstWhere('id', $subjectId);
            $declining = $trend !== null && $trend['change'] < 0;
            $priority = $average < 50 ? 'Priority support' : ($average < 70 ? 'Targeted practice' : ($declining ? 'Review decline' : ($topics !== [] ? 'Review lower-scoring tests' : 'Consolidate learning')));
            $comment = $tests->first()['subject'].': average '.number_format($average, 1).'% across '.count($scores).' recorded numeric test(s).';
            if (count($scores) === 1) {
                $comment .= ' This is a single-test snapshot; confirm the pattern with another short assessment.';
            }
            if ($trend !== null) {
                $comment .= ' Monthly comparison: '.number_format($trend['before'], 1).'% to '.number_format($trend['after'], 1).'%'
                    .' ('.($trend['change'] > 0 ? '+' : '').number_format($trend['change'], 1).' pp, '.$progress['previous'].' to '.$progress['current'].').';
            }
            if ($average < 50) {
                $intervention = 'Review the marked work with the student to identify prerequisite gaps and recurring errors. Prioritise one or two learning objectives for individual or small-group support.';
                $strategy = 'Model a worked example, ask the student to explain each step, then move from guided to independent practice. Use a brief exit question after each lesson and reassess the same objectives after one to two weeks.';
            } elseif ($average < 70 || $declining || $topics !== []) {
                $intervention = 'Review the lower-scoring test items and ask the student to explain their approach. Check whether errors involve understanding, applying a method, or interpreting the question before choosing the teaching focus.';
                $strategy = 'Reteach the confirmed gap with an example, give a short set of focused questions, and provide feedback on errors. Revisit the skill in the next lessons and use a comparable short quiz after one to two weeks.';
            } else {
                $intervention = 'Maintain practice and check that the student can apply the learning to unfamiliar questions.';
                $strategy = 'Use spaced review, mixed practice and an extension task. Ask the student to explain their reasoning and check retention with a short follow-up quiz.';
            }
            $subjects[] = ['name' => $tests->first()['subject'], 'average' => round($average, 2),
                'priority' => $priority, 'comment' => $comment, 'topics' => array_values(array_unique($topics)),
                'intervention' => $intervention, 'strategy' => $strategy,
                'order' => $average < 50 ? 0 : ($average < 70 ? 1 : ($declining ? 2 : 3))];
        }
        $subjects = collect($subjects)->sortBy([['order', 'asc'], ['average', 'asc']])->values()->all();
        $summary = $numericResults === 0
            ? 'There are no usable numeric results in this selection to identify weaker areas. Review recorded grades and classwork, or collect a short diagnostic assessment before planning support.'
            : 'Based on '.$numericResults.' numeric test result(s), the comments below identify subjects to review and suggest next teaching steps.';
        if ($missing > 0) {
            $summary .= ' '.$missing.' test result(s) are not entered; check the records before drawing conclusions. Missing results are not treated as zero.';
        }
        if ($gradeResults > 0) {
            $summary .= ' '.$gradeResults.' grade result(s) are shown in the sheet; they are not converted into numeric scores for these comments.';
        }

        return compact('summary', 'subjects');
    }
}
