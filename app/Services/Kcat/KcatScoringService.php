<?php

namespace App\Services\Kcat;

use App\Models\KcatAttempt;
use App\Models\KcatScore;
use App\Models\KcatSection;
use Illuminate\Support\Facades\DB;

class KcatScoringService
{
    public function __construct(
        private readonly KcatStreamPredictionService $streamPredictionService,
        private readonly KcatQuestionQualityService $questionQualityService,
    ) {}

    public function scoreAttempt(KcatAttempt $attempt): KcatAttempt
    {
        return DB::transaction(function () use ($attempt): KcatAttempt {
            $attempt->load(['test.sections', 'answers.selectedOption']);
            $totalScore = 0.0;
            $totalMarks = 0.0;

            foreach ($attempt->test->sections as $section) {
                $score = $this->scoreSection($attempt, $section);
                $totalScore += (float) $score->raw_score;
                $totalMarks += (float) $score->total_marks;
            }

            $percentage = $totalMarks > 0 ? round(($totalScore / $totalMarks) * 100, 2) : 0;

            $attempt->update([
                'total_score' => $totalScore,
                'percentage' => $percentage,
                'band' => $this->calculateBand($percentage),
            ]);

            $recommendations = $this->streamPredictionService
                ->generateRecommendations($attempt->fresh(['scores']) ?? $attempt)
                ->all();
            $this->streamPredictionService->saveRecommendations($attempt, $recommendations);

            return $attempt->fresh(['scores.section', 'test.sections', 'student.classRoom', 'streamRecommendations']);
        });
    }

    public function scoreSection(KcatAttempt $attempt, KcatSection $section): KcatScore
    {
        $rawScore = 0.0;

        if ($attempt->is_adaptive) {
            $answers = $attempt->answers()
                ->whereHas('question', fn ($query) => $query->where('kcat_section_id', $section->id))
                ->with(['question.options', 'selectedOption'])
                ->get();
            $totalMarks = (float) $answers->sum(fn ($answer) => (float) ($answer->question?->marks ?? 0));

            foreach ($answers as $answer) {
                $question = $answer->question;
                if (! $question) {
                    continue;
                }

                $correctOption = $question->options->firstWhere('is_correct', true);
                $isCorrect = $correctOption && (int) $answer->selected_option_id === (int) $correctOption->id;
                $marks = $isCorrect ? (float) $question->marks : 0.0;
                $rawScore += $marks;
                $answer->update(['is_correct' => $isCorrect, 'marks_awarded' => $marks]);
                $this->questionQualityService->recordQuestionAttempt($answer);
            }
        } else {
            $questions = $section->questions()
                ->where('is_active', true)
                ->whereNull('retired_at')
                ->with('options')
                ->get();
            $answers = $attempt->answers()
                ->whereIn('kcat_question_id', $questions->pluck('id'))
                ->with('selectedOption')
                ->get()
                ->keyBy('kcat_question_id');
            $totalMarks = (float) $questions->sum('marks');

            foreach ($questions as $question) {
                $answer = $answers->get($question->id);
                $correctOption = $question->options->firstWhere('is_correct', true);
                $isCorrect = $answer && $correctOption && (int) $answer->selected_option_id === (int) $correctOption->id;
                $marks = $isCorrect ? (float) $question->marks : 0.0;
                $rawScore += $marks;

                if ($answer) {
                    $answer->update(['is_correct' => $isCorrect, 'marks_awarded' => $marks]);
                    $this->questionQualityService->recordQuestionAttempt($answer);
                }
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

    private function remarksForBand(string $band): string
    {
        return str_replace('_', ' ', ucfirst($band));
    }
}
