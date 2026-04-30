<?php

namespace Database\Seeders;

use App\Models\KcatQuestion;
use App\Models\KcatQuestionOption;
use App\Models\KcatSection;
use App\Models\KcatTest;
use App\Services\Kcat\KcatTestService;
use Illuminate\Database\Seeder;

class KcatDefaultSeeder extends Seeder
{
    public function run(): void
    {
        $test = KcatTest::query()->firstOrCreate(
            ['title' => 'KORT Cognitive Assessment Test - Level 1'],
            [
                'description' => 'Demo KCAT template with original sample questions for setup testing. Replace with school-approved question bank before formal use.',
                'grade_from' => 7,
                'grade_to' => 12,
                'duration_minutes' => 45,
                'status' => 'draft',
                'session' => $this->currentSession(),
            ]
        );

        $questions = [
            'verbal_reasoning' => [
                ['Demo verbal analogy: Seed is to plant as idea is to ___.', ['book', 'plan', 'rain', 'chair'], 1],
                ['Demo odd word out: Which word does not belong?', ['observe', 'notice', 'ignore', 'watch'], 2],
            ],
            'quantitative_reasoning' => [
                ['Demo number series: 3, 6, 12, 24, ___.', ['30', '36', '48', '60'], 2],
                ['Demo logic count: If 4 notebooks cost 200, what do 6 notebooks cost at the same rate?', ['250', '300', '320', '400'], 1],
            ],
            'non_verbal_reasoning' => [
                ['Demo pattern: Circle, Square, Circle, Square, ___.', ['Circle', 'Triangle', 'Rectangle', 'Line'], 0],
                ['Demo matrix idea: A row changes from small to medium to large. What comes after medium?', ['small', 'large', 'same', 'blank'], 1],
            ],
            'spatial_reasoning' => [
                ['Demo rotation: An arrow pointing up is turned 90 degrees clockwise. It points ___.', ['left', 'right', 'down', 'up'], 1],
                ['Demo folding: A square paper folded in half forms which basic shape?', ['circle', 'triangle', 'rectangle', 'star'], 2],
            ],
        ];

        $sort = 1;
        foreach (KcatTestService::DEFAULT_SECTIONS as $code => $name) {
            $section = KcatSection::query()->firstOrCreate(
                ['kcat_test_id' => $test->id, 'code' => $code],
                ['name' => $name, 'sort_order' => $sort]
            );

            if (! $section->questions()->exists()) {
                foreach ($questions[$code] as $index => [$text, $options, $correctIndex]) {
                    $question = KcatQuestion::query()->create([
                        'kcat_test_id' => $test->id,
                        'kcat_section_id' => $section->id,
                        'question_type' => $index === 0 ? 'analogy' : 'mcq',
                        'difficulty' => 'easy',
                        'question_text' => $text,
                        'marks' => 1,
                        'sort_order' => $index + 1,
                        'is_active' => true,
                    ]);

                    foreach ($options as $optionIndex => $optionText) {
                        KcatQuestionOption::query()->create([
                            'kcat_question_id' => $question->id,
                            'option_text' => $optionText,
                            'is_correct' => $optionIndex === $correctIndex,
                            'sort_order' => $optionIndex + 1,
                        ]);
                    }
                }
            }

            $sort++;
        }

        app(KcatTestService::class)->refreshTotals($test);
    }

    private function currentSession(): string
    {
        $now = now();
        $startYear = $now->month >= 7 ? $now->year : ($now->year - 1);

        return $startYear.'-'.($startYear + 1);
    }
}
