<?php

namespace App\Modules\Assessments\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCognitiveAssessmentResponsesRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('responses') || ! $this->has('answers')) {
            return;
        }

        $answers = $this->input('answers', []);
        if (! is_array($answers)) {
            return;
        }

        $responses = collect($answers)
            ->map(function ($selectedAnswer, $questionKey): ?array {
                $resolved = $this->parseQuestionIdentifier((string) $questionKey);
                if ($resolved === null) {
                    return null;
                }

                return [
                    'question_id' => $resolved['question_id'],
                    'bank_question_id' => $resolved['bank_question_id'],
                    'selected_answer' => $selectedAnswer,
                ];
            })
            ->filter()
            ->values()
            ->all();

        $this->merge([
            'responses' => $responses,
        ]);
    }

    public function authorize(): bool
    {
        $user = $this->user();

        return ($user?->hasRole('Student') ?? false)
            && ($user?->can('take_cognitive_assessment') ?? false);
    }

    public function rules(): array
    {
        return [
            'responses' => ['nullable', 'array'],
            'responses.*.question_id' => ['nullable', 'integer', Rule::exists('cognitive_assessment_questions', 'id')],
            'responses.*.bank_question_id' => ['nullable', 'integer', Rule::exists('cognitive_bank_questions', 'id')],
            'responses.*.selected_answer' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            foreach ($this->input('responses', []) as $index => $response) {
                $hasLegacy = ! empty($response['question_id']);
                $hasBank = ! empty($response['bank_question_id']);

                if ($hasLegacy === $hasBank) {
                    $validator->errors()->add(
                        "responses.$index.question_id",
                        'Each response must reference exactly one assessment question source.'
                    );
                }
            }
        });
    }

    /**
     * @return array{question_id:int|null,bank_question_id:int|null}|null
     */
    private function parseQuestionIdentifier(string $questionKey): ?array
    {
        if (preg_match('/^\d+$/', $questionKey) === 1) {
            return [
                'question_id' => (int) $questionKey,
                'bank_question_id' => null,
            ];
        }

        if (preg_match('/^legacy[:\-](\d+)$/', $questionKey, $matches) === 1) {
            return [
                'question_id' => (int) ($matches[1] ?? 0),
                'bank_question_id' => null,
            ];
        }

        if (preg_match('/^bank[:\-](\d+)$/', $questionKey, $matches) === 1) {
            return [
                'question_id' => null,
                'bank_question_id' => (int) ($matches[1] ?? 0),
            ];
        }

        return null;
    }
}
