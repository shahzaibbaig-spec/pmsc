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
    public function __construct(private readonly KcatScoringService $scoringService) {}

    public function startAttempt(KcatAssignment $assignment, Student $student): KcatAttempt
    {
        $existing = KcatAttempt::query()
            ->where('kcat_assignment_id', $assignment->id)
            ->where('student_id', $student->id)
            ->where('status', 'in_progress')
            ->first();

        if ($existing) {
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

            return KcatAttempt::query()->create([
                'kcat_assignment_id' => $assignment->id,
                'kcat_test_id' => $assignment->kcat_test_id,
                'student_id' => $student->id,
                'session' => $assignment->session,
                'started_at' => now(),
                'status' => 'in_progress',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ])->fresh(['test.sections.questions.options']);
        });
    }

    public function saveAnswer(KcatAttempt $attempt, KcatQuestion $question, array $data): KcatAnswer
    {
        if ($attempt->status !== 'in_progress') {
            throw ValidationException::withMessages(['attempt' => 'Submitted KCAT attempts cannot be changed.']);
        }

        return DB::transaction(fn (): KcatAnswer => KcatAnswer::query()->updateOrCreate(
            ['kcat_attempt_id' => $attempt->id, 'kcat_question_id' => $question->id],
            ['selected_option_id' => $data['selected_option_id'] ?? null, 'answer_text' => $data['answer_text'] ?? null]
        ));
    }

    public function submitAttempt(KcatAttempt $attempt): KcatAttempt
    {
        if ($attempt->status !== 'in_progress') {
            return $attempt;
        }

        return DB::transaction(function () use ($attempt): KcatAttempt {
            $duration = $attempt->started_at ? now()->diffInSeconds($attempt->started_at) : null;
            $attempt->update(['status' => 'submitted', 'submitted_at' => now(), 'duration_seconds' => $duration]);
            $attempt->assignment?->update(['status' => 'completed']);

            return $this->scoringService->scoreAttempt($attempt);
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
