<?php

namespace Database\Seeders;

use App\Models\CognitiveAssessment;
use App\Models\CognitiveAssessmentQuestion;
use App\Models\CognitiveAssessmentSection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CognitiveSkillsAssessmentLevel4Seeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $assessment = CognitiveAssessment::query()->updateOrCreate(
                ['slug' => CognitiveAssessment::LEVEL_4_SLUG],
                [
                    'title' => CognitiveAssessment::LEVEL_4_TITLE,
                    'description' => 'Internal cognitive assessment for Grades 8 to 12',
                    'is_active' => true,
                ]
            );

            $sections = collect($this->sectionPayloads())
                ->mapWithKeys(function (array $sectionPayload) use ($assessment): array {
                    $section = CognitiveAssessmentSection::query()->updateOrCreate(
                        [
                            'assessment_id' => $assessment->id,
                            'skill' => $sectionPayload['skill'],
                        ],
                        [
                            'title' => $sectionPayload['title'],
                            'duration_seconds' => $sectionPayload['duration_seconds'],
                            'sort_order' => $sectionPayload['sort_order'],
                        ]
                    );

                    return [$sectionPayload['skill'] => $section];
                });

            $hasDifficultyLevel = Schema::hasColumn('cognitive_assessment_questions', 'difficulty_level');
            $hasExplanation = Schema::hasColumn('cognitive_assessment_questions', 'explanation');

            foreach (self::questionPayloads() as $skill => $questions) {
                /** @var CognitiveAssessmentSection $section */
                $section = $sections[$skill];
                $totalMarks = 0;

                foreach ($questions as $index => $questionPayload) {
                    $marks = (int) ($questionPayload['marks'] ?? 1);
                    $values = [
                        'question_image' => $questionPayload['question_image'] ?? null,
                        'options' => $questionPayload['options'],
                        'correct_answer' => $questionPayload['correct_answer'],
                        'marks' => $marks,
                        'sort_order' => $index + 1,
                    ];

                    if ($hasDifficultyLevel) {
                        $values['difficulty_level'] = $questionPayload['difficulty_level'] ?? null;
                    }

                    if ($hasExplanation) {
                        $values['explanation'] = $questionPayload['explanation'] ?? null;
                    }

                    CognitiveAssessmentQuestion::query()->updateOrCreate(
                        [
                            'section_id' => $section->id,
                            'question_type' => $questionPayload['question_type'],
                            'question_text' => $questionPayload['question_text'],
                        ],
                        $values
                    );

                    $totalMarks += $marks;
                }

                $section->forceFill(['total_marks' => $totalMarks])->save();
            }
        });
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function questionPayloads(): array
    {
        return [
            CognitiveAssessmentSection::SKILL_VERBAL => [
                [
                    'question_type' => 'analogy',
                    'difficulty_level' => 'easy',
                    'question_text' => 'Book is to reading as fork is to ____.',
                    'question_image' => null,
                    'options' => ['writing', 'drawing', 'eating', 'walking'],
                    'correct_answer' => 'eating',
                    'marks' => 1,
                    'explanation' => 'A book is used for reading and a fork is used for eating.',
                ],
                [
                    'question_type' => 'classification',
                    'difficulty_level' => 'easy',
                    'question_text' => 'Choose the word that does not belong.',
                    'question_image' => null,
                    'options' => ['Rose', 'Lily', 'Tulip', 'Table'],
                    'correct_answer' => 'Table',
                    'marks' => 1,
                    'explanation' => 'Three choices are flowers while table is furniture.',
                ],
                [
                    'question_type' => 'mcq',
                    'difficulty_level' => 'medium',
                    'question_text' => 'If all poets are writers and some writers are teachers, which statement must be true?',
                    'question_image' => null,
                    'options' => [
                        'All writers are poets',
                        'Some teachers are poets',
                        'All poets are writers',
                        'No teacher is a writer',
                    ],
                    'correct_answer' => 'All poets are writers',
                    'marks' => 1,
                    'explanation' => 'The first statement directly tells us that every poet is a writer.',
                ],
                [
                    'question_type' => 'analogy',
                    'difficulty_level' => 'easy',
                    'question_text' => 'Thermometer is to temperature as compass is to ____.',
                    'question_image' => null,
                    'options' => ['speed', 'direction', 'rain', 'weight'],
                    'correct_answer' => 'direction',
                    'marks' => 1,
                    'explanation' => 'A thermometer measures temperature and a compass indicates direction.',
                ],
                [
                    'question_type' => 'odd_one_out',
                    'difficulty_level' => 'easy',
                    'question_text' => 'Choose the odd one out.',
                    'question_image' => null,
                    'options' => ['Honest', 'Loyal', 'Brave', 'Dishonest'],
                    'correct_answer' => 'Dishonest',
                    'marks' => 1,
                    'explanation' => 'Three words describe positive traits while dishonest is the opposite.',
                ],
                [
                    'question_type' => 'sequence',
                    'difficulty_level' => 'medium',
                    'question_text' => 'If CLOUD becomes DMPVE by moving each letter one step forward, how does TRAIN change?',
                    'question_image' => null,
                    'options' => ['USBHO', 'USBJO', 'VTCKP', 'URAIN'],
                    'correct_answer' => 'USBJO',
                    'marks' => 1,
                    'explanation' => 'Each letter moves forward by one: T-U, R-S, A-B, I-J, N-O.',
                ],
                [
                    'question_type' => 'analogy',
                    'difficulty_level' => 'easy',
                    'question_text' => 'Seed is to plant as egg is to ____.',
                    'question_image' => null,
                    'options' => ['bird', 'nest', 'feather', 'tree'],
                    'correct_answer' => 'bird',
                    'marks' => 1,
                    'explanation' => 'A seed grows into a plant and an egg develops into a bird.',
                ],
                [
                    'question_type' => 'mcq',
                    'difficulty_level' => 'easy',
                    'question_text' => 'Which word is closest in meaning to brief?',
                    'question_image' => null,
                    'options' => ['short', 'heavy', 'ancient', 'loud'],
                    'correct_answer' => 'short',
                    'marks' => 1,
                    'explanation' => 'Brief means short in length or duration.',
                ],
                [
                    'question_type' => 'classification',
                    'difficulty_level' => 'easy',
                    'question_text' => 'Choose the option that is not a type of transport.',
                    'question_image' => null,
                    'options' => ['Bicycle', 'Bus', 'Train', 'Pillow'],
                    'correct_answer' => 'Pillow',
                    'marks' => 1,
                    'explanation' => 'The first three are transport methods while a pillow is not.',
                ],
                [
                    'question_type' => 'mcq',
                    'difficulty_level' => 'medium',
                    'question_text' => 'If all tulips are flowers and all flowers need water, which statement must be true?',
                    'question_image' => null,
                    'options' => [
                        'All flowers are tulips',
                        'Some tulips do not need water',
                        'All tulips need water',
                        'Only tulips need water',
                    ],
                    'correct_answer' => 'All tulips need water',
                    'marks' => 1,
                    'explanation' => 'Tulips belong to the group of flowers, and all flowers need water.',
                ],
            ],
            CognitiveAssessmentSection::SKILL_NON_VERBAL => [
                [
                    'question_type' => 'matrix',
                    'difficulty_level' => 'medium',
                    'question_text' => 'Review the matrix image and choose the option that best completes the missing tile.',
                    'question_image' => 'cognitive-questions/non-verbal-matrix-sample.svg',
                    'options' => ['Option A', 'Option B', 'Option C', 'Option D'],
                    'correct_answer' => 'Option C',
                    'marks' => 1,
                    'explanation' => 'The correct tile combines the row and column patterns shown in the matrix.',
                ],
                [
                    'question_type' => 'pattern',
                    'difficulty_level' => 'medium',
                    'question_text' => 'Identify the next pattern in the sequence shown in the image.',
                    'question_image' => 'cognitive-questions/non-verbal-pattern-sample.svg',
                    'options' => ['Pattern A', 'Pattern B', 'Pattern C', 'Pattern D'],
                    'correct_answer' => 'Pattern B',
                    'marks' => 1,
                    'explanation' => 'The triangle keeps rotating clockwise and the circle keeps shifting outward.',
                ],
                [
                    'question_type' => 'odd_one_out',
                    'difficulty_level' => 'easy',
                    'question_text' => 'Identify the odd one out.',
                    'question_image' => null,
                    'options' => ['Horizontal line', 'Vertical line', 'Diagonal line', 'Blue color'],
                    'correct_answer' => 'Blue color',
                    'marks' => 1,
                    'explanation' => 'Three options are lines while one option is a color.',
                ],
                [
                    'question_type' => 'sequence',
                    'difficulty_level' => 'easy',
                    'question_text' => 'Which shape comes next in the repeating pattern: circle, square, circle, square, ____?',
                    'question_image' => null,
                    'options' => ['circle', 'triangle', 'rectangle', 'pentagon'],
                    'correct_answer' => 'circle',
                    'marks' => 1,
                    'explanation' => 'The pattern alternates between circle and square.',
                ],
                [
                    'question_type' => 'sequence',
                    'difficulty_level' => 'medium',
                    'question_text' => 'A marker moves clockwise around a square: top-left, top-right, bottom-right, ____. Choose the next position.',
                    'question_image' => null,
                    'options' => ['top-left', 'bottom-left', 'center', 'top-right'],
                    'correct_answer' => 'bottom-left',
                    'marks' => 1,
                    'explanation' => 'Moving clockwise around the corners leads next to the bottom-left corner.',
                ],
                [
                    'question_type' => 'classification',
                    'difficulty_level' => 'medium',
                    'question_text' => 'Which option does not have a vertical line of symmetry?',
                    'question_image' => null,
                    'options' => ['Square', 'Circle', 'Rectangle', 'Scalene triangle'],
                    'correct_answer' => 'Scalene triangle',
                    'marks' => 1,
                    'explanation' => 'A scalene triangle has no line of symmetry.',
                ],
                [
                    'question_type' => 'classification',
                    'difficulty_level' => 'easy',
                    'question_text' => 'Which figure has the greatest number of corners?',
                    'question_image' => null,
                    'options' => ['Triangle', 'Square', 'Pentagon', 'Hexagon'],
                    'correct_answer' => 'Hexagon',
                    'marks' => 1,
                    'explanation' => 'A hexagon has six corners, more than the other options.',
                ],
                [
                    'question_type' => 'odd_one_out',
                    'difficulty_level' => 'easy',
                    'question_text' => 'Which option is not a flat 2D figure?',
                    'question_image' => null,
                    'options' => ['Circle', 'Rectangle', 'Cylinder', 'Triangle'],
                    'correct_answer' => 'Cylinder',
                    'marks' => 1,
                    'explanation' => 'Cylinder is a 3D solid while the others are flat 2D figures.',
                ],
                [
                    'question_type' => 'pattern',
                    'difficulty_level' => 'medium',
                    'question_text' => 'A figure gains one extra side each step: triangle, square, pentagon, ____. Choose the next figure.',
                    'question_image' => null,
                    'options' => ['hexagon', 'heptagon', 'octagon', 'circle'],
                    'correct_answer' => 'hexagon',
                    'marks' => 1,
                    'explanation' => 'The number of sides increases by one each step: 3, 4, 5, 6.',
                ],
                [
                    'question_type' => 'classification',
                    'difficulty_level' => 'medium',
                    'question_text' => 'If the first figure has 1 circle and each new figure adds exactly 1 more circle, how many circles does the fourth figure have?',
                    'question_image' => null,
                    'options' => ['2', '3', '4', '5'],
                    'correct_answer' => '4',
                    'marks' => 1,
                    'explanation' => 'The figures would contain 1, 2, 3, and then 4 circles.',
                ],
            ],
            CognitiveAssessmentSection::SKILL_SPATIAL => [
                [
                    'question_type' => 'shape_rotation',
                    'difficulty_level' => 'medium',
                    'question_text' => 'Review the shape image and choose the correct rotated version.',
                    'question_image' => 'cognitive-questions/spatial-shape-rotation-sample.svg',
                    'options' => ['Rotation A', 'Rotation B', 'Rotation C', 'Rotation D'],
                    'correct_answer' => 'Rotation B',
                    'marks' => 1,
                    'explanation' => 'Rotation B matches the target after rotation without flipping it.',
                ],
                [
                    'question_type' => 'mirror_image',
                    'difficulty_level' => 'medium',
                    'question_text' => 'Which option shows the correct mirror image of the shape?',
                    'question_image' => 'cognitive-questions/spatial-mirror-image-sample.svg',
                    'options' => ['Mirror A', 'Mirror B', 'Mirror C', 'Mirror D'],
                    'correct_answer' => 'Mirror D',
                    'marks' => 1,
                    'explanation' => 'Mirror D places each part of the shape and the marker on the correct reflected side.',
                ],
                [
                    'question_type' => 'classification',
                    'difficulty_level' => 'easy',
                    'question_text' => 'Which option is a 3D shape?',
                    'question_image' => null,
                    'options' => ['Circle', 'Triangle', 'Cube', 'Square'],
                    'correct_answer' => 'Cube',
                    'marks' => 1,
                    'explanation' => 'Cube is the only solid three-dimensional shape listed.',
                ],
                [
                    'question_type' => 'shape_rotation',
                    'difficulty_level' => 'easy',
                    'question_text' => 'If an arrow points north and is rotated 90 degrees clockwise, which direction will it point?',
                    'question_image' => null,
                    'options' => ['West', 'East', 'South', 'North'],
                    'correct_answer' => 'East',
                    'marks' => 1,
                    'explanation' => 'A 90-degree clockwise turn from north points east.',
                ],
                [
                    'question_type' => 'mirror_image',
                    'difficulty_level' => 'medium',
                    'question_text' => 'Which capital letter looks the same in a vertical mirror?',
                    'question_image' => null,
                    'options' => ['A', 'F', 'G', 'R'],
                    'correct_answer' => 'A',
                    'marks' => 1,
                    'explanation' => 'A has vertical symmetry while the other letters do not.',
                ],
                [
                    'question_type' => 'classification',
                    'difficulty_level' => 'medium',
                    'question_text' => 'How many faces meet at one corner of a cube?',
                    'question_image' => null,
                    'options' => ['2', '3', '4', '6'],
                    'correct_answer' => '3',
                    'marks' => 1,
                    'explanation' => 'Every corner of a cube is formed by three faces.',
                ],
                [
                    'question_type' => 'classification',
                    'difficulty_level' => 'medium',
                    'question_text' => 'Which solid can both roll smoothly and stand upright on a flat surface?',
                    'question_image' => null,
                    'options' => ['Sphere', 'Cylinder', 'Cube', 'Cone'],
                    'correct_answer' => 'Cylinder',
                    'marks' => 1,
                    'explanation' => 'A cylinder rolls on its curved side and also stands on its flat circular base.',
                ],
                [
                    'question_type' => 'pattern',
                    'difficulty_level' => 'easy',
                    'question_text' => 'A square paper is folded once exactly in half. How many equal parts does the fold create?',
                    'question_image' => null,
                    'options' => ['1', '2', '3', '4'],
                    'correct_answer' => '2',
                    'marks' => 1,
                    'explanation' => 'One fold in half creates two equal parts.',
                ],
                [
                    'question_type' => 'odd_one_out',
                    'difficulty_level' => 'easy',
                    'question_text' => 'Which option is not mainly a three-dimensional object?',
                    'question_image' => null,
                    'options' => ['Cube', 'Sphere', 'Triangle', 'Cone'],
                    'correct_answer' => 'Triangle',
                    'marks' => 1,
                    'explanation' => 'Triangle is a flat 2D figure while the others are solids.',
                ],
                [
                    'question_type' => 'shape_rotation',
                    'difficulty_level' => 'medium',
                    'question_text' => 'If an L-shaped figure is rotated 180 degrees, what stays true?',
                    'question_image' => null,
                    'options' => [
                        'It becomes a mirror image',
                        'It points in the opposite direction while keeping the same shape',
                        'It changes into a T-shape',
                        'It loses one arm',
                    ],
                    'correct_answer' => 'It points in the opposite direction while keeping the same shape',
                    'marks' => 1,
                    'explanation' => 'A 180-degree rotation changes orientation but does not change the actual shape.',
                ],
            ],
            CognitiveAssessmentSection::SKILL_QUANTITATIVE => [
                [
                    'question_type' => 'sequence',
                    'difficulty_level' => 'easy',
                    'question_text' => 'Find the next number: 2, 4, 8, 16, ____.',
                    'question_image' => null,
                    'options' => ['18', '24', '32', '64'],
                    'correct_answer' => '32',
                    'marks' => 1,
                    'explanation' => 'Each number doubles the one before it.',
                ],
                [
                    'question_type' => 'number_series',
                    'difficulty_level' => 'easy',
                    'question_text' => 'Find the missing number: 3, 6, 12, 24, ____.',
                    'question_image' => null,
                    'options' => ['30', '36', '42', '48'],
                    'correct_answer' => '48',
                    'marks' => 1,
                    'explanation' => 'The pattern doubles each time.',
                ],
                [
                    'question_type' => 'mcq',
                    'difficulty_level' => 'easy',
                    'question_text' => 'If 5 notebooks cost 250, what is the cost of 8 notebooks?',
                    'question_image' => null,
                    'options' => ['300', '350', '400', '450'],
                    'correct_answer' => '400',
                    'marks' => 1,
                    'explanation' => 'Each notebook costs 50, so 8 notebooks cost 400.',
                ],
                [
                    'question_type' => 'number_series',
                    'difficulty_level' => 'easy',
                    'question_text' => 'Find the next number: 11, 14, 17, 20, ____.',
                    'question_image' => null,
                    'options' => ['21', '22', '23', '24'],
                    'correct_answer' => '23',
                    'marks' => 1,
                    'explanation' => 'The sequence increases by 3 each time.',
                ],
                [
                    'question_type' => 'mcq',
                    'difficulty_level' => 'medium',
                    'question_text' => 'A bus can seat 42 students. What is the smallest number of buses needed for 120 students?',
                    'question_image' => null,
                    'options' => ['2', '3', '4', '5'],
                    'correct_answer' => '3',
                    'marks' => 1,
                    'explanation' => 'Two buses hold only 84 students, while three buses hold 126.',
                ],
                [
                    'question_type' => 'mcq',
                    'difficulty_level' => 'medium',
                    'question_text' => 'What is 15% of 200?',
                    'question_image' => null,
                    'options' => ['15', '20', '25', '30'],
                    'correct_answer' => '30',
                    'marks' => 1,
                    'explanation' => 'Ten percent of 200 is 20 and five percent is 10, giving 30.',
                ],
                [
                    'question_type' => 'sequence',
                    'difficulty_level' => 'easy',
                    'question_text' => 'Find the next number: 1, 4, 9, 16, ____.',
                    'question_image' => null,
                    'options' => ['20', '24', '25', '36'],
                    'correct_answer' => '25',
                    'marks' => 1,
                    'explanation' => 'These are square numbers ending with 5 squared.',
                ],
                [
                    'question_type' => 'mcq',
                    'difficulty_level' => 'medium',
                    'question_text' => 'If 2 pencils cost 18, what is the cost of 5 pencils at the same rate?',
                    'question_image' => null,
                    'options' => ['36', '40', '45', '50'],
                    'correct_answer' => '45',
                    'marks' => 1,
                    'explanation' => 'One pencil costs 9, so 5 pencils cost 45.',
                ],
                [
                    'question_type' => 'mcq',
                    'difficulty_level' => 'medium',
                    'question_text' => 'A clock shows 3:00. What time will it show after 135 minutes?',
                    'question_image' => null,
                    'options' => ['4:45', '5:00', '5:15', '5:30'],
                    'correct_answer' => '5:15',
                    'marks' => 1,
                    'explanation' => '135 minutes is 2 hours and 15 minutes after 3:00.',
                ],
                [
                    'question_type' => 'number_series',
                    'difficulty_level' => 'easy',
                    'question_text' => 'What number should replace x: 7, 10, 13, x, 19?',
                    'question_image' => null,
                    'options' => ['14', '15', '16', '17'],
                    'correct_answer' => '16',
                    'marks' => 1,
                    'explanation' => 'The sequence increases by 3 each time: 7, 10, 13, 16, 19.',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    private function sectionPayloads(): array
    {
        return [
            [
                'skill' => CognitiveAssessmentSection::SKILL_VERBAL,
                'title' => 'Verbal Reasoning',
                'duration_seconds' => 600,
                'sort_order' => 1,
            ],
            [
                'skill' => CognitiveAssessmentSection::SKILL_NON_VERBAL,
                'title' => 'Non-Verbal Reasoning',
                'duration_seconds' => 600,
                'sort_order' => 2,
            ],
            [
                'skill' => CognitiveAssessmentSection::SKILL_SPATIAL,
                'title' => 'Spatial Reasoning',
                'duration_seconds' => 600,
                'sort_order' => 3,
            ],
            [
                'skill' => CognitiveAssessmentSection::SKILL_QUANTITATIVE,
                'title' => 'Quantitative Reasoning',
                'duration_seconds' => 600,
                'sort_order' => 4,
            ],
        ];
    }
}
