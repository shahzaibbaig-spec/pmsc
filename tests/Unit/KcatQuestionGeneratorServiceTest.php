<?php

namespace Tests\Unit;

use App\Services\Kcat\KcatQuestionGeneratorService;
use Tests\TestCase;

class KcatQuestionGeneratorServiceTest extends TestCase
{
    public function test_it_generates_valid_verbal_questions(): void
    {
        $generator = app(KcatQuestionGeneratorService::class);
        $questions = $generator->generateVerbalQuestions(20, 'easy');

        $this->assertCount(20, $questions);
        $this->assertQuestionsAreValid($questions, 'easy');
    }

    public function test_it_generates_valid_quantitative_questions(): void
    {
        $generator = app(KcatQuestionGeneratorService::class);
        $questions = $generator->generateQuantitativeQuestions(20, 'medium');

        $this->assertCount(20, $questions);
        $this->assertQuestionsAreValid($questions, 'medium');
    }

    public function test_it_generates_valid_non_verbal_questions(): void
    {
        $generator = app(KcatQuestionGeneratorService::class);
        $questions = $generator->generateNonVerbalQuestions(20, 'hard');

        $this->assertCount(20, $questions);
        $this->assertQuestionsAreValid($questions, 'hard');
    }

    public function test_it_generates_valid_spatial_questions(): void
    {
        $generator = app(KcatQuestionGeneratorService::class);
        $questions = $generator->generateSpatialQuestions(20, 'medium');

        $this->assertCount(20, $questions);
        $this->assertQuestionsAreValid($questions, 'medium');
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     */
    private function assertQuestionsAreValid(array $questions, string $difficulty): void
    {
        $texts = [];

        foreach ($questions as $question) {
            $this->assertSame($difficulty, $question['difficulty']);
            $this->assertSame(1, $question['marks']);
            $this->assertNotEmpty(trim((string) ($question['question_text'] ?? '')));
            $this->assertNotEmpty(trim((string) ($question['explanation'] ?? '')));
            $this->assertCount(4, $question['options']);

            $correct = 0;
            foreach ($question['options'] as $option) {
                $this->assertNotEmpty(trim((string) ($option['option_text'] ?? '')));
                if ((bool) ($option['is_correct'] ?? false)) {
                    $correct++;
                }
            }

            $this->assertSame(1, $correct, 'Each question must have exactly one correct option.');
            $texts[] = mb_strtolower(trim((string) $question['question_text']));
        }

        $this->assertSameSize(array_unique($texts), $texts, 'Questions should be unique within a generated set.');
    }
}
