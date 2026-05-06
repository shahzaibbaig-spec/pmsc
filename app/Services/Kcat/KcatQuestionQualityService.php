<?php

namespace App\Services\Kcat;

use App\Models\KcatAnswer;
use App\Models\KcatQuestion;
use App\Models\KcatQuestionReview;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KcatQuestionQualityService
{
    public function recordQuestionAttempt(KcatAnswer $answer): void
    {
        $questionId = (int) $answer->kcat_question_id;
        if ($questionId <= 0) {
            return;
        }

        $question = KcatQuestion::query()->find($questionId);
        if (! $question) {
            return;
        }

        $stats = KcatAnswer::query()
            ->where('kcat_question_id', $questionId)
            ->whereNotNull('is_correct')
            ->selectRaw('COUNT(*) as attempts')
            ->selectRaw('SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct')
            ->selectRaw('AVG(response_time_seconds) as avg_time')
            ->first();

        $attempts = (int) ($stats?->attempts ?? 0);
        $correct = (int) ($stats?->correct ?? 0);
        $averageResponseTime = $stats?->avg_time !== null ? round((float) $stats->avg_time, 2) : null;

        $question->update([
            'times_attempted' => $attempts,
            'times_correct' => $correct,
            'average_response_time' => $averageResponseTime,
        ]);

        $analysis = $this->analyzeQuestion($question->fresh() ?? $question);
        $question->update([
            'discrimination_flag' => $analysis['discrimination_flag'],
        ]);
    }

    public function analyzeQuestion(KcatQuestion $question): array
    {
        $attempts = (int) ($question->times_attempted ?? 0);
        $correct = (int) ($question->times_correct ?? 0);
        $rate = $attempts > 0 ? round(($correct / $attempts) * 100, 2) : 0.0;
        $avgTime = $question->average_response_time !== null ? (float) $question->average_response_time : null;

        $flag = null;
        if ($attempts < 10) {
            $flag = 'insufficient_data';
        } elseif ($rate > 85 && $question->difficulty === 'hard') {
            $flag = 'too_easy';
        } elseif ($rate < 25 && $question->difficulty === 'easy') {
            $flag = 'too_hard';
        } elseif ($avgTime !== null && $avgTime >= 90 && $rate <= 40) {
            $flag = 'confusing';
        }

        return [
            'times_attempted' => $attempts,
            'times_correct' => $correct,
            'correct_rate' => $rate,
            'average_response_time' => $avgTime,
            'discrimination_flag' => $flag,
        ];
    }

    public function flagWeakQuestions(array $filters = []): LengthAwarePaginator
    {
        return KcatQuestion::query()
            ->with(['section', 'test', 'latestReview.reviewer'])
            ->when($filters['section_id'] ?? null, fn (Builder $query, $sectionId) => $query->where('kcat_section_id', (int) $sectionId))
            ->when($filters['difficulty'] ?? null, fn (Builder $query, string $difficulty) => $query->where('difficulty', $difficulty))
            ->when($filters['review_status'] ?? null, fn (Builder $query, string $status) => $query->where('review_status', $status))
            ->when($filters['discrimination_flag'] ?? null, fn (Builder $query, string $flag) => $query->where('discrimination_flag', $flag))
            ->when(($filters['only_flagged'] ?? null) === '1', fn (Builder $query) => $query->whereNotNull('discrimination_flag'))
            ->orderByDesc('retired_at')
            ->orderByRaw('CASE WHEN discrimination_flag IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('times_attempted')
            ->paginate(25)
            ->withQueryString();
    }

    public function submitReview(KcatQuestion $question, array $data, User $reviewer): KcatQuestionReview
    {
        return DB::transaction(function () use ($question, $data, $reviewer): KcatQuestionReview {
            $review = KcatQuestionReview::query()->create([
                'kcat_question_id' => $question->id,
                'reviewed_by' => $reviewer->id,
                'status' => $data['status'],
                'difficulty_review' => $data['difficulty_review'] ?? null,
                'clarity_score' => $data['clarity_score'] ?? null,
                'quality_score' => $data['quality_score'] ?? null,
                'issue_notes' => $data['issue_notes'] ?? null,
                'action_taken' => $data['action_taken'] ?? null,
                'reviewed_at' => now(),
            ]);

            $question->update([
                'review_status' => $data['status'],
                'updated_by' => $reviewer->id,
            ]);

            if (($data['status'] ?? null) === 'retired') {
                $this->retireQuestion($question, $reviewer);
            } elseif (($data['status'] ?? null) === 'approved') {
                $this->approveQuestion($question, $reviewer);
            }

            return $review->fresh(['question', 'reviewer']) ?? $review;
        });
    }

    public function retireQuestion(KcatQuestion $question, User $user): KcatQuestion
    {
        $question->update([
            'review_status' => 'retired',
            'is_active' => false,
            'retired_at' => now(),
            'retired_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return $question->fresh() ?? $question;
    }

    public function approveQuestion(KcatQuestion $question, User $user): KcatQuestion
    {
        $question->update([
            'review_status' => 'approved',
            'updated_by' => $user->id,
        ]);

        return $question->fresh() ?? $question;
    }

    public function reviewStatsSummary(): Collection
    {
        return KcatQuestion::query()
            ->selectRaw('review_status, COUNT(*) as total')
            ->groupBy('review_status')
            ->pluck('total', 'review_status');
    }
}

