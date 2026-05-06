<?php

namespace Database\Seeders;

use App\Models\KcatQuestion;
use App\Models\KcatSection;
use App\Models\KcatTest;
use App\Services\Kcat\KcatQuestionGeneratorService;
use App\Services\Kcat\KcatTestService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class KcatQuestionBankSeeder extends Seeder
{
    /**
     * @var array<string, int>
     */
    private const DIFFICULTY_DISTRIBUTION = [
        'easy' => 50,
        'medium' => 75,
        'hard' => 75,
    ];

    /**
     * @var array<string, string>
     */
    private const SECTION_LABELS = [
        'verbal_reasoning' => 'Verbal',
        'quantitative_reasoning' => 'Quantitative',
        'non_verbal_reasoning' => 'Non-Verbal',
        'spatial_reasoning' => 'Spatial',
    ];

    public function run(): void
    {
        $this->seedBank();
    }

    /**
     * @param callable(string):void|null $progress
     * @return array<string, mixed>
     */
    public function seedBank(?callable $progress = null, bool $dryRun = false): array
    {
        $generator = app(KcatQuestionGeneratorService::class);
        $testService = app(KcatTestService::class);

        $test = $this->resolveDefaultTest($progress);
        $sections = $this->ensureDefaultSections($test);

        $summary = [
            'test_id' => (int) $test->id,
            'dry_run' => $dryRun,
            'inserted_questions' => 0,
            'inserted_options' => 0,
            'sections' => [],
        ];

        DB::transaction(function () use (
            $progress,
            $generator,
            $test,
            $sections,
            $dryRun,
            &$summary
        ): void {
            foreach (KcatTestService::DEFAULT_SECTIONS as $sectionCode => $sectionName) {
                $section = $sections[$sectionCode] ?? null;
                if (! $section instanceof KcatSection) {
                    throw new RuntimeException('KCAT section "'.$sectionCode.'" could not be resolved.');
                }

                $existingTexts = KcatQuestion::query()
                    ->where('kcat_test_id', $test->id)
                    ->where('kcat_section_id', $section->id)
                    ->pluck('question_text')
                    ->map(static fn ($value): string => (string) $value)
                    ->all();

                $generator->primeUsedSignatures($sectionCode, $existingTexts);

                $sectionSummary = [
                    'section_code' => $sectionCode,
                    'inserted_questions' => 0,
                    'inserted_options' => 0,
                    'difficulties' => [],
                ];

                foreach (self::DIFFICULTY_DISTRIBUTION as $difficulty => $targetCount) {
                    $existingCount = KcatQuestion::query()
                        ->where('kcat_test_id', $test->id)
                        ->where('kcat_section_id', $section->id)
                        ->where('difficulty', $difficulty)
                        ->where('is_active', true)
                        ->count();

                    $needed = max(0, $targetCount - $existingCount);
                    $label = self::SECTION_LABELS[$sectionCode] ?? $sectionName;
                    $this->emit($progress, 'Generating '.$label.' '.ucfirst($difficulty).'...');

                    if ($needed === 0) {
                        $this->emit($progress, 'Skipped '.$label.' '.ucfirst($difficulty).' (target already met).');
                        $sectionSummary['difficulties'][$difficulty] = [
                            'target' => $targetCount,
                            'existing' => $existingCount,
                            'generated' => 0,
                            'inserted' => 0,
                        ];
                        continue;
                    }

                    $generated = $this->generateForSection($generator, $sectionCode, $needed, $difficulty);
                    if (count($generated) !== $needed) {
                        throw new RuntimeException('Generation count mismatch for '.$sectionCode.' '.$difficulty.'.');
                    }

                    $insertCounts = ['questions' => 0, 'options' => 0];
                    if (! $dryRun) {
                        $insertCounts = $this->insertQuestionsWithOptions($test, $section, $generated);
                    } else {
                        $insertCounts['questions'] = count($generated);
                        $insertCounts['options'] = count($generated) * 4;
                    }

                    $summary['inserted_questions'] += $insertCounts['questions'];
                    $summary['inserted_options'] += $insertCounts['options'];
                    $sectionSummary['inserted_questions'] += $insertCounts['questions'];
                    $sectionSummary['inserted_options'] += $insertCounts['options'];
                    $sectionSummary['difficulties'][$difficulty] = [
                        'target' => $targetCount,
                        'existing' => $existingCount,
                        'generated' => count($generated),
                        'inserted' => $insertCounts['questions'],
                    ];

                    $this->emit($progress, 'Inserted '.$insertCounts['questions'].' questions for '.$label.' '.ucfirst($difficulty).'.');
                }

                $summary['sections'][$sectionCode] = $sectionSummary;
            }
        });

        if (! $dryRun) {
            $testService->refreshTotals($test->fresh(['sections.questions']));
        }

        return $summary;
    }

    private function emit(?callable $progress, string $message): void
    {
        if ($progress !== null) {
            $progress($message);
        }
    }

    private function resolveDefaultTest(?callable $progress): KcatTest
    {
        $defaultTitle = 'KORT Cognitive Assessment Test - Level 1';

        $test = KcatTest::query()
            ->where('title', $defaultTitle)
            ->orWhere('title', 'like', 'KORT Cognitive Assessment Test%')
            ->orderBy('id')
            ->first();

        if ($test instanceof KcatTest) {
            $this->emit($progress, 'Using KCAT test #'.$test->id.' ('.$test->title.').');

            return $test;
        }

        $this->emit($progress, 'No KCAT test found. Creating default test...');

        return KcatTest::query()->create([
            'title' => $defaultTitle,
            'description' => 'Generated original KCAT question bank for reasoning practice.',
            'grade_from' => 7,
            'grade_to' => 12,
            'duration_minutes' => 45,
            'status' => 'draft',
            'session' => $this->currentSession(),
        ]);
    }

    /**
     * @return array<string, KcatSection>
     */
    private function ensureDefaultSections(KcatTest $test): array
    {
        $sections = [];
        $sortOrder = 1;

        foreach (KcatTestService::DEFAULT_SECTIONS as $code => $name) {
            $sections[$code] = KcatSection::query()->firstOrCreate(
                ['kcat_test_id' => $test->id, 'code' => $code],
                [
                    'name' => $name,
                    'description' => null,
                    'sort_order' => $sortOrder,
                    'total_questions' => 0,
                    'total_marks' => 0,
                ]
            );

            $sortOrder++;
        }

        return $sections;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function generateForSection(
        KcatQuestionGeneratorService $generator,
        string $sectionCode,
        int $count,
        string $difficulty
    ): array {
        return match ($sectionCode) {
            'verbal_reasoning' => $generator->generateVerbalQuestions($count, $difficulty),
            'quantitative_reasoning' => $generator->generateQuantitativeQuestions($count, $difficulty),
            'non_verbal_reasoning' => $generator->generateNonVerbalQuestions($count, $difficulty),
            'spatial_reasoning' => $generator->generateSpatialQuestions($count, $difficulty),
            default => throw new RuntimeException('Unsupported section code: '.$sectionCode),
        };
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     * @return array{questions:int,options:int}
     */
    private function insertQuestionsWithOptions(KcatTest $test, KcatSection $section, array $questions): array
    {
        $questionInsertCount = 0;
        $optionInsertCount = 0;
        $sortOrder = (int) KcatQuestion::query()
            ->where('kcat_test_id', $test->id)
            ->where('kcat_section_id', $section->id)
            ->max('sort_order');
        $sortOrder++;

        foreach (array_chunk($questions, 100) as $chunk) {
            $timestamp = now()->format('Y-m-d H:i:s');
            $questionRows = [];

            foreach ($chunk as $question) {
                $questionRows[] = [
                    'kcat_test_id' => $test->id,
                    'kcat_section_id' => $section->id,
                    'question_type' => (string) ($question['question_type'] ?? 'mcq'),
                    'difficulty' => (string) ($question['difficulty'] ?? 'medium'),
                    'question_text' => (string) ($question['question_text'] ?? ''),
                    'question_image' => $question['question_image'] ?? null,
                    'explanation' => $question['explanation'] ?? null,
                    'marks' => (int) ($question['marks'] ?? 1),
                    'sort_order' => $sortOrder++,
                    'is_active' => true,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            if ($questionRows === []) {
                continue;
            }

            DB::table('kcat_questions')->insert($questionRows);
            $questionInsertCount += count($questionRows);

            $questionIdsByText = DB::table('kcat_questions')
                ->where('kcat_test_id', $test->id)
                ->where('kcat_section_id', $section->id)
                ->where('created_at', $timestamp)
                ->whereIn('question_text', array_column($questionRows, 'question_text'))
                ->pluck('id', 'question_text')
                ->map(static fn ($value): int => (int) $value)
                ->all();

            $optionRows = [];
            foreach ($chunk as $question) {
                $questionText = (string) ($question['question_text'] ?? '');
                $questionId = (int) ($questionIdsByText[$questionText] ?? 0);
                if ($questionId <= 0) {
                    continue;
                }

                $options = $question['options'] ?? [];
                foreach ($options as $optionIndex => $option) {
                    $optionRows[] = [
                        'kcat_question_id' => $questionId,
                        'option_text' => $option['option_text'] ?? null,
                        'option_image' => $option['option_image'] ?? null,
                        'is_correct' => (bool) ($option['is_correct'] ?? false),
                        'sort_order' => (int) ($option['sort_order'] ?? ($optionIndex + 1)),
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }
            }

            foreach (array_chunk($optionRows, 400) as $optionChunk) {
                if ($optionChunk === []) {
                    continue;
                }

                DB::table('kcat_question_options')->insert($optionChunk);
                $optionInsertCount += count($optionChunk);
            }
        }

        return ['questions' => $questionInsertCount, 'options' => $optionInsertCount];
    }

    private function currentSession(): string
    {
        $now = now();
        $startYear = $now->month >= 7 ? $now->year : ($now->year - 1);

        return $startYear.'-'.($startYear + 1);
    }
}
