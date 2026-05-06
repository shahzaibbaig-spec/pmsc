<?php

namespace App\Services\Kcat;

use App\Models\KcatQuestion;
use App\Models\KcatQuestionOption;
use App\Models\KcatSection;
use App\Models\KcatTest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KcatTestService
{
    public const DEFAULT_SECTIONS = [
        'verbal_reasoning' => 'Verbal Reasoning',
        'quantitative_reasoning' => 'Quantitative Reasoning',
        'non_verbal_reasoning' => 'Non-Verbal Reasoning',
        'spatial_reasoning' => 'Spatial Reasoning',
    ];

    public function createTest(array $data, User $user): KcatTest
    {
        return DB::transaction(function () use ($data, $user): KcatTest {
            $test = KcatTest::query()->create([
                ...collect($data)->only([
                    'title',
                    'description',
                    'grade_from',
                    'grade_to',
                    'duration_minutes',
                    'status',
                    'is_adaptive_enabled',
                    'questions_per_section',
                    'session',
                ])->all(),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            foreach (array_values(self::DEFAULT_SECTIONS) as $index => $name) {
                $code = array_keys(self::DEFAULT_SECTIONS)[$index];
                $this->addSection($test, ['name' => $name, 'code' => $code, 'sort_order' => $index + 1]);
            }

            $this->populateTestQuestionsFromBankIfRequested($test, $data, $user);

            return $test->fresh(['sections']);
        });
    }

    public function updateTest(KcatTest $test, array $data, User $user): KcatTest
    {
        return DB::transaction(function () use ($test, $data, $user): KcatTest {
            $test->update([
                ...collect($data)->only([
                    'title',
                    'description',
                    'grade_from',
                    'grade_to',
                    'duration_minutes',
                    'status',
                    'is_adaptive_enabled',
                    'questions_per_section',
                    'session',
                ])->all(),
                'updated_by' => $user->id,
            ]);

            return $test->fresh(['sections']);
        });
    }

    public function activateTest(KcatTest $test, User $user): KcatTest
    {
        $test->update(['status' => 'active', 'updated_by' => $user->id]);
        return $test->fresh();
    }

    public function archiveTest(KcatTest $test, User $user): KcatTest
    {
        $test->update(['status' => 'archived', 'updated_by' => $user->id]);
        return $test->fresh();
    }

    public function addSection(KcatTest $test, array $data): KcatSection
    {
        return DB::transaction(function () use ($test, $data): KcatSection {
            return $test->sections()->create([
                'name' => $data['name'],
                'code' => $data['code'],
                'description' => $data['description'] ?? null,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
            ]);
        });
    }

    public function addQuestion(KcatSection $section, array $data, User $user): KcatQuestion
    {
        return DB::transaction(function () use ($section, $data, $user): KcatQuestion {
            $question = $section->questions()->create([
                'kcat_test_id' => $section->kcat_test_id,
                'question_type' => $data['question_type'],
                'difficulty' => $data['difficulty'],
                'question_text' => $data['question_text'] ?? '',
                'question_image' => $data['question_image'] ?? null,
                'explanation' => $data['explanation'] ?? null,
                'marks' => (int) $data['marks'],
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $this->syncOptions($question, $data['options'] ?? []);
            $this->refreshTotals($section->test);

            return $question->fresh(['options', 'section']);
        });
    }

    public function updateQuestion(KcatQuestion $question, array $data, User $user): KcatQuestion
    {
        return DB::transaction(function () use ($question, $data, $user): KcatQuestion {
            if ($question->answers()->exists()) {
                throw ValidationException::withMessages(['question' => 'Questions with submitted answers cannot be edited. Archive and create a new question instead.']);
            }

            $question->update([
                'question_type' => $data['question_type'],
                'difficulty' => $data['difficulty'],
                'question_text' => $data['question_text'] ?? '',
                'question_image' => $data['question_image'] ?? null,
                'explanation' => $data['explanation'] ?? null,
                'marks' => (int) $data['marks'],
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'updated_by' => $user->id,
            ]);

            $question->options()->delete();
            $this->syncOptions($question, $data['options'] ?? []);
            $this->refreshTotals($question->test);

            return $question->fresh(['options', 'section']);
        });
    }

    public function refreshTotals(KcatTest $test): void
    {
        $test->load('sections.questions');
        foreach ($test->sections as $section) {
            $questions = $section->questions
                ->where('is_active', true)
                ->whereNull('retired_at');
            $section->update([
                'total_questions' => $questions->count(),
                'total_marks' => $questions->sum('marks'),
            ]);
        }

        $test->load('sections');
        $test->update([
            'total_questions' => $test->sections->sum('total_questions'),
            'total_marks' => $test->sections->sum('total_marks'),
        ]);
    }

    private function syncOptions(KcatQuestion $question, array $options): void
    {
        foreach (array_values($options) as $index => $option) {
            KcatQuestionOption::query()->create([
                'kcat_question_id' => $question->id,
                'option_text' => $option['option_text'] ?? null,
                'option_image' => $option['option_image'] ?? null,
                'is_correct' => (bool) ($option['is_correct'] ?? false),
                'sort_order' => (int) ($option['sort_order'] ?? ($index + 1)),
            ]);
        }
    }

    private function populateTestQuestionsFromBankIfRequested(KcatTest $test, array $data, User $user): void
    {
        $requestedTotalQuestions = (int) ($data['question_count'] ?? 0);
        if ($requestedTotalQuestions <= 0) {
            return;
        }

        $test->loadMissing('sections');
        $sections = $test->sections->sortBy('sort_order')->values();
        $sectionCount = $sections->count();
        if ($sectionCount <= 0) {
            throw ValidationException::withMessages([
                'question_count' => 'KCAT sections are missing. Add sections before generating questions.',
            ]);
        }

        if ($requestedTotalQuestions % $sectionCount !== 0) {
            throw ValidationException::withMessages([
                'question_count' => 'Total questions must be equally divisible by category count ('.$sectionCount.').',
            ]);
        }

        $perSection = intdiv($requestedTotalQuestions, $sectionCount);
        $difficultyMode = trim((string) ($data['difficulty_level'] ?? 'auto'));
        $difficultyLevels = $this->resolveDifficultyLevels(
            $difficultyMode,
            isset($data['grade_from']) ? (int) $data['grade_from'] : null,
            isset($data['grade_to']) ? (int) $data['grade_to'] : null
        );

        foreach ($sections as $section) {
            $sourceQuestions = $this->selectRandomBankQuestions(
                (string) $section->code,
                $perSection,
                $difficultyLevels,
                (int) $test->id
            );

            if ($sourceQuestions->count() < $perSection) {
                throw ValidationException::withMessages([
                    'question_count' => 'Not enough '.$difficultyMode.' questions available in bank for '.$section->name
                        .'. Required '.$perSection.', found '.$sourceQuestions->count().'.',
                ]);
            }

            $nextSortOrder = ((int) ($section->questions()->max('sort_order') ?? 0)) + 1;
            foreach ($sourceQuestions as $sourceQuestion) {
                $clonedQuestion = $section->questions()->create([
                    'kcat_test_id' => $test->id,
                    'question_type' => (string) $sourceQuestion->question_type,
                    'difficulty' => (string) $sourceQuestion->difficulty,
                    'question_text' => (string) $sourceQuestion->question_text,
                    'question_image' => $sourceQuestion->question_image,
                    'explanation' => $sourceQuestion->explanation,
                    'marks' => (int) $sourceQuestion->marks,
                    'sort_order' => $nextSortOrder++,
                    'is_active' => true,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);

                $this->syncOptions(
                    $clonedQuestion,
                    $sourceQuestion->options
                        ->sortBy('sort_order')
                        ->values()
                        ->map(fn (KcatQuestionOption $option): array => [
                            'option_text' => $option->option_text,
                            'option_image' => $option->option_image,
                            'is_correct' => (bool) $option->is_correct,
                            'sort_order' => (int) $option->sort_order,
                        ])
                        ->all()
                );
            }
        }

        $this->refreshTotals($test->fresh(['sections.questions']) ?? $test);
    }

    /**
     * @param array<int, string> $difficultyLevels
     * @return Collection<int, KcatQuestion>
     */
    private function selectRandomBankQuestions(
        string $sectionCode,
        int $limit,
        array $difficultyLevels,
        int $excludeTestId
    ): Collection {
        $preferred = $this->questionBankQuery($sectionCode, $difficultyLevels, $excludeTestId, true)
            ->inRandomOrder()
            ->get();

        $fallback = collect();
        if ($preferred->count() < $limit) {
            $fallback = $this->questionBankQuery($sectionCode, $difficultyLevels, $excludeTestId, false)
                ->inRandomOrder()
                ->get();
        }

        /** @var Collection<int, KcatQuestion> $combined */
        $combined = $preferred
            ->concat($fallback)
            ->filter(fn (KcatQuestion $question): bool => $this->isUsableQuestion($question))
            ->unique(fn (KcatQuestion $question): string => mb_strtolower(trim((string) $question->question_text)))
            ->values();

        return $combined->take($limit)->values();
    }

    /**
     * @param array<int, string> $difficultyLevels
     */
    private function questionBankQuery(
        string $sectionCode,
        array $difficultyLevels,
        int $excludeTestId,
        bool $preferBankTests
    ): Builder {
        $query = KcatQuestion::query()
            ->where('kcat_test_id', '!=', $excludeTestId)
            ->where('is_active', true)
            ->whereNull('retired_at')
            ->whereIn('difficulty', $difficultyLevels)
            ->whereHas('section', fn (Builder $builder) => $builder->where('code', $sectionCode))
            ->whereHas('options')
            ->with([
                'options:id,kcat_question_id,option_text,option_image,is_correct,sort_order',
                'test:id,title,description',
            ]);

        if ($preferBankTests) {
            $query->whereHas('test', function (Builder $builder): void {
                $builder->where(function (Builder $nested): void {
                    $nested->where('title', 'like', 'KORT Cognitive Assessment Test%')
                        ->orWhere('description', 'like', '%question bank%')
                        ->orWhere('description', 'like', '%question-bank%');
                });
            });
        }

        return $query;
    }

    private function isUsableQuestion(KcatQuestion $question): bool
    {
        $options = $question->options ?? collect();

        return $options->count() >= 2 && $options->where('is_correct', true)->count() === 1;
    }

    /**
     * @return array<int, string>
     */
    private function resolveDifficultyLevels(string $mode, ?int $gradeFrom, ?int $gradeTo): array
    {
        $normalized = in_array($mode, ['easy', 'medium', 'hard', 'auto'], true) ? $mode : 'auto';
        if ($normalized !== 'auto') {
            return [$normalized];
        }

        $from = $gradeFrom ?? 7;
        $to = $gradeTo ?? $from;
        $average = ($from + $to) / 2;

        if ($average <= 8.5) {
            return ['easy'];
        }

        if ($average >= 10.5) {
            return ['hard'];
        }

        return ['medium'];
    }
}
