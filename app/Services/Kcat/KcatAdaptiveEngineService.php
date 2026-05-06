<?php

namespace App\Services\Kcat;

use App\Models\KcatAnswer;
use App\Models\KcatAttempt;
use App\Models\KcatQuestion;
use App\Models\KcatSection;

class KcatAdaptiveEngineService
{
    public function initializeAdaptiveAttempt(KcatAttempt $attempt): KcatAttempt
    {
        $attempt->loadMissing('test.sections');
        $sections = $attempt->test?->sections ?? collect();
        $firstSection = $sections->first();
        $questionsPerSection = max((int) ($attempt->test?->questions_per_section ?? 10), 1);

        $state = [
            'questions_per_section' => $questionsPerSection,
            'section_order' => [],
            'sections' => [],
        ];

        foreach ($sections as $section) {
            $state['section_order'][] = $section->code;
            $state['sections'][$section->code] = [
                'section_id' => $section->id,
                'answered' => 0,
                'correct_streak' => 0,
                'incorrect_streak' => 0,
                'current_difficulty' => 'medium',
                'pending_question_id' => null,
            ];
        }

        $attempt->update([
            'is_adaptive' => true,
            'current_section_id' => $firstSection?->id,
            'current_difficulty' => 'medium',
            'adaptive_state' => $state,
        ]);

        return $attempt->fresh(['test.sections']) ?? $attempt;
    }

    public function getNextQuestion(KcatAttempt $attempt): ?KcatQuestion
    {
        $attempt->loadMissing(['test.sections', 'answers']);

        if ($this->attemptCompleted($attempt)) {
            return null;
        }

        if (! $attempt->is_adaptive || ! is_array($attempt->adaptive_state)) {
            $attempt = $this->initializeAdaptiveAttempt($attempt);
        }

        $state = $attempt->adaptive_state ?? [];
        $section = $this->resolveCurrentSection($attempt, $state);
        if (! $section) {
            return null;
        }

        $sectionCode = $section->code;
        $sectionState = $state['sections'][$sectionCode] ?? [];
        $pendingQuestionId = (int) ($sectionState['pending_question_id'] ?? 0);
        if ($pendingQuestionId > 0 && ! $attempt->answers->contains('kcat_question_id', $pendingQuestionId)) {
            $pendingQuestion = KcatQuestion::query()
                ->whereKey($pendingQuestionId)
                ->where('kcat_section_id', $section->id)
                ->where('kcat_test_id', $attempt->kcat_test_id)
                ->where('is_active', true)
                ->whereNull('retired_at')
                ->with('options')
                ->first();
            if ($pendingQuestion) {
                return $pendingQuestion;
            }

            $sectionState['pending_question_id'] = null;
            $state['sections'][$sectionCode] = $sectionState;
            $attempt->update(['adaptive_state' => $state]);
        }

        $answeredQuestionIds = $attempt->answers->pluck('kcat_question_id')->map(fn ($id) => (int) $id)->all();
        $difficulty = (string) ($sectionState['current_difficulty'] ?? 'medium');
        $difficultyOrder = $this->difficultyOrder($difficulty);
        $selectedQuestion = null;

        foreach ($difficultyOrder as $candidateDifficulty) {
            $selectedQuestion = KcatQuestion::query()
                ->where('kcat_test_id', $attempt->kcat_test_id)
                ->where('kcat_section_id', $section->id)
                ->where('difficulty', $candidateDifficulty)
                ->where('is_active', true)
                ->whereNull('retired_at')
                ->when($answeredQuestionIds !== [], fn ($query) => $query->whereNotIn('id', $answeredQuestionIds))
                ->inRandomOrder()
                ->first();

            if ($selectedQuestion) {
                $difficulty = $candidateDifficulty;
                break;
            }
        }

        if (! $selectedQuestion) {
            return null;
        }

        $sectionState['pending_question_id'] = $selectedQuestion->id;
        $sectionState['current_difficulty'] = $difficulty;
        $state['sections'][$sectionCode] = $sectionState;

        $attempt->update([
            'current_section_id' => $section->id,
            'current_difficulty' => $difficulty,
            'adaptive_state' => $state,
        ]);

        return $selectedQuestion->load('options');
    }

