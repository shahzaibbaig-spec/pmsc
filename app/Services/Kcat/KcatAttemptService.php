<?php

namespace App\Services\Kcat;

use App\Models\KcatAnswer;
use App\Models\KcatAssignment;
use App\Models\KcatAttempt;
use App\Models\KcatQuestion;
use App\Models\KcatTest;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KcatAttemptService
{
    public function __construct(
        private readonly KcatScoringService $scoringService,
        private readonly KcatAdaptiveEngineService $adaptiveEngine,
        private readonly KcatQuestionQualityService $questionQualityService,
    ) {}

    public function startAttempt(KcatAssignment $assignment, Student $student): KcatAttempt
    {
        $assignment->loadMissing('test');
        $existing = KcatAttempt::query()
            ->where('kcat_assignment_id', $assignment->id)
            ->where('student_id', $student->id)
            ->where('status', 'in_progress')
            ->first();

        if ($existing) {
            if ($existing->is_adaptive && ! is_array($existing->adaptive_state)) {
                return $this->adaptiveEngine->initializeAdaptiveAttempt($existing);
            }

            return $existing->load(['test.sections.questions.options']);
        }

        if ($assignment->assigned_to_type === 'student' && (int) $assignment->student_id !== (int) $student->id) {
            abort(403);
        }

        if ($assignment->assigned_to_type === 'class' && (int) $assignment->class_id !== (int) $student->class_id) {
            abort(403);
        }

        return DB::transaction(function () use ($assignment, $student): KcatAttempt {
            $assignment->update(['status' => 'in_progress']);

            $attempt = KcatAttempt::query()->create([
                'kcat_assignment_id' => $assignment->id,
                'kcat_test_id' => $assignment->kcat_test_id,
                'student_id' => $student->id,
                'session' => $assignment->session,
                'started_at' => now(),
                'status' => 'in_progress',
                'is_adaptive' => (bool) ($assignment->test?->is_adaptive_enabled ?? false),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            if ($attempt->is_adaptive) {
                $attempt = $this->adaptiveEngine->initializeAdaptiveAttempt($attempt);
            }

            return $attempt->fresh(['test.sections.questions.options', 'answers']);
        });
    }

    public function saveAnswer(KcatAttempt $attempt, KcatQuestion $question, array $data): KcatAnswer
    {
        if ($attempt->status !== 'in_progress') {
            throw ValidationException::withMessages(['attempt' => 'Submitted KCAT attempts cannot be changed.']);
        }

        if ((int) $question->kcat_test_id !== (int) $attempt->kcat_test_id) {
            throw ValidationException::withMessages(['question' => 'This question does not belong to the current KCAT attempt.']);
        }

        if (
            isset($data['selected_option_id'])
            && $data['selected_option_id'] !== null
            && ! $question->options()->whereKey((int) $data['selected_option_id'])->exists()
        ) {
            throw ValidationException::withMessages(['selected_option_id' => 'Selected option is invalid for this question.']);
        }

        return DB::transaction(fn (): KcatAnswer => KcatAnswer::query()->updateOrCreate(
            ['kcat_attempt_id' => $attempt->id, 'kcat_question_id' => $question->id],
            [
                'selected_option_id' => $data['selected_option_id'] ?? null,
                'answer_text' => $data['answer_text'] ?? null,
                'difficulty_at_time' => $question->difficulty,
                'answered_at' => now(),
                'response_time_seconds' => isset($data['response_time_seconds']) ? (int) $data['response_time_seconds'] : null,
            ]
        ));
    }

    public function submitAttempt(KcatAttempt $attempt): KcatAttempt
    {
        if ($attempt->status !== 'in_progress') {
            return $attempt;
        }

        return DB::transaction(function () use ($attempt): KcatAttempt {
            $duration = $attempt->started_at ? now()->diffInSeconds($attempt->started_at) : null;
            $attempt->update([
                'status' => 'submitted',
                'submitted_at' => now(),
                'duration_seconds' => $duration,
                'updated_by' => auth()->id() ?? $attempt->updated_by,
            ]);
            $attempt->assignment?->update(['status' => 'completed']);

            return $this->scoringService->scoreAttempt($attempt);
        });
    }

    public function getNextAdaptiveQuestion(KcatAttempt $attempt): ?KcatQuestion
    {
        if (! $attempt->is_adaptive) {
            throw ValidationException::withMessages(['attempt' => 'This attempt is not adaptive.']);
        }

        if ($attempt->status !== 'in_progress') {
            return null;
        }

        return $this->adaptiveEngine->getNextQuestion($attempt);
    }

    public function saveAdaptiveAnswer(KcatAttempt $attempt, array $data): array
    {
        if (! $attempt->is_adaptive) {
            throw ValidationException::withMessages(['attempt' => 'Adaptive answer submission is not available for this attempt.']);
        }

        if ($attempt->status !== 'in_progress') {
            throw ValidationException::withMessages(['attempt' => 'Submitted KCAT attempts cannot be changed.']);
        }

        return DB::transaction(function () use ($attempt, $data): array {
            $attempt->loadMissing(['answers', 'test.sections']);
            $question = $this->adaptiveEngine->getNextQuestion($attempt);
            if (! $question) {
                if ($this->adaptiveEngine->attemptCompleted($attempt)) {
                    $final = $this->submitAttempt($attempt);
                    return ['attempt' => $final, 'completed' => true, 'answer' => null];
                }

                throw ValidationException::withMessages(['attempt' => 'No adaptive question is currently available.']);
            }

            $selectedOptionId = isset($data['selected_option_id']) ? (int) $data['selected_option_id'] : null;
            if ($selectedOptionId !== null && ! $question->options()->whereKey($selectedOptionId)->exists()) {
                throw ValidationException::withMessages(['selected_option_id' => 'Selected option is invalid for the current question.']);
            }

            $correctOptionId = (int) ($question->options()->where('is_correct', true)->value('id') ?? 0);
            $isCorrect = $selectedOptionId !== null && $selectedOptionId === $correctOptionId;
            $marks = $isCorrect ? (float) $question->marks : 0.0;

            $answer = KcatAnswer::query()->updateOrCreate(
                ['kcat_attempt_id' => $attempt->id, 'kcat_question_id' => $question->id],
                [
                    'selected_option_id' => $selectedOptionId,
                    'answer_text' => $data['answer_text'] ?? null,
                    'is_correct' => $isCorrect,
                    'marks_awarded' => $marks,
                    'difficulty_at_time' => $attempt->current_difficulty ?? $question->difficulty,
                    'answered_at' => now(),
                    'response_time_seconds' => isset($data['response_time_seconds']) ? max((int) $data['response_time_seconds'], 0) : null,
                ]
            );

            $this->questionQualityService->recordQuestionAttempt($answer);
            $updatedAttempt = $this->adaptiveEngine->updateAdaptiveState($attempt, $answer);

            if ($this->adaptiveEngine->attemptCompleted($updatedAttempt)) {
                $submitted = $this->submitAttempt($updatedAttempt->fresh() ?? $updatedAttempt);
                return ['attempt' => $submitted, 'completed' => true, 'answer' => $answer->fresh()];
            }

            return ['attempt' => $updatedAttempt->fresh(), 'completed' => false, 'answer' => $answer->fresh()];
        });
    }

    public function manualEntry(KcatTest $test, Student $student, array $answers, User $counselor): KcatAttempt
    {
        return DB::transaction(function () use ($test, $student, $answers, $counselor): KcatAttempt {
            $attempt = KcatAttempt::query()->create([
                'kcat_test_id' => $test->id,
                'student_id' => $student->id,
                'counselor_id' => $counselor->id,
                'session' => $test->session ?? $this->currentSession(),
                'started_at' => now(),
                'submitted_at' => now(),
                'status' => 'submitted',
                'created_by' => $counselor->id,
                'updated_by' => $counselor->id,
            ]);

            foreach ($answers as $questionId => $answer) {
                $selectedOptionId = is_array($answer) ? ($answer['selected_option_id'] ?? null) : $answer;

                KcatAnswer::query()->create([
                    'kcat_attempt_id' => $attempt->id,
                    'kcat_question_id' => (int) $questionId,
                    'selected_option_id' => $selectedOptionId,
                    'answer_text' => is_array($answer) ? ($answer['answer_text'] ?? null) : null,
                    'answered_at' => now(),
                ]);
            }

            return $this->scoringService->scoreAttempt($attempt);
        });
    }

    private function currentSession(): string
    {
        $now = now();
        $startYear = $now->month >= 7 ? $now->year : ($now->year - 1);
        return $startYear.'-'.($startYear + 1);
    }
}
