<?php

namespace App\Services\Kcat;

use RuntimeException;

class KcatQuestionGeneratorService
{
    /**
     * @var array<string, array<string, bool>>
     */
    private array $usedSignatures = [
        'verbal_reasoning' => [],
        'quantitative_reasoning' => [],
        'non_verbal_reasoning' => [],
        'spatial_reasoning' => [],
    ];

    private int $patternImageCounter = 1;

    /**
     * @param array<int, string> $questionTexts
     */
    public function primeUsedSignatures(string $sectionCode, array $questionTexts): void
    {
        if (! array_key_exists($sectionCode, $this->usedSignatures)) {
            return;
        }

        foreach ($questionTexts as $questionText) {
            $signature = $this->questionSignature($questionText);
            if ($signature === '') {
                continue;
            }

            $this->usedSignatures[$sectionCode][$signature] = true;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function generateVerbalQuestions(int $count, string $difficulty): array
    {
        return $this->generateQuestions(
            'verbal_reasoning',
            $count,
            $difficulty,
            ['analogy', 'synonym', 'antonym', 'odd_one_out', 'sentence_completion'],
            fn (string $questionType, int $index, string $resolvedDifficulty): array => $this->buildVerbalQuestion($questionType, $resolvedDifficulty, $index)
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function generateQuantitativeQuestions(int $count, string $difficulty): array
    {
        return $this->generateQuestions(
            'quantitative_reasoning',
            $count,
            $difficulty,
            ['number_series', 'missing_number', 'pattern_logic', 'ratio_logic'],
            fn (string $questionType, int $index, string $resolvedDifficulty): array => $this->buildQuantitativeQuestion($questionType, $resolvedDifficulty, $index)
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function generateNonVerbalQuestions(int $count, string $difficulty): array
    {
        return $this->generateQuestions(
            'non_verbal_reasoning',
            $count,
            $difficulty,
            ['pattern_sequence', 'matrix', 'odd_shape', 'shape_series'],
            fn (string $questionType, int $index, string $resolvedDifficulty): array => $this->buildNonVerbalQuestion($questionType, $resolvedDifficulty, $index)
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function generateSpatialQuestions(int $count, string $difficulty): array
    {
        return $this->generateQuestions(
            'spatial_reasoning',
            $count,
            $difficulty,
            ['rotation', 'mirror_image', 'folding', 'cube_logic'],
            fn (string $questionType, int $index, string $resolvedDifficulty): array => $this->buildSpatialQuestion($questionType, $resolvedDifficulty, $index)
        );
    }

    /**
     * @param array<int, string> $questionTypes
     * @param callable(string,int,string):array<string,mixed> $builder
     * @return array<int, array<string, mixed>>
     */
    private function generateQuestions(
        string $sectionCode,
        int $count,
        string $difficulty,
        array $questionTypes,
        callable $builder
    ): array {
        $resolvedDifficulty = $this->resolveDifficulty($difficulty);
        $initialSectionSignatures = $this->usedSignatures[$sectionCode] ?? [];
        $questions = [];
        $attempts = 0;
        $maxAttempts = max($count * 80, 200);

        while (count($questions) < $count && $attempts < $maxAttempts) {
            // Rotate by attempt count so we don't get stuck on a single exhausted type.
            $questionType = $questionTypes[$attempts % count($questionTypes)];
            $question = $builder($questionType, $attempts, $resolvedDifficulty);
            $attempts++;

            $questionText = (string) ($question['question_text'] ?? '');
            if (! $this->rememberQuestionSignature($sectionCode, $questionText)) {
                continue;
            }

            if (! $this->hasSingleCorrectOption($question['options'] ?? [])) {
                continue;
            }

            $questions[] = $question;
        }

        if (count($questions) < $count) {
            // Roll back partial signature reservations so callers can retry with a smaller target.
            $this->usedSignatures[$sectionCode] = $initialSectionSignatures;
            throw new RuntimeException('Unable to generate enough unique questions for section '.$sectionCode.' ('.$difficulty.').');
        }

        return $questions;
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public function snapshotUsedSignatures(): array
    {
        return $this->usedSignatures;
    }

    /**
     * @param array<string, array<string, bool>> $snapshot
     */
    public function restoreUsedSignatures(array $snapshot): void
    {
        $this->usedSignatures = $snapshot;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildVerbalQuestion(string $questionType, string $difficulty, int $index): array
    {
        $variant = intdiv($index, 5);

        return match ($questionType) {
            'analogy' => $this->buildVerbalAnalogyQuestion($difficulty, $variant),
            'synonym' => $this->buildVerbalSynonymQuestion($difficulty, $variant),
            'antonym' => $this->buildVerbalAntonymQuestion($difficulty, $variant),
            'odd_one_out' => $this->buildVerbalOddOneOutQuestion($difficulty, $variant),
            'sentence_completion' => $this->buildVerbalSentenceCompletionQuestion($difficulty, $variant),
            default => throw new RuntimeException('Unsupported verbal question type: '.$questionType),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function buildQuantitativeQuestion(string $questionType, string $difficulty, int $index): array
    {
        $variant = intdiv($index, 4);

        return match ($questionType) {
            'number_series' => $this->buildQuantitativeNumberSeriesQuestion($difficulty, $variant),
            'missing_number' => $this->buildQuantitativeMissingNumberQuestion($difficulty, $variant),
            'pattern_logic' => $this->buildQuantitativePatternLogicQuestion($difficulty, $variant),
            'ratio_logic' => $this->buildQuantitativeRatioLogicQuestion($difficulty, $variant),
            default => throw new RuntimeException('Unsupported quantitative question type: '.$questionType),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function buildNonVerbalQuestion(string $questionType, string $difficulty, int $index): array
    {
        $variant = intdiv($index, 4);

        return match ($questionType) {
            'pattern_sequence' => $this->buildNonVerbalPatternSequenceQuestion($difficulty, $variant),
            'matrix' => $this->buildNonVerbalMatrixQuestion($difficulty, $variant),
            'odd_shape' => $this->buildNonVerbalOddShapeQuestion($difficulty, $variant),
            'shape_series' => $this->buildNonVerbalShapeSeriesQuestion($difficulty, $variant),
            default => throw new RuntimeException('Unsupported non-verbal question type: '.$questionType),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSpatialQuestion(string $questionType, string $difficulty, int $index): array
    {
        $variant = intdiv($index, 4);

        return match ($questionType) {
            'rotation' => $this->buildSpatialRotationQuestion($difficulty, $variant),
            'mirror_image' => $this->buildSpatialMirrorQuestion($difficulty, $variant),
            'folding' => $this->buildSpatialFoldingQuestion($difficulty, $variant),
            'cube_logic' => $this->buildSpatialCubeLogicQuestion($difficulty, $variant),
            default => throw new RuntimeException('Unsupported spatial question type: '.$questionType),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function buildVerbalAnalogyQuestion(string $difficulty, int $index): array
    {
        $relations = $this->verbalAnalogyRelations($difficulty);
        $relation = $relations[$index % count($relations)];
        $pairs = $relation['pairs'];

        $firstPair = $pairs[$index % count($pairs)];
        $secondPair = $pairs[($index + 3) % count($pairs)];
        if ($firstPair[0] === $secondPair[0]) {
            $secondPair = $pairs[($index + 1) % count($pairs)];
        }

        $correct = $secondPair[1];
        $sameRelationDistractors = array_values(array_filter(
            array_map(static fn (array $pair): string => $pair[1], $pairs),
            static fn (string $value): bool => $value !== $correct
        ));

        $allAlternatives = [];
        foreach ($relations as $candidate) {
            foreach ($candidate['pairs'] as $pair) {
                $allAlternatives[] = $pair[1];
            }
        }

        $distractors = $this->pickDistractors($correct, array_merge($sameRelationDistractors, $allAlternatives), 3, $index);
        $questionText = $firstPair[0].' : '.$firstPair[1].' :: '.$secondPair[0].' : ?';
        $explanation = 'The first pair shows "'.$relation['label'].'". The second pair follows the same relationship.';

        return $this->composeQuestion('analogy', $difficulty, $questionText, $correct, $distractors, $explanation);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildVerbalSynonymQuestion(string $difficulty, int $index): array
    {
        $items = $this->verbalSynonymBank($difficulty);
        $item = $items[$index % count($items)];
        $correct = $item['answer'];

        $pool = array_values(array_filter(
            array_map(static fn (array $entry): string => $entry['answer'], $items),
            static fn (string $value): bool => $value !== $correct
        ));

        $distractors = $this->pickDistractors($correct, $pool, 3, $index);
        $questionText = 'Choose the word closest in meaning to "'.$item['word'].'".';
        $explanation = '"'.$correct.'" is the closest meaning of "'.$item['word'].'".';

        return $this->composeQuestion('synonym', $difficulty, $questionText, $correct, $distractors, $explanation);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildVerbalAntonymQuestion(string $difficulty, int $index): array
    {
        $items = $this->verbalAntonymBank($difficulty);
        $item = $items[$index % count($items)];
        $correct = $item['answer'];

        $pool = array_values(array_filter(
            array_map(static fn (array $entry): string => $entry['answer'], $items),
            static fn (string $value): bool => $value !== $correct
        ));

        $distractors = $this->pickDistractors($correct, $pool, 3, $index + 11);
        $questionText = 'Choose the opposite meaning of "'.$item['word'].'".';
        $explanation = '"'.$correct.'" is opposite in meaning to "'.$item['word'].'".';

        return $this->composeQuestion('antonym', $difficulty, $questionText, $correct, $distractors, $explanation);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildVerbalOddOneOutQuestion(string $difficulty, int $index): array
    {
        $categories = $this->verbalOddOneOutCategories($difficulty);
        $mainCategory = $categories[$index % count($categories)];
        $outsiderCategory = $categories[($index + 2) % count($categories)];

        $groupWords = $mainCategory['words'];
        $mainOne = $groupWords[$index % count($groupWords)];
        $mainTwo = $groupWords[($index + 1) % count($groupWords)];
        $mainThree = $groupWords[($index + 3) % count($groupWords)];
        $outsider = $outsiderCategory['words'][($index + 4) % count($outsiderCategory['words'])];

        $correct = $outsider;
        $distractors = [$mainOne, $mainTwo, $mainThree];
        $questionText = 'Which word is the odd one out in this set: '.$mainOne.', '.$mainTwo.', '.$mainThree.', '.$outsider.'?';
        $explanation = $outsider.' does not belong to the '.$mainCategory['label'].' group.';

        return $this->composeQuestion('odd_one_out', $difficulty, $questionText, $correct, $distractors, $explanation);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildVerbalSentenceCompletionQuestion(string $difficulty, int $index): array
    {
        $actions = $this->verbalActionBank($difficulty);
        $first = $actions[$index % count($actions)];
        $second = $actions[($index + 5) % count($actions)];
        if ($first['verb'] === $second['verb']) {
            $second = $actions[($index + 2) % count($actions)];
        }

        $correct = $second['verb'];
        $pool = array_values(array_filter(
            array_map(static fn (array $entry): string => $entry['verb'], $actions),
            static fn (string $verb): bool => $verb !== $correct
        ));
        $distractors = $this->pickDistractors($correct, $pool, 3, $index + 17);

        $questionText = $first['subject'].' '.$first['verb'].' '.$first['object'].'. '
            .$second['subject'].' ___ '.$second['object'].'.';
        $explanation = $second['subject'].' logically "'.$correct.'" '.$second['object'].'.';

        return $this->composeQuestion('sentence_completion', $difficulty, $questionText, $correct, $distractors, $explanation);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildQuantitativeNumberSeriesQuestion(string $difficulty, int $index): array
    {
        $mode = $index % 3;

        if ($difficulty === 'easy') {
            if ($mode === 0) {
                $start = 4 + ($index % 9);
                $step = 2 + ($index % 5);
                $series = [$start];
                for ($i = 1; $i < 5; $i++) {
                    $series[] = $series[$i - 1] + $step;
                }

                $answer = $series[4] + $step;
                $explanation = 'Each term increases by '.$step.'.';
            } elseif ($mode === 1) {
                $start = 2 + ($index % 6);
                $multiplier = 2 + ($index % 2);
                $series = [$start];
                for ($i = 1; $i < 5; $i++) {
                    $series[] = $series[$i - 1] * $multiplier;
                }

                $answer = $series[4] * $multiplier;
                $explanation = 'Each term is multiplied by '.$multiplier.'.';
            } else {
                $start = 5 + ($index % 7);
                $addA = 2 + ($index % 3);
                $addB = 4 + ($index % 4);
                $series = [$start];
                for ($i = 1; $i < 5; $i++) {
                    $series[] = $series[$i - 1] + ($i % 2 === 1 ? $addA : $addB);
                }

                $answer = $series[4] + (5 % 2 === 1 ? $addA : $addB);
                $explanation = 'The pattern alternates +'.$addA.' and +'.$addB.'.';
            }
        } elseif ($difficulty === 'medium') {
            if ($mode === 0) {
                $start = 8 + ($index % 11);
                $difference = 3 + ($index % 4);
                $growth = 1 + ($index % 3);
                $series = [$start];
                for ($i = 1; $i < 5; $i++) {
                    $series[] = $series[$i - 1] + $difference;
                    $difference += $growth;
                }
                $answer = $series[4] + $difference;
                $explanation = 'Differences grow by '.$growth.' each step.';
            } elseif ($mode === 1) {
                $start = 3 + ($index % 7);
                $multiplier = 2 + ($index % 2);
                $addition = 1 + ($index % 4);
                $series = [$start];
                for ($i = 1; $i < 5; $i++) {
                    $series[] = ($series[$i - 1] * $multiplier) + $addition;
                }
                $answer = ($series[4] * $multiplier) + $addition;
                $explanation = 'Each term follows (previous * '.$multiplier.') + '.$addition.'.';
            } else {
                $startA = 2 + ($index % 5);
                $startB = 7 + ($index % 6);
                $series = [$startA, $startB];
                for ($i = 2; $i < 5; $i++) {
                    if ($i % 2 === 0) {
                        $series[] = $series[$i - 2] + 3;
                    } else {
                        $series[] = $series[$i - 2] + 4;
                    }
                }
                $answer = $series[3] + 4;
                $explanation = 'Odd and even positions form two separate +3 and +4 sequences.';
            }
        } else {
            if ($mode === 0) {
                $coefficient = 1 + ($index % 3);
                $linear = 2 + ($index % 4);
                $constant = 1 + ($index % 5);
                $series = [];
                for ($n = 1; $n <= 5; $n++) {
                    $series[] = ($coefficient * $n * $n) + ($linear * $n) + $constant;
                }
                $n = 6;
                $answer = ($coefficient * $n * $n) + ($linear * $n) + $constant;
                $explanation = 'Terms follow the quadratic rule '.$coefficient.'n^2 + '.$linear.'n + '.$constant.'.';
            } elseif ($mode === 1) {
                $start = 10 + ($index % 9);
                $multiplier = 2 + ($index % 3);
                $subA = 1 + ($index % 4);
                $subB = 2 + ($index % 5);
                $series = [$start];
                for ($i = 1; $i < 5; $i++) {
                    $subtract = $i % 2 === 1 ? $subA : $subB;
                    $series[] = ($series[$i - 1] * $multiplier) - $subtract;
                }
                $answer = ($series[4] * $multiplier) - (5 % 2 === 1 ? $subA : $subB);
                $explanation = 'Each step multiplies by '.$multiplier.' then subtracts '.$subA.' and '.$subB.' alternately.';
            } else {
                $first = 3 + ($index % 5);
                $second = 6 + ($index % 7);
                $series = [$first, $second];
                for ($i = 2; $i < 5; $i++) {
                    $series[] = $series[$i - 1] + $series[$i - 2] + 1;
                }
                $answer = $series[4] + $series[3] + 1;
                $explanation = 'Each term is the sum of previous two terms plus 1.';
            }
        }

        $distractors = $this->numericDistractors($answer, $difficulty, $index);
        $questionText = 'Find the next number in the series: '.implode(', ', $series).', ?';

        return $this->composeQuestion('number_series', $difficulty, $questionText, (string) $answer, $distractors, $explanation);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildQuantitativeMissingNumberQuestion(string $difficulty, int $index): array
    {
        if ($difficulty === 'easy') {
            $base = 4 + ($index % 8);
            $add = 3 + ($index % 5);
            $result = $base + $add;
            $questionText = 'Missing number: '.$base.' + '.$add.' = '.$result.', '.$result.' - '.$base.' = ?';
            $answer = $add;
            $explanation = 'Subtract '.$base.' from '.$result.' to get '.$add.'.';
        } elseif ($difficulty === 'medium') {
            $inputA = 2 + ($index % 7);
            $inputB = 5 + ($index % 6);
            $ruleAdd = 1 + ($index % 4);
            $valueA = ($inputA * 3) + $ruleAdd;
            $valueB = ($inputB * 3) + $ruleAdd;
            $unknownInput = 8 + ($index % 5);
            $answer = ($unknownInput * 3) + $ruleAdd;
            $questionText = 'If '.$inputA.' -> '.$valueA.' and '.$inputB.' -> '.$valueB.', then '.$unknownInput.' -> ?';
            $explanation = 'The rule is n * 3 + '.$ruleAdd.'.';
        } else {
            $a = 3 + ($index % 6);
            $b = 5 + ($index % 7);
            $c = 2 + ($index % 5);
            $left = ($a * $b) + $c;
            $unknownB = 6 + ($index % 5);
            $answer = ($a * $unknownB) + $c;
            $questionText = 'Pattern rule: (x * y) + z. If ('.$a.' * '.$b.') + '.$c.' = '.$left.', then ('.$a.' * '.$unknownB.') + '.$c.' = ?';
            $explanation = 'Apply the same rule: '.$a.' * '.$unknownB.' + '.$c.'.';
        }

        $distractors = $this->numericDistractors($answer, $difficulty, $index + 23);

        return $this->composeQuestion('missing_number', $difficulty, $questionText, (string) $answer, $distractors, $explanation);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildQuantitativePatternLogicQuestion(string $difficulty, int $index): array
    {
        if ($difficulty === 'easy') {
            $input = 6 + ($index % 8);
            $answer = ($input * 2) + 1;
            $questionText = 'A rule machine does: (n * 2) + 1. What is the output for n = '.$input.'?';
            $explanation = 'Double '.$input.' and add 1.';
        } elseif ($difficulty === 'medium') {
            $input = 7 + ($index % 9);
            $subtract = 2 + ($index % 4);
            $multiply = 3 + ($index % 2);
            $answer = ($input - $subtract) * $multiply;
            $questionText = 'A code rule does: first subtract '.$subtract.', then multiply by '.$multiply.'. What is the output for '.$input.'?';
            $explanation = '('.$input.' - '.$subtract.') * '.$multiply.' = '.$answer.'.';
        } else {
            $input = 10 + ($index % 10);
            $add = 3 + ($index % 5);
            $squarePart = $input + $add;
            $divide = 2 + ($index % 3);
            $answer = (int) floor(($squarePart * $squarePart) / $divide);
            $questionText = 'A rule does: add '.$add.', square the result, then divide by '.$divide.' and keep the whole number. What is the output for '.$input.'?';
            $explanation = '(('.$input.' + '.$add.')^2) / '.$divide.' gives '.$answer.' as whole-number output.';
        }

        $distractors = $this->numericDistractors($answer, $difficulty, $index + 41);

        return $this->composeQuestion('pattern_logic', $difficulty, $questionText, (string) $answer, $distractors, $explanation);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildQuantitativeRatioLogicQuestion(string $difficulty, int $index): array
    {
        if ($difficulty === 'easy') {
            $ratioA = 2 + ($index % 4);
            $ratioB = 3 + ($index % 5);
            $multiplier = 2 + ($index % 4);
            $questionText = 'In a ratio '.$ratioA.':'.$ratioB.', if the first value is '.$ratioA * $multiplier.', what is the second value?';
            $answer = $ratioB * $multiplier;
            $explanation = 'Both parts are multiplied by '.$multiplier.'.';
        } elseif ($difficulty === 'medium') {
            $partA = 3 + ($index % 5);
            $partB = 4 + ($index % 6);
            $total = ($partA + $partB) * (5 + ($index % 4));
            $questionText = 'Two numbers are in ratio '.$partA.':'.$partB.' and their sum is '.$total.'. What is the larger number?';
            $unit = (int) ($total / ($partA + $partB));
            $answer = max($partA, $partB) * $unit;
            $explanation = 'One ratio unit is '.$unit.'. Multiply by the larger part.';
        } else {
            $a = 4 + ($index % 6);
            $b = 7 + ($index % 6);
            $changeA = 2 + ($index % 3);
            $changeB = 3 + ($index % 4);
            $questionText = 'A mixture has ratio '.$a.':'.$b.'. After adding '.$changeA.' parts to first and '.$changeB.' parts to second, what is the new ratio of first to second?';
            $newA = $a + $changeA;
            $newB = $b + $changeB;
            $gcd = $this->greatestCommonDivisor($newA, $newB);
            $simplifiedA = (int) ($newA / $gcd);
            $simplifiedB = (int) ($newB / $gcd);
            $answer = $simplifiedA.':'.$simplifiedB;
            $explanation = 'New ratio is '.$newA.':'.$newB.', simplified to '.$answer.'.';
        }

        $distractors = $difficulty === 'hard'
            ? $this->ratioDistractors((string) $answer, $index)
            : $this->numericDistractors((int) $answer, $difficulty, $index + 59);

        return $this->composeQuestion(
            'ratio_logic',
            $difficulty,
            $questionText,
            (string) $answer,
            $distractors,
            $explanation
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildNonVerbalPatternSequenceQuestion(string $difficulty, int $index): array
    {
        $shapes = ['TRI', 'CIR', 'SQR', 'DIA', 'HEX', 'STAR'];

        if ($difficulty === 'easy') {
            $a = $shapes[$index % count($shapes)];
            $b = $shapes[($index + 1) % count($shapes)];
            $sequence = [$a, $b, $a, $b];
            $answer = $a;
            $explanation = 'The pattern repeats '.$a.', '.$b.'.';
        } elseif ($difficulty === 'medium') {
            $a = $shapes[$index % count($shapes)];
            $b = $shapes[($index + 2) % count($shapes)];
            $c = $shapes[($index + 4) % count($shapes)];
            $sequence = [$a, $b, $c, $a, $b, $c];
            $answer = $a;
            $explanation = 'The sequence cycles through three shapes: '.$a.', '.$b.', '.$c.'.';
        } else {
            $a = $shapes[$index % count($shapes)];
            $b = $shapes[($index + 1) % count($shapes)];
            $c = $shapes[($index + 3) % count($shapes)];
            $level = 1 + intdiv($index, count($shapes));
            $sequence = [
                $a.'-'.$level,
                $a.'-'.($level + 1),
                $b.'-'.$level,
                $a.'-'.($level + 2),
                $a.'-'.($level + 3),
                $b.'-'.($level + 1),
                $c.'-'.$level,
                $c.'-'.($level + 1),
                '?',
            ];
            $answer = $c.'-'.($level + 2);
            $explanation = 'Each shape keeps its own running number progression across grouped positions.';
        }

        $pool = $difficulty === 'hard'
            ? [
                'TRI-1', 'TRI-2', 'TRI-3', 'TRI-4',
                'CIR-1', 'CIR-2', 'CIR-3', 'CIR-4',
                'SQR-1', 'SQR-2', 'SQR-3', 'SQR-4',
                'DIA-1', 'DIA-2', 'DIA-3', 'DIA-4',
                'HEX-1', 'HEX-2', 'HEX-3', 'HEX-4',
                'STAR-1', 'STAR-2', 'STAR-3', 'STAR-4',
            ]
            : array_values(array_filter($shapes, static fn (string $shape): bool => $shape !== $answer));
        $distractors = $this->pickDistractors($answer, $pool, 3, $index + 71);
        $sequenceText = '['.implode(', ', $sequence).']';
        $questionText = 'Complete the pattern sequence: '.$sequenceText;

        return $this->composeQuestion('pattern_sequence', $difficulty, $questionText, $answer, $distractors, $explanation);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildNonVerbalMatrixQuestion(string $difficulty, int $index): array
    {
        $shapes = ['TRI', 'CIR', 'SQR', 'DIA', 'HEX', 'STAR'];
        $questionImage = null;

        if ($difficulty === 'easy') {
            $first = $shapes[$index % count($shapes)];
            $second = $shapes[($index + 1) % count($shapes)];
            $third = $shapes[($index + 2) % count($shapes)];
            $questionText = 'Matrix pattern:'."\n"
                .'Row 1: '.$first.'  '.$second.'  '.$third."\n"
                .'Row 2: '.$second.'  '.$third.'  ?'."\n"
                .'The order shifts one step right. What is ?';
            $answer = $first;
            $explanation = 'Row 2 continues the same order: '.$second.' -> '.$third.' -> '.$first.'.';
        } elseif ($difficulty === 'medium') {
            $first = $shapes[$index % count($shapes)];
            $second = $shapes[($index + 2) % count($shapes)];
            $questionText = 'Matrix pattern:'."\n"
                .'Row 1: 1-'.$first.'   2-'.$second."\n"
                .'Row 2: 2-'.$first.'   3-'.$second."\n"
                .'Row 3: 3-'.$first.'   ?'."\n"
                .'Numbers increase by 1 down each column. What is ?';
            $answer = '4-'.$second;
            $explanation = 'Second-column number increases 2, 3, 4 while shape stays '.$second.'.';
        } else {
            $a = $shapes[$index % count($shapes)];
            $b = $shapes[($index + 1) % count($shapes)];
            $c = $shapes[($index + 2) % count($shapes)];
            $offset = 1 + intdiv($index, count($shapes));
            $questionText = 'Matrix pattern:'."\n"
                .'Row 1: '.$a.'-'.$offset.'   '.$b.'-'.($offset + 1).'   '.$c.'-'.($offset + 2)."\n"
                .'Row 2: '.$b.'-'.($offset + 1).'   '.$c.'-'.($offset + 2).'   '.$a.'-'.($offset + 3)."\n"
                .'Row 3: '.$c.'-'.($offset + 2).'   '.$a.'-'.($offset + 3).'   ?'."\n"
                .'Both shape order and number increase are consistent. What is ?';
            $answer = $b.'-'.($offset + 4);
            $explanation = 'Shape cycle shifts right and number part increases by 1 each step.';
            if ($index % 4 === 0) {
                $questionImage = 'pattern_'.$this->patternImageCounter.'.png';
                $this->patternImageCounter++;
            }
        }

        $optionPool = $difficulty === 'hard'
            ? [
                'TRI-4', 'TRI-5', 'CIR-4', 'CIR-5', 'SQR-4', 'SQR-5', 'DIA-4', 'DIA-5', 'HEX-4', 'HEX-5', 'STAR-4', 'STAR-5',
            ]
            : array_map(static fn (string $shape): string => $shape, $shapes);
        $distractors = $this->pickDistractors($answer, $optionPool, 3, $index + 89);

        return $this->composeQuestion('matrix', $difficulty, $questionText, $answer, $distractors, $explanation, $questionImage);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildNonVerbalOddShapeQuestion(string $difficulty, int $index): array
    {
        $groups = [
            ['label' => 'straight-edge polygons', 'items' => ['TRI', 'SQR', 'RECT', 'PENT']],
            ['label' => 'curved shapes', 'items' => ['CIR', 'OVAL', 'ARC', 'RING']],
            ['label' => 'arrow directions', 'items' => ['UP_ARROW', 'RIGHT_ARROW', 'DOWN_ARROW', 'LEFT_ARROW']],
            ['label' => 'star variants', 'items' => ['STAR_4', 'STAR_5', 'STAR_6', 'STAR_8']],
        ];

        $mainGroup = $groups[$index % count($groups)];
        $outsiderGroup = $groups[($index + 1) % count($groups)];
        $offset = intdiv($index, count($groups));
        $mainItems = $mainGroup['items'];
        $outsiderItems = $outsiderGroup['items'];

        $one = $mainItems[($index + $offset) % count($mainItems)];
        $two = $mainItems[($index + $offset + 1) % count($mainItems)];
        $three = $mainItems[($index + $offset + 2) % count($mainItems)];
        $answer = $outsiderItems[($index + $offset + 3) % count($outsiderItems)];

        $questionText = 'Which option is the odd shape out in this set: '.$one.', '.$two.', '.$three.', '.$answer.'?';
        $explanation = $answer.' is not in the '.$mainGroup['label'].' group.';

        return $this->composeQuestion('odd_shape', $difficulty, $questionText, $answer, [$one, $two, $three], $explanation);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildNonVerbalShapeSeriesQuestion(string $difficulty, int $index): array
    {
        $directions = ['UP', 'RIGHT', 'DOWN', 'LEFT'];
        $shapes = ['TRI', 'SQR', 'DIA', 'HEX'];

        if ($difficulty === 'easy') {
            $start = $index % count($directions);
            $sequence = [
                'ARROW_'.$directions[$start],
                'ARROW_'.$directions[($start + 1) % 4],
                'ARROW_'.$directions[($start + 2) % 4],
                'ARROW_'.$directions[($start + 3) % 4],
                '?',
            ];
            $answer = 'ARROW_'.$directions[$start];
            $explanation = 'The arrow rotates 90 degrees each step.';
            $pool = ['ARROW_UP', 'ARROW_RIGHT', 'ARROW_DOWN', 'ARROW_LEFT'];
        } elseif ($difficulty === 'medium') {
            $a = $shapes[$index % count($shapes)];
            $b = $shapes[($index + 1) % count($shapes)];
            $sequence = [$a.'-SMALL', $a.'-LARGE', $b.'-SMALL', $b.'-LARGE', '?'];
            $answer = $a.'-SMALL';
            $explanation = 'Two-shape cycle repeats with size SMALL then LARGE.';
            $pool = [
                $a.'-SMALL', $a.'-LARGE', $b.'-SMALL', $b.'-LARGE',
                $shapes[($index + 2) % count($shapes)].'-SMALL',
            ];
        } else {
            $a = $shapes[$index % count($shapes)];
            $b = $shapes[($index + 2) % count($shapes)];
            $level = 1 + intdiv($index, count($shapes));
            $sequence = [
                $a.'-UP-'.$level,
                $b.'-RIGHT-'.$level,
                $a.'-DOWN-'.($level + 1),
                $b.'-LEFT-'.($level + 1),
                '?',
            ];
            $answer = $a.'-UP-'.($level + 2);
            $explanation = 'Shape cycle repeats while direction returns and level number increases.';
            $pool = [
                $a.'-UP-'.$level,
                $a.'-DOWN-'.$level,
                $b.'-RIGHT-'.$level,
                $b.'-LEFT-'.$level,
                $a.'-UP-'.($level + 1),
                $a.'-DOWN-'.($level + 1),
                $b.'-RIGHT-'.($level + 1),
                $b.'-LEFT-'.($level + 1),
                $a.'-RIGHT-'.($level + 2),
                $b.'-UP-'.($level + 2),
            ];
        }

        $distractors = $this->pickDistractors($answer, $pool, 3, $index + 103);
        $questionText = 'Complete the shape series: ['.implode(', ', $sequence).']';

        return $this->composeQuestion('shape_series', $difficulty, $questionText, $answer, $distractors, $explanation);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSpatialRotationQuestion(string $difficulty, int $index): array
    {
        $directions = ['North', 'East', 'South', 'West'];
        $start = $index % 4;
        $startDirection = $directions[$start];
        $turns = [];

        if ($difficulty === 'easy') {
            $turns[] = ['dir' => 'clockwise', 'degrees' => 90];
            if ($index % 2 === 0) {
                $turns[] = ['dir' => 'clockwise', 'degrees' => 90];
            }
        } elseif ($difficulty === 'medium') {
            $turns[] = ['dir' => 'clockwise', 'degrees' => 180];
            $turns[] = ['dir' => 'counterclockwise', 'degrees' => 90];
            if ($index % 3 === 0) {
                $turns[] = ['dir' => 'clockwise', 'degrees' => 90];
            }
        } else {
            $turns[] = ['dir' => 'clockwise', 'degrees' => 90];
            $turns[] = ['dir' => 'counterclockwise', 'degrees' => 180];
            $turns[] = ['dir' => 'clockwise', 'degrees' => 270];
        }

        $final = $start;
        foreach ($turns as $turn) {
            $steps = (int) ($turn['degrees'] / 90);
            $final += $turn['dir'] === 'clockwise' ? $steps : -$steps;
            $final = (($final % 4) + 4) % 4;
        }

        $answer = $directions[$final];
        $turnText = implode(', ', array_map(static fn (array $turn): string => $turn['degrees'].'° '.$turn['dir'], $turns));
        $questionText = 'An arrow starts facing '.$startDirection.'. It rotates '.$turnText.'. Which direction does it face now?';
        $explanation = 'Apply the turns in order to track the final direction.';

        return $this->composeQuestion('rotation', $difficulty, $questionText, $answer, $this->pickDistractors($answer, $directions, 3, $index + 127), $explanation);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSpatialMirrorQuestion(string $difficulty, int $index): array
    {
        if ($difficulty === 'easy') {
            $x = 2 + ($index % 6);
            $y = 1 + ($index % 7);
            $questionText = 'Point P is at ('.$x.', '.$y.'). What is its mirror image across the y-axis?';
            $answerPoint = [-$x, $y];
            $explanation = 'Reflection across y-axis changes x to -x and keeps y same.';
        } elseif ($difficulty === 'medium') {
            $x = -5 + ($index % 11);
            $y = -4 + (($index + 3) % 9);
            $axis = $index % 2 === 0 ? 'x-axis' : 'y-axis';
            if ($axis === 'x-axis') {
                $answerPoint = [$x, -$y];
                $explanation = 'Reflection across x-axis changes y to -y.';
            } else {
                $answerPoint = [-$x, $y];
                $explanation = 'Reflection across y-axis changes x to -x.';
            }
            $questionText = 'Point Q is at ('.$x.', '.$y.'). What is its mirror image across the '.$axis.'?';
        } else {
            $x = 1 + ($index % 7);
            $y = 2 + (($index + 2) % 7);
            $firstReflection = [-$x, $y];
            $answerPoint = [$firstReflection[0], -$firstReflection[1]];
            $questionText = 'Point R starts at ('.$x.', '.$y.'). It is reflected across the y-axis and then across the x-axis. What is the final point?';
            $explanation = 'After y-axis reflection: ('.(-$x).', '.$y.'). After x-axis reflection: ('.$answerPoint[0].', '.$answerPoint[1].').';
        }

        $answer = '('.$answerPoint[0].', '.$answerPoint[1].')';
        $distractorPool = [
            '('.$answerPoint[0].', '.(-$answerPoint[1]).')',
            '('.(-$answerPoint[0]).', '.$answerPoint[1].')',
            '('.(-$answerPoint[0]).', '.(-$answerPoint[1]).')',
            '('.($answerPoint[0] + 1).', '.$answerPoint[1].')',
            '('.$answerPoint[0].', '.($answerPoint[1] + 1).')',
        ];

        return $this->composeQuestion('mirror_image', $difficulty, $questionText, $answer, $this->pickDistractors($answer, $distractorPool, 3, $index + 151), $explanation);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSpatialFoldingQuestion(string $difficulty, int $index): array
    {
        if ($difficulty === 'easy') {
            $templates = [
                ['desc' => 'once vertically', 'layers' => 2],
                ['desc' => 'once horizontally', 'layers' => 2],
                ['desc' => 'once diagonally', 'layers' => 2],
                ['desc' => 'twice in the same direction', 'layers' => 4],
                ['desc' => 'once vertically then once horizontally', 'layers' => 4],
                ['desc' => 'once diagonally then once horizontally', 'layers' => 4],
            ];
            $template = $templates[$index % count($templates)];
            $punches = 1 + (int) (($index / count($templates)) % 2);
            $answer = $template['layers'] * $punches;
            $questionText = 'A square paper is folded '.$template['desc'].'. '.$punches.' hole(s) are punched through all folded layers. How many holes appear after unfolding?';
            $explanation = 'Folding creates '.$template['layers'].' layers. '.$punches.' punch(es) make '.$answer.' holes after unfolding.';
        } elseif ($difficulty === 'medium') {
            $templates = [
                ['desc' => 'three times in a row', 'layers' => 8],
                ['desc' => 'twice and then rotated before a third fold', 'layers' => 8],
                ['desc' => 'once vertical, once horizontal, once diagonal', 'layers' => 8],
                ['desc' => 'once diagonal, once vertical, once horizontal', 'layers' => 8],
                ['desc' => 'four times with equal halves each time', 'layers' => 16],
                ['desc' => 'three times, then the folded stack is halved once more', 'layers' => 16],
            ];
            $template = $templates[$index % count($templates)];
            $punches = 1 + (int) (($index / 3) % 3);
            $answer = $template['layers'] * $punches;
            $questionText = 'A paper is folded '.$template['desc'].'. '.$punches.' punch mark(s) are made on the folded packet. How many holes are visible after complete unfolding?';
            $explanation = 'The fold pattern creates '.$template['layers'].' layers, so total holes are '.$template['layers'].' * '.$punches.'.';
        } else {
            $templates = [
                ['desc' => 'four times', 'layers' => 16],
                ['desc' => 'five times', 'layers' => 32],
                ['desc' => 'four times with one diagonal fold included', 'layers' => 16],
                ['desc' => 'five times with mixed fold directions', 'layers' => 32],
                ['desc' => 'six times', 'layers' => 64],
                ['desc' => 'six times with alternating diagonal and straight folds', 'layers' => 64],
            ];
            $template = $templates[$index % count($templates)];
            $punches = 2 + (int) (($index / 2) % 3);
            $answer = $template['layers'] * $punches;
            $questionText = 'A square sheet is folded '.$template['desc'].'. '.$punches.' punches are made in different places on the folded sheet. How many holes appear after full unfolding?';
            $explanation = 'The folded stack has '.$template['layers'].' layers. Multiply by '.$punches.' punches to get '.$answer.' holes.';
        }

        $distractors = $this->numericDistractors($answer, $difficulty, $index + 173);

        return $this->composeQuestion('folding', $difficulty, $questionText, (string) $answer, $distractors, $explanation);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSpatialCubeLogicQuestion(string $difficulty, int $index): array
    {
        $labels = $this->cubeLabels($index);
        $cube = [
            'top' => $labels[0],
            'bottom' => $labels[3],
            'front' => $labels[1],
            'back' => $labels[4],
            'right' => $labels[2],
            'left' => $labels[5],
        ];

        $operations = $difficulty === 'easy'
            ? ['roll_right']
            : ($difficulty === 'medium'
                ? ['roll_forward', 'roll_left']
                : ['roll_right', 'roll_forward', 'roll_left']);

        if ($difficulty !== 'easy' && $index % 2 === 0) {
            $operations[] = 'roll_backward';
        }

        $final = $cube;
        foreach ($operations as $operation) {
            $final = $this->applyCubeOperation($final, $operation);
        }

        $askFace = $difficulty === 'hard' ? 'front' : 'top';
        $answer = $final[$askFace];

        $operationText = implode(', ', array_map(function (string $operation): string {
            return match ($operation) {
                'roll_right' => 'rolls to the right',
                'roll_left' => 'rolls to the left',
                'roll_forward' => 'rolls forward',
                'roll_backward' => 'rolls backward',
                default => $operation,
            };
        }, $operations));

        $questionText = 'A cube has Top='.$cube['top'].', Front='.$cube['front'].', Right='.$cube['right']
            .' (so opposite faces are Bottom='.$cube['bottom'].', Back='.$cube['back'].', Left='.$cube['left'].'). '
            .'Then the cube '.$operationText.'. Which label is on the '.$askFace.' face now?';
        $explanation = 'Track how each roll moves faces around the cube.';

        $distractors = $this->pickDistractors($answer, array_values($labels), 3, $index + 197);

        return $this->composeQuestion('cube_logic', $difficulty, $questionText, $answer, $distractors, $explanation);
    }

    /**
     * @param array<string, string> $cube
     * @return array<string, string>
     */
    private function applyCubeOperation(array $cube, string $operation): array
    {
        return match ($operation) {
            'roll_right' => [
                'top' => $cube['left'],
                'bottom' => $cube['right'],
                'front' => $cube['front'],
                'back' => $cube['back'],
                'right' => $cube['top'],
                'left' => $cube['bottom'],
            ],
            'roll_left' => [
                'top' => $cube['right'],
                'bottom' => $cube['left'],
                'front' => $cube['front'],
                'back' => $cube['back'],
                'right' => $cube['bottom'],
                'left' => $cube['top'],
            ],
            'roll_forward' => [
                'top' => $cube['back'],
                'bottom' => $cube['front'],
                'front' => $cube['top'],
                'back' => $cube['bottom'],
                'right' => $cube['right'],
                'left' => $cube['left'],
            ],
            'roll_backward' => [
                'top' => $cube['front'],
                'bottom' => $cube['back'],
                'front' => $cube['bottom'],
                'back' => $cube['top'],
                'right' => $cube['right'],
                'left' => $cube['left'],
            ],
            default => $cube,
        };
    }

    /**
     * @param array<int, string> $options
     */
    private function hasSingleCorrectOption(array $options): bool
    {
        $correct = 0;
        foreach ($options as $option) {
            if ((bool) ($option['is_correct'] ?? false) === true) {
                $correct++;
            }
        }

        return count($options) === 4 && $correct === 1;
    }

    private function resolveDifficulty(string $difficulty): string
    {
        $resolved = strtolower(trim($difficulty));
        if (! in_array($resolved, ['easy', 'medium', 'hard'], true)) {
            throw new RuntimeException('Unsupported difficulty: '.$difficulty);
        }

        return $resolved;
    }

    /**
     * @param array<int, string> $distractors
     * @return array<string, mixed>
     */
    private function composeQuestion(
        string $questionType,
        string $difficulty,
        string $questionText,
        string $correctOption,
        array $distractors,
        string $explanation,
        ?string $questionImage = null
    ): array {
        $cleanCorrect = trim($correctOption);
        $cleanDistractors = array_values(array_filter(
            array_map(static fn (string $value): string => trim($value), $distractors),
            static fn (string $value): bool => $value !== '' && $value !== $cleanCorrect
        ));

        while (count($cleanDistractors) < 3) {
            $cleanDistractors[] = 'Option '.(count($cleanDistractors) + 1);
        }

        $finalDistractors = array_slice(array_values(array_unique($cleanDistractors)), 0, 3);
        if (count($finalDistractors) < 3) {
            $fallback = ['Choice A', 'Choice B', 'Choice C', 'Choice D'];
            foreach ($fallback as $item) {
                if ($item !== $cleanCorrect && ! in_array($item, $finalDistractors, true)) {
                    $finalDistractors[] = $item;
                    if (count($finalDistractors) === 3) {
                        break;
                    }
                }
            }
        }

        $options = array_merge(
            [['option_text' => $cleanCorrect, 'is_correct' => true]],
            array_map(static fn (string $value): array => ['option_text' => $value, 'is_correct' => false], $finalDistractors)
        );

        $seed = crc32($questionType.'|'.$difficulty.'|'.$questionText);
        if ($seed === false) {
            $seed = 0;
        }
        $this->deterministicShuffle($options, (int) $seed);

        foreach ($options as $index => &$option) {
            $option['sort_order'] = $index + 1;
            $option['option_image'] = null;
        }
        unset($option);

        return [
            'question_type' => $questionType,
            'difficulty' => $difficulty,
            'question_text' => trim($questionText),
            'question_image' => $questionImage,
            'marks' => 1,
            'explanation' => trim($explanation),
            'options' => $options,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function deterministicShuffle(array &$items, int $seed): void
    {
        if (count($items) <= 1) {
            return;
        }

        $localSeed = $seed;
        for ($i = count($items) - 1; $i > 0; $i--) {
            $localSeed = (1103515245 * $localSeed + 12345) & 0x7fffffff;
            $j = $localSeed % ($i + 1);
            $temp = $items[$i];
            $items[$i] = $items[$j];
            $items[$j] = $temp;
        }
    }

    /**
     * @param array<int, string> $pool
     * @return array<int, string>
     */
    private function pickDistractors(string $correct, array $pool, int $count, int $offset = 0): array
    {
        $uniquePool = [];
        foreach ($pool as $item) {
            $value = trim((string) $item);
            if ($value === '' || $value === $correct || in_array($value, $uniquePool, true)) {
                continue;
            }
            $uniquePool[] = $value;
        }

        if ($uniquePool === []) {
            return ['Choice 1', 'Choice 2', 'Choice 3'];
        }

        $picked = [];
        for ($i = 0; $i < count($uniquePool) && count($picked) < $count; $i++) {
            $candidate = $uniquePool[($i + $offset) % count($uniquePool)];
            if (! in_array($candidate, $picked, true)) {
                $picked[] = $candidate;
            }
        }

        $fallback = ['Choice A', 'Choice B', 'Choice C', 'Choice D', 'Choice E'];
        foreach ($fallback as $item) {
            if (count($picked) >= $count) {
                break;
            }
            if ($item !== $correct && ! in_array($item, $picked, true)) {
                $picked[] = $item;
            }
        }

        return array_slice($picked, 0, $count);
    }

    /**
     * @return array<int, string>
     */
    private function numericDistractors(int $answer, string $difficulty, int $index): array
    {
        $spread = match ($difficulty) {
            'easy' => [1, 2, 3, 4, 5],
            'medium' => [2, 4, 6, 8, 10],
            'hard' => [3, 6, 9, 12, 15],
            default => [1, 2, 3, 4, 5],
        };

        $picked = [];
        foreach ($spread as $offset) {
            $variant = $index % 2 === 0 ? $answer + $offset : $answer - $offset;
            if ($variant <= 0) {
                $variant = $answer + $offset + 1;
            }
            if ($variant === $answer) {
                $variant++;
            }
            $picked[] = (string) $variant;
            if (count($picked) === 3) {
                break;
            }
        }

        return array_slice(array_values(array_unique($picked)), 0, 3);
    }

    /**
     * @return array<int, string>
     */
    private function ratioDistractors(string $answer, int $index): array
    {
        [$a, $b] = array_map('intval', explode(':', $answer));
        $pool = [
            ($a + 1).':'.$b,
            $a.':'.($b + 1),
            ($a + 1).':'.($b + 1),
            ($b).':'.$a,
            ($a + 2).':'.max(1, $b - 1),
        ];

        return $this->pickDistractors($answer, $pool, 3, $index);
    }

    private function greatestCommonDivisor(int $a, int $b): int
    {
        $a = abs($a);
        $b = abs($b);

        while ($b !== 0) {
            $temp = $b;
            $b = $a % $b;
            $a = $temp;
        }

        return max($a, 1);
    }

    /**
     * @return array<int, string>
     */
    private function cubeLabels(int $index): array
    {
        $alphabet = range('A', 'Z');
        $start = $index % (count($alphabet) - 6);

        return [
            $alphabet[$start],
            $alphabet[$start + 1],
            $alphabet[$start + 2],
            $alphabet[$start + 3],
            $alphabet[$start + 4],
            $alphabet[$start + 5],
        ];
    }

    private function rememberQuestionSignature(string $sectionCode, string $questionText): bool
    {
        $signature = $this->questionSignature($questionText);
        if ($signature === '' || ! array_key_exists($sectionCode, $this->usedSignatures)) {
            return false;
        }

        if (isset($this->usedSignatures[$sectionCode][$signature])) {
            return false;
        }

        $this->usedSignatures[$sectionCode][$signature] = true;

        return true;
    }

    private function questionSignature(string $questionText): string
    {
        $normalized = preg_replace('/\s+/', ' ', strtolower(trim($questionText)));

        return is_string($normalized) ? $normalized : '';
    }

    /**
     * @return array<int, array{label:string,pairs:array<int,array{0:string,1:string}>}>
     */
    private function verbalAnalogyRelations(string $difficulty): array
    {
        if ($difficulty === 'easy') {
            return [
                ['label' => 'young one', 'pairs' => [['Dog', 'Puppy'], ['Cat', 'Kitten'], ['Cow', 'Calf'], ['Goat', 'Kid'], ['Horse', 'Foal'], ['Lion', 'Cub'], ['Duck', 'Duckling'], ['Hen', 'Chick']]],
                ['label' => 'home', 'pairs' => [['Bee', 'Hive'], ['Bird', 'Nest'], ['Spider', 'Web'], ['Rabbit', 'Burrow'], ['Fox', 'Den'], ['Ant', 'Colony']]],
                ['label' => 'person and workplace', 'pairs' => [['Teacher', 'School'], ['Doctor', 'Clinic'], ['Chef', 'Kitchen'], ['Pilot', 'Cockpit'], ['Farmer', 'Field'], ['Librarian', 'Library']]],
            ];
        }

        if ($difficulty === 'medium') {
            return [
                ['label' => 'tool and use', 'pairs' => [['Thermometer', 'Measure'], ['Compass', 'Navigate'], ['Microscope', 'Magnify'], ['Stethoscope', 'Listen'], ['Calculator', 'Compute'], ['Scanner', 'Digitize']]],
                ['label' => 'cause and effect', 'pairs' => [['Drought', 'Scarcity'], ['Practice', 'Improvement'], ['Neglect', 'Decay'], ['Exercise', 'Fitness'], ['Pollution', 'Smog'], ['Friction', 'Heat']]],
                ['label' => 'process and result', 'pairs' => [['Melt', 'Liquid'], ['Freeze', 'Solid'], ['Evaporate', 'Vapor'], ['Condense', 'Droplets'], ['Ferment', 'Acid'], ['Refine', 'Purity']]],
            ];
        }

        return [
            ['label' => 'abstract relation', 'pairs' => [['Evidence', 'Conclusion'], ['Hypothesis', 'Experiment'], ['Premise', 'Inference'], ['Signal', 'Interpretation'], ['Context', 'Meaning'], ['Pattern', 'Prediction']]],
            ['label' => 'governance relation', 'pairs' => [['Constitution', 'Law'], ['Budget', 'Policy'], ['Treaty', 'Cooperation'], ['Audit', 'Accountability'], ['Mandate', 'Authority'], ['Debate', 'Decision']]],
            ['label' => 'design relation', 'pairs' => [['Blueprint', 'Structure'], ['Algorithm', 'Output'], ['Model', 'Simulation'], ['Prototype', 'Testing'], ['Constraint', 'Optimization'], ['Feedback', 'Revision']]],
        ];
    }

    /**
     * @return array<int, array{word:string,answer:string}>
     */
    private function verbalSynonymBank(string $difficulty): array
    {
        if ($difficulty === 'easy') {
            return [
                ['word' => 'Rapid', 'answer' => 'Quick'],
                ['word' => 'Silent', 'answer' => 'Quiet'],
                ['word' => 'Large', 'answer' => 'Big'],
                ['word' => 'Tiny', 'answer' => 'Small'],
                ['word' => 'Begin', 'answer' => 'Start'],
                ['word' => 'Assist', 'answer' => 'Help'],
                ['word' => 'Smart', 'answer' => 'Clever'],
                ['word' => 'Bright', 'answer' => 'Shiny'],
                ['word' => 'Brave', 'answer' => 'Bold'],
                ['word' => 'Calm', 'answer' => 'Peaceful'],
                ['word' => 'Neat', 'answer' => 'Tidy'],
                ['word' => 'Simple', 'answer' => 'Easy'],
                ['word' => 'Goal', 'answer' => 'Aim'],
                ['word' => 'Error', 'answer' => 'Mistake'],
                ['word' => 'Observe', 'answer' => 'Watch'],
                ['word' => 'Collect', 'answer' => 'Gather'],
                ['word' => 'Finish', 'answer' => 'Complete'],
                ['word' => 'Repair', 'answer' => 'Fix'],
                ['word' => 'Exit', 'answer' => 'Leave'],
                ['word' => 'Gain', 'answer' => 'Acquire'],
            ];
        }

        if ($difficulty === 'medium') {
            return [
                ['word' => 'Accurate', 'answer' => 'Precise'],
                ['word' => 'Adapt', 'answer' => 'Adjust'],
                ['word' => 'Complex', 'answer' => 'Complicated'],
                ['word' => 'Expand', 'answer' => 'Broaden'],
                ['word' => 'Restrict', 'answer' => 'Limit'],
                ['word' => 'Essential', 'answer' => 'Vital'],
                ['word' => 'Frequent', 'answer' => 'Regular'],
                ['word' => 'Generate', 'answer' => 'Produce'],
                ['word' => 'Hesitate', 'answer' => 'Pause'],
                ['word' => 'Inspect', 'answer' => 'Examine'],
                ['word' => 'Maintain', 'answer' => 'Preserve'],
                ['word' => 'Predict', 'answer' => 'Forecast'],
                ['word' => 'Reject', 'answer' => 'Decline'],
                ['word' => 'Sustain', 'answer' => 'Support'],
                ['word' => 'Vary', 'answer' => 'Differ'],
                ['word' => 'Isolate', 'answer' => 'Separate'],
                ['word' => 'Merge', 'answer' => 'Combine'],
                ['word' => 'Clarify', 'answer' => 'Explain'],
                ['word' => 'Enhance', 'answer' => 'Improve'],
                ['word' => 'Interpret', 'answer' => 'Explain'],
            ];
        }

        return [
            ['word' => 'Ambiguous', 'answer' => 'Unclear'],
            ['word' => 'Coherent', 'answer' => 'Logical'],
            ['word' => 'Constrain', 'answer' => 'Restrict'],
            ['word' => 'Derive', 'answer' => 'Obtain'],
            ['word' => 'Evaluate', 'answer' => 'Assess'],
            ['word' => 'Infer', 'answer' => 'Conclude'],
            ['word' => 'Intricate', 'answer' => 'Detailed'],
            ['word' => 'Mitigate', 'answer' => 'Reduce'],
            ['word' => 'Optimize', 'answer' => 'Improve'],
            ['word' => 'Rational', 'answer' => 'Reasoned'],
            ['word' => 'Robust', 'answer' => 'Strong'],
            ['word' => 'Subtle', 'answer' => 'Slight'],
            ['word' => 'Validate', 'answer' => 'Confirm'],
            ['word' => 'Viable', 'answer' => 'Practical'],
            ['word' => 'Consistent', 'answer' => 'Stable'],
            ['word' => 'Abstract', 'answer' => 'Conceptual'],
            ['word' => 'Allocate', 'answer' => 'Assign'],
            ['word' => 'Correlate', 'answer' => 'Relate'],
            ['word' => 'Differentiate', 'answer' => 'Distinguish'],
            ['word' => 'Synthesize', 'answer' => 'Integrate'],
        ];
    }

    /**
     * @return array<int, array{word:string,answer:string}>
     */
    private function verbalAntonymBank(string $difficulty): array
    {
        if ($difficulty === 'easy') {
            return [
                ['word' => 'Hot', 'answer' => 'Cold'],
                ['word' => 'Early', 'answer' => 'Late'],
                ['word' => 'Open', 'answer' => 'Closed'],
                ['word' => 'Heavy', 'answer' => 'Light'],
                ['word' => 'Inside', 'answer' => 'Outside'],
                ['word' => 'Above', 'answer' => 'Below'],
                ['word' => 'Accept', 'answer' => 'Reject'],
                ['word' => 'Empty', 'answer' => 'Full'],
                ['word' => 'Front', 'answer' => 'Back'],
                ['word' => 'Sharp', 'answer' => 'Blunt'],
                ['word' => 'Fast', 'answer' => 'Slow'],
                ['word' => 'Noisy', 'answer' => 'Quiet'],
                ['word' => 'Strong', 'answer' => 'Weak'],
                ['word' => 'Clean', 'answer' => 'Dirty'],
                ['word' => 'Safe', 'answer' => 'Risky'],
                ['word' => 'Major', 'answer' => 'Minor'],
                ['word' => 'Always', 'answer' => 'Never'],
                ['word' => 'Buy', 'answer' => 'Sell'],
                ['word' => 'Win', 'answer' => 'Lose'],
                ['word' => 'Arrive', 'answer' => 'Depart'],
            ];
        }

        if ($difficulty === 'medium') {
            return [
                ['word' => 'Expand', 'answer' => 'Contract'],
                ['word' => 'Frequent', 'answer' => 'Rare'],
                ['word' => 'Increase', 'answer' => 'Decrease'],
                ['word' => 'Permanent', 'answer' => 'Temporary'],
                ['word' => 'Visible', 'answer' => 'Hidden'],
                ['word' => 'Flexible', 'answer' => 'Rigid'],
                ['word' => 'Maximum', 'answer' => 'Minimum'],
                ['word' => 'Complex', 'answer' => 'Simple'],
                ['word' => 'Generous', 'answer' => 'Stingy'],
                ['word' => 'Modern', 'answer' => 'Ancient'],
                ['word' => 'Support', 'answer' => 'Oppose'],
                ['word' => 'Precise', 'answer' => 'Vague'],
                ['word' => 'Legal', 'answer' => 'Illegal'],
                ['word' => 'Combine', 'answer' => 'Separate'],
                ['word' => 'Stable', 'answer' => 'Unstable'],
                ['word' => 'Logical', 'answer' => 'Illogical'],
                ['word' => 'Secure', 'answer' => 'Exposed'],
                ['word' => 'Active', 'answer' => 'Passive'],
                ['word' => 'Favorable', 'answer' => 'Adverse'],
                ['word' => 'Dense', 'answer' => 'Sparse'],
            ];
        }

        return [
            ['word' => 'Accurate', 'answer' => 'Inaccurate'],
            ['word' => 'Coherent', 'answer' => 'Confused'],
            ['word' => 'Consistent', 'answer' => 'Erratic'],
            ['word' => 'Concrete', 'answer' => 'Abstract'],
            ['word' => 'Converge', 'answer' => 'Diverge'],
            ['word' => 'Deficit', 'answer' => 'Surplus'],
            ['word' => 'Explicit', 'answer' => 'Implicit'],
            ['word' => 'Inferior', 'answer' => 'Superior'],
            ['word' => 'Mitigate', 'answer' => 'Worsen'],
            ['word' => 'Neutral', 'answer' => 'Biased'],
            ['word' => 'Objective', 'answer' => 'Subjective'],
            ['word' => 'Optimistic', 'answer' => 'Pessimistic'],
            ['word' => 'Robust', 'answer' => 'Fragile'],
            ['word' => 'Sufficient', 'answer' => 'Insufficient'],
            ['word' => 'Transparent', 'answer' => 'Opaque'],
            ['word' => 'Validate', 'answer' => 'Refute'],
            ['word' => 'Viable', 'answer' => 'Impractical'],
            ['word' => 'Rational', 'answer' => 'Irrational'],
            ['word' => 'Stable', 'answer' => 'Volatile'],
            ['word' => 'Conserve', 'answer' => 'Waste'],
        ];
    }

    /**
     * @return array<int, array{label:string,words:array<int,string>}>
     */
    private function verbalOddOneOutCategories(string $difficulty): array
    {
        if ($difficulty === 'easy') {
            return [
                ['label' => 'fruits', 'words' => ['Apple', 'Banana', 'Orange', 'Mango', 'Pear']],
                ['label' => 'vehicles', 'words' => ['Car', 'Bus', 'Truck', 'Van', 'Taxi']],
                ['label' => 'school items', 'words' => ['Notebook', 'Pencil', 'Eraser', 'Ruler', 'Marker']],
                ['label' => 'birds', 'words' => ['Sparrow', 'Pigeon', 'Eagle', 'Parrot', 'Crow']],
                ['label' => 'body parts', 'words' => ['Hand', 'Leg', 'Arm', 'Foot', 'Finger']],
            ];
        }

        if ($difficulty === 'medium') {
            return [
                ['label' => 'energy sources', 'words' => ['Solar', 'Wind', 'Hydro', 'Geothermal', 'Biogas']],
                ['label' => 'writing actions', 'words' => ['Draft', 'Edit', 'Proofread', 'Revise', 'Annotate']],
                ['label' => 'data terms', 'words' => ['Average', 'Median', 'Mode', 'Range', 'Variance']],
                ['label' => 'map terms', 'words' => ['Scale', 'Legend', 'Compass', 'Latitude', 'Longitude']],
                ['label' => 'science methods', 'words' => ['Observe', 'Measure', 'Classify', 'Hypothesize', 'Test']],
            ];
        }

        return [
            ['label' => 'argument terms', 'words' => ['Premise', 'Inference', 'Conclusion', 'Counterclaim', 'Evidence']],
            ['label' => 'research terms', 'words' => ['Variable', 'Control', 'Sample', 'Bias', 'Replication']],
            ['label' => 'systems terms', 'words' => ['Input', 'Process', 'Output', 'Feedback', 'Constraint']],
            ['label' => 'economics terms', 'words' => ['Demand', 'Supply', 'Inflation', 'Deficit', 'Tariff']],
            ['label' => 'programming concepts', 'words' => ['Loop', 'Condition', 'Function', 'Array', 'Recursion']],
        ];
    }

    /**
     * @return array<int, array{subject:string,verb:string,object:string}>
     */
    private function verbalActionBank(string $difficulty): array
    {
        if ($difficulty === 'easy') {
            return [
                ['subject' => 'A pilot', 'verb' => 'flies', 'object' => 'an aircraft'],
                ['subject' => 'A chef', 'verb' => 'cooks', 'object' => 'a meal'],
                ['subject' => 'A tailor', 'verb' => 'stitches', 'object' => 'cloth'],
                ['subject' => 'A farmer', 'verb' => 'grows', 'object' => 'crops'],
                ['subject' => 'A painter', 'verb' => 'paints', 'object' => 'a wall'],
                ['subject' => 'A driver', 'verb' => 'steers', 'object' => 'a vehicle'],
                ['subject' => 'A teacher', 'verb' => 'guides', 'object' => 'students'],
                ['subject' => 'A librarian', 'verb' => 'organizes', 'object' => 'books'],
                ['subject' => 'A mechanic', 'verb' => 'repairs', 'object' => 'machines'],
                ['subject' => 'A nurse', 'verb' => 'cares for', 'object' => 'patients'],
                ['subject' => 'A coach', 'verb' => 'trains', 'object' => 'players'],
                ['subject' => 'A writer', 'verb' => 'drafts', 'object' => 'stories'],
                ['subject' => 'A baker', 'verb' => 'bakes', 'object' => 'bread'],
                ['subject' => 'A gardener', 'verb' => 'waters', 'object' => 'plants'],
                ['subject' => 'A musician', 'verb' => 'plays', 'object' => 'an instrument'],
                ['subject' => 'A guard', 'verb' => 'protects', 'object' => 'the gate'],
                ['subject' => 'A doctor', 'verb' => 'treats', 'object' => 'patients'],
                ['subject' => 'A student', 'verb' => 'solves', 'object' => 'problems'],
                ['subject' => 'A carpenter', 'verb' => 'cuts', 'object' => 'wood'],
                ['subject' => 'A scientist', 'verb' => 'tests', 'object' => 'ideas'],
            ];
        }

        if ($difficulty === 'medium') {
            return [
                ['subject' => 'An analyst', 'verb' => 'interprets', 'object' => 'data'],
                ['subject' => 'An architect', 'verb' => 'designs', 'object' => 'structures'],
                ['subject' => 'A researcher', 'verb' => 'evaluates', 'object' => 'evidence'],
                ['subject' => 'A manager', 'verb' => 'allocates', 'object' => 'resources'],
                ['subject' => 'A mediator', 'verb' => 'resolves', 'object' => 'conflicts'],
                ['subject' => 'A planner', 'verb' => 'schedules', 'object' => 'tasks'],
                ['subject' => 'A reviewer', 'verb' => 'assesses', 'object' => 'quality'],
                ['subject' => 'A counselor', 'verb' => 'advises', 'object' => 'families'],
                ['subject' => 'An engineer', 'verb' => 'optimizes', 'object' => 'systems'],
                ['subject' => 'A coordinator', 'verb' => 'synchronizes', 'object' => 'teams'],
                ['subject' => 'An auditor', 'verb' => 'verifies', 'object' => 'records'],
                ['subject' => 'A programmer', 'verb' => 'debugs', 'object' => 'code'],
                ['subject' => 'A statistician', 'verb' => 'models', 'object' => 'trends'],
                ['subject' => 'A strategist', 'verb' => 'prioritizes', 'object' => 'goals'],
                ['subject' => 'An editor', 'verb' => 'refines', 'object' => 'drafts'],
                ['subject' => 'A technician', 'verb' => 'calibrates', 'object' => 'equipment'],
                ['subject' => 'A supervisor', 'verb' => 'monitors', 'object' => 'progress'],
                ['subject' => 'A consultant', 'verb' => 'recommends', 'object' => 'solutions'],
                ['subject' => 'An investigator', 'verb' => 'examines', 'object' => 'reports'],
                ['subject' => 'A negotiator', 'verb' => 'balances', 'object' => 'interests'],
            ];
        }

        return [
            ['subject' => 'A policy team', 'verb' => 'formulates', 'object' => 'frameworks'],
            ['subject' => 'A scientist', 'verb' => 'validates', 'object' => 'hypotheses'],
            ['subject' => 'An economist', 'verb' => 'forecasts', 'object' => 'demand'],
            ['subject' => 'A data team', 'verb' => 'correlates', 'object' => 'variables'],
            ['subject' => 'A system architect', 'verb' => 'integrates', 'object' => 'modules'],
            ['subject' => 'A legal team', 'verb' => 'interprets', 'object' => 'statutes'],
            ['subject' => 'An ethicist', 'verb' => 'weighs', 'object' => 'trade-offs'],
            ['subject' => 'A regulator', 'verb' => 'enforces', 'object' => 'standards'],
            ['subject' => 'A project board', 'verb' => 'authorizes', 'object' => 'milestones'],
            ['subject' => 'A quality lead', 'verb' => 'benchmarks', 'object' => 'performance'],
            ['subject' => 'A designer', 'verb' => 'prototypes', 'object' => 'concepts'],
            ['subject' => 'A researcher', 'verb' => 'synthesizes', 'object' => 'findings'],
            ['subject' => 'An operations team', 'verb' => 'streamlines', 'object' => 'workflows'],
            ['subject' => 'A review panel', 'verb' => 'scrutinizes', 'object' => 'proposals'],
            ['subject' => 'A planner', 'verb' => 'mitigates', 'object' => 'risks'],
            ['subject' => 'An analyst', 'verb' => 'triangulates', 'object' => 'evidence'],
            ['subject' => 'A board', 'verb' => 'ratifies', 'object' => 'policies'],
            ['subject' => 'A taskforce', 'verb' => 'coordinates', 'object' => 'responses'],
            ['subject' => 'A scientist', 'verb' => 'replicates', 'object' => 'results'],
            ['subject' => 'A strategist', 'verb' => 'reconciles', 'object' => 'constraints'],
        ];
    }
}
