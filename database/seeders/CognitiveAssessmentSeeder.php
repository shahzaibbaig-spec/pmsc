<?php

namespace Database\Seeders;

use App\Models\CognitiveAssessment;
use App\Models\CognitiveAssessmentQuestion;
use App\Models\CognitiveAssessmentSection;
use App\Models\CognitiveAssessmentSectionQuestion;
use App\Models\CognitiveBankQuestion;
use App\Models\CognitiveQuestionBank;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class CognitiveAssessmentSeeder extends Seeder
{
    public function run(): void
    {
        $assessment = CognitiveAssessment::query()->updateOrCreate(
            ['slug' => CognitiveAssessment::LEVEL_4_SLUG],
            [
                'title' => CognitiveAssessment::LEVEL_4_TITLE,
                'description' => 'Internal cognitive assessment for Grades 8 to 12',
                'is_active' => true,
            ]
        );

        $sections = [
            [
                'skill' => CognitiveAssessmentSection::SKILL_VERBAL,
                'title' => 'Verbal Reasoning',
                'sort_order' => 1,
                'duration_seconds' => 600,
            ],
            [
                'skill' => CognitiveAssessmentSection::SKILL_NON_VERBAL,
                'title' => 'Non-Verbal Reasoning',
                'sort_order' => 2,
                'duration_seconds' => 600,
            ],
            [
                'skill' => CognitiveAssessmentSection::SKILL_SPATIAL,
                'title' => 'Spatial Reasoning',
                'sort_order' => 3,
                'duration_seconds' => 600,
            ],
            [
                'skill' => CognitiveAssessmentSection::SKILL_QUANTITATIVE,
                'title' => 'Quantitative Reasoning',
                'sort_order' => 4,
                'duration_seconds' => 600,
            ],
        ];

        $sectionMap = collect($sections)
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

        if ($this->questionBankTablesAvailable()) {
            $this->seedQuestionBankAssessment($sectionMap);

            return;
        }

        $this->seedLegacyAssessmentQuestions($sectionMap);
    }

    private function seedQuestionBankAssessment($sectionMap): void
    {
        $creator = User::query()->firstOrCreate(
            ['email' => 'admin@pmsc.edu.pk'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'must_change_password' => false,
                'password_changed_at' => now(),
            ]
        );

        $bank = CognitiveQuestionBank::query()->updateOrCreate(
            ['slug' => 'cognitive-skills-assessment-test-level-4-question-bank'],
            [
                'title' => 'Cognitive Skills Assessment Test Level 4 Question Bank',
                'description' => 'Reusable bank for the internal cognitive assessment used in Grades 8 to 12.',
                'is_active' => true,
                'created_by' => $creator->id,
            ]
        );

        $questionPayloads = $this->questionBankSeedPayloads();

        foreach ($questionPayloads as $skill => $questions) {
            /** @var CognitiveAssessmentSection $section */
            $section = $sectionMap[$skill];
            $totalMarks = 0;

            foreach ($questions as $index => $questionPayload) {
                $question = CognitiveBankQuestion::query()->firstOrNew([
                    'question_bank_id' => $bank->id,
                    'skill' => $skill,
                    'question_type' => $questionPayload['question_type'],
                    'question_text' => $questionPayload['question_text'],
                ]);

                $question->fill([
                    'difficulty_level' => $questionPayload['difficulty_level'] ?? null,
                    'explanation' => $questionPayload['explanation'] ?? null,
                    'options' => $questionPayload['options'],
                    'correct_answer' => $questionPayload['correct_answer'],
                    'marks' => (int) ($questionPayload['marks'] ?? 1),
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]);

                if (empty($question->question_image)) {
                    $question->question_image = $questionPayload['question_image'] ?? null;
                }

                $question->save();

                CognitiveAssessmentSectionQuestion::query()->updateOrCreate(
                    [
                        'section_id' => $section->id,
                        'bank_question_id' => $question->id,
                    ],
                    [
                        'sort_order' => $index + 1,
                    ]
                );

                $totalMarks += (int) $question->marks;
            }

            $section->forceFill(['total_marks' => $totalMarks])->save();
        }
    }

    private function seedLegacyAssessmentQuestions($sectionMap): void
    {
        $hasDifficultyLevel = Schema::hasColumn('cognitive_assessment_questions', 'difficulty_level');
        $hasExplanation = Schema::hasColumn('cognitive_assessment_questions', 'explanation');

        foreach ($this->questionBankSeedPayloads() as $skill => $questions) {
            /** @var CognitiveAssessmentSection $section */
            $section = $sectionMap[$skill];
            $totalMarks = 0;

            foreach ($questions as $index => $questionPayload) {
                $marks = (int) ($questionPayload['marks'] ?? 1);
                $totalMarks += $marks;

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
            }

            $section->forceFill(['total_marks' => $totalMarks])->save();
        }
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function questionBankSeedPayloads(): array
    {
        return CognitiveSkillsAssessmentLevel4Seeder::questionPayloads();
    }

    private function questionBankTablesAvailable(): bool
    {
        return Schema::hasTable('cognitive_question_banks')
            && Schema::hasTable('cognitive_bank_questions')
            && Schema::hasTable('cognitive_assessment_section_questions');
    }
}