    public function updateAdaptiveState(KcatAttempt $attempt, KcatAnswer $answer): KcatAttempt
    {
        $attempt->loadMissing('test.sections');
        $answer->loadMissing('question.section');

        $section = $answer->question?->section;
        if (! $section) {
            return $attempt;
        }

        $state = $attempt->adaptive_state ?? [];
        if (! isset($state['sections'][$section->code])) {
            $state['sections'][$section->code] = [
                'section_id' => $section->id,
                'answered' => 0,
                'correct_streak' => 0,
                'incorrect_streak' => 0,
                'current_difficulty' => 'medium',
                'pending_question_id' => null,
            ];
        }

        $sectionState = $state['sections'][$section->code];
        $sectionState['answered'] = (int) ($sectionState['answered'] ?? 0) + 1;

        $isCorrect = (bool) $answer->is_correct;
        if ($isCorrect) {
            $sectionState['correct_streak'] = (int) ($sectionState['correct_streak'] ?? 0) + 1;
            $sectionState['incorrect_streak'] = 0;
        } else {
            $sectionState['incorrect_streak'] = (int) ($sectionState['incorrect_streak'] ?? 0) + 1;
            $sectionState['correct_streak'] = 0;
        }

        $previousDifficulty = (string) ($sectionState['current_difficulty'] ?? 'medium');
        $nextDifficulty = $this->determineNextDifficulty($sectionState, $isCorrect);
        $sectionState['current_difficulty'] = $nextDifficulty;
        $sectionState['pending_question_id'] = null;

        if ($nextDifficulty !== $previousDifficulty) {
            $sectionState['correct_streak'] = 0;
            $sectionState['incorrect_streak'] = 0;
        }

        $state['sections'][$section->code] = $sectionState;
        $attempt->adaptive_state = $state;
        $nextSection = $this->sectionCompleted($attempt, $section)
            ? $this->nextUnfinishedSection($attempt, $state)
            : $section;

        $attempt->update([
            'current_section_id' => $nextSection?->id,
            'current_difficulty' => $nextSection
                ? (string) ($state['sections'][$nextSection->code]['current_difficulty'] ?? 'medium')
                : $nextDifficulty,
            'adaptive_state' => $state,
        ]);

        return $attempt->fresh(['test.sections']) ?? $attempt;
    }

    public function determineNextDifficulty(array $state, bool $isCorrect): string
    {
        $difficulty = (string) ($state['current_difficulty'] ?? 'medium');

        if ($isCorrect && (int) ($state['correct_streak'] ?? 0) >= 2) {
            return match ($difficulty) {
                'easy' => 'medium',
                'medium' => 'hard',
                default => 'hard',
            };
        }

        if (! $isCorrect && (int) ($state['incorrect_streak'] ?? 0) >= 2) {
            return match ($difficulty) {
                'hard' => 'medium',
                'medium' => 'easy',
                default => 'easy',
            };
        }

        return $difficulty;
    }

    public function sectionCompleted(KcatAttempt $attempt, KcatSection $section): bool
    {
        $attempt->loadMissing('test');
        $state = $attempt->adaptive_state ?? [];
        $sectionState = $state['sections'][$section->code] ?? [];
        $answered = (int) ($sectionState['answered'] ?? 0);
        $required = max((int) ($attempt->test?->questions_per_section ?? ($state['questions_per_section'] ?? 10)), 1);

        return $answered >= $required;
    }

    public function attemptCompleted(KcatAttempt $attempt): bool
    {
        $attempt->loadMissing('test.sections');

        foreach ($attempt->test->sections as $section) {
            if (! $this->sectionCompleted($attempt, $section)) {
                return false;
            }
        }

        return true;
    }

    private function resolveCurrentSection(KcatAttempt $attempt, array $state): ?KcatSection
    {
        $attempt->loadMissing('test.sections');
        $sections = $attempt->test->sections->keyBy('id');

        if ($attempt->current_section_id && $sections->has($attempt->current_section_id)) {
            $current = $sections->get($attempt->current_section_id);
            if ($current && ! $this->sectionCompleted($attempt, $current)) {
                return $current;
            }
        }

        return $this->nextUnfinishedSection($attempt, $state);
    }

    private function nextUnfinishedSection(KcatAttempt $attempt, array $state): ?KcatSection
    {
        $attempt->loadMissing('test.sections');
        $orderedCodes = $state['section_order'] ?? $attempt->test->sections->pluck('code')->all();
        $sectionsByCode = $attempt->test->sections->keyBy('code');

        foreach ($orderedCodes as $code) {
            /** @var KcatSection|null $section */
            $section = $sectionsByCode->get($code);
            if (! $section) {
                continue;
            }

            if (! $this->sectionCompleted($attempt, $section)) {
                return $section;
            }
        }

        return null;
    }

    private function difficultyOrder(string $difficulty): array
    {
        return match ($difficulty) {
            'easy' => ['easy', 'medium', 'hard'],
            'hard' => ['hard', 'medium', 'easy'],
            default => ['medium', 'easy', 'hard'],
        };
    }
}
