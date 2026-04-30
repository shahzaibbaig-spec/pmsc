<?php

namespace App\Services\Kcat;

use App\Models\KcatAttempt;
use App\Models\KcatScore;
use App\Models\KcatSection;
use Illuminate\Support\Facades\DB;

class KcatScoringService
{
    public function scoreAttempt(KcatAttempt $attempt): KcatAttempt
    {
        return DB::transaction(function () use ($attempt): KcatAttempt {
            $attempt->load(['test.sections.questions.options', 'answers.selectedOption']);
            $totalScore = 0.0;
            $totalMarks = 0.0;

            foreach ($attempt->test->sections as $section) {
                $score = $this->scoreSection($attempt, $section);
                $totalScore += (float) $score->raw_score;
                $totalMarks += (float) $score->total_marks;
            }

            $percentage = $totalMarks > 0 ? round(($totalScore / $totalMarks) * 100, 2) : 0;
            $attempt->forceFill(['percentage' => $percentage]);
            $recommendation = $this->generateStreamRecommendation($attempt->fresh(['scores']));

            $attempt->update([
                'total_score' => $totalScore,
                'percentage' => $percentage,
                'band' => $this->calculateBand($percentage),
                'recommended_stream' => $recommendation['recommended_stream'],
                'recommendation_summary' => $recommendation['summary'],
            ]);

            return $attempt->fresh(['scores.section', 'test.sections', 'student.classRoom']);
        });
    }

    public function scoreSection(KcatAttempt $attempt, KcatSection $section): KcatScore
    {
        $questions = $section->questions()->where('is_active', true)->with('options')->get();
        $answers = $attempt->answers()->whereIn('kcat_question_id', $questions->pluck('id'))->with('selectedOption')->get()->keyBy('kcat_question_id');
        $rawScore = 0.0;
        $totalMarks = (float) $questions->sum('marks');

        foreach ($questions as $question) {
            $answer = $answers->get($question->id);
            $correctOption = $question->options->firstWhere('is_correct', true);
            $isCorrect = $answer && $correctOption && (int) $answer->selected_option_id === (int) $correctOption->id;
            $marks = $isCorrect ? (float) $question->marks : 0.0;
            $rawScore += $marks;

            if ($answer) {
                $answer->update(['is_correct' => $isCorrect, 'marks_awarded' => $marks]);
            }
        }

        $percentage = $totalMarks > 0 ? round(($rawScore / $totalMarks) * 100, 2) : 0;

        return KcatScore::query()->updateOrCreate(
            ['kcat_attempt_id' => $attempt->id, 'kcat_section_id' => $section->id],
            [
                'section_code' => $section->code,
                'raw_score' => $rawScore,
                'total_marks' => $totalMarks,
                'percentage' => $percentage,
                'band' => $this->calculateBand($percentage),
                'remarks' => $this->remarksForBand($this->calculateBand($percentage)),
            ]
        );
    }

    public function calculateBand(float $percentage): string
    {
        return match (true) {
            $percentage >= 90 => 'exceptional',
            $percentage >= 75 => 'strong',
            $percentage >= 60 => 'above_average',
            $percentage >= 45 => 'average',
            default => 'needs_support',
        };
    }

    public function generateStreamRecommendation(KcatAttempt $attempt): array
    {
        $attempt->loadMissing('scores');
        $scores = $attempt->scores->keyBy('section_code');
        $verbal = (float) ($scores['verbal_reasoning']->percentage ?? 0);
        $quant = (float) ($scores['quantitative_reasoning']->percentage ?? 0);
        $nonVerbal = (float) ($scores['non_verbal_reasoning']->percentage ?? 0);
        $spatial = (float) ($scores['spatial_reasoning']->percentage ?? 0);
        $overall = (float) ($attempt->percentage ?? $attempt->scores->avg('percentage') ?? 0);

        $stream = 'Teaching / Humanities';
        if ($quant >= 75 && $spatial >= 75 && $nonVerbal >= 75) {
            $stream = 'Pre-Engineering';
        } elseif ($quant >= 70 && $nonVerbal >= 70) {
            $stream = 'Computer Science';
        } elseif ($verbal >= 70 && $quant >= 60) {
            $stream = 'Commerce / Business';
        } elseif ($verbal >= 75 && $nonVerbal >= 65) {
            $stream = 'Law / Civil Services';
        } elseif ($spatial >= 70 && $quant >= 55) {
            $stream = 'Technical / Vocational';
        }

        if ($overall >= 75 && abs($verbal - $quant) <= 15 && abs($quant - $nonVerbal) <= 15 && abs($nonVerbal - $spatial) <= 15) {
            $stream = 'Multiple pathways';
        }

        return [
            'recommended_stream' => $stream,
            'summary' => 'KCAT indicates '.$stream.' as a useful counseling direction. Counselor review remains required.',
        ];
    }

    private function remarksForBand(string $band): string
    {
        return str_replace('_', ' ', ucfirst($band));
    }
}
