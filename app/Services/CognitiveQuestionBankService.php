<?php

namespace App\Services;

use App\Models\CognitiveAssessmentAttempt;
use App\Models\CognitiveAssessmentSection;
use App\Models\CognitiveAssessmentSectionQuestion;
use App\Models\CognitiveBankQuestion;
use App\Models\CognitiveQuestionBank;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CognitiveQuestionBankService
{
    public const QUESTION_TYPES = [
        'mcq',
        'analogy',
        'classification',
        'odd_one_out',
        'sequence',
        'number_series',
        'matrix',
        'pattern',
        'shape_rotation',
        'mirror_image',
    ];

    public const IMAGE_RECOMMENDED_TYPES = [
        'matrix',
        'pattern',
        'shape_rotation',
        'mirror_image',
    ];

    /**
     * @return array<int, string>
     */
    public function questionTypes(): array
    {
        return self::QUESTION_TYPES;
    }

    /**
     * @return array<string, string>
     */
    public function skills(): array
    {
        return [
            CognitiveAssessmentSection::SKILL_VERBAL => 'Verbal Reasoning',
            CognitiveAssessmentSection::SKILL_NON_VERBAL => 'Non-Verbal Reasoning',
            CognitiveAssessmentSection::SKILL_QUANTITATIVE => 'Quantitative Reasoning',
            CognitiveAssessmentSection::SKILL_SPATIAL => 'Spatial Reasoning',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function imageRecommendedTypes(): array
    {
        return self::IMAGE_RECOMMENDED_TYPES;
    }

    public function createOrUpdateQuestionBank(array $data, ?CognitiveQuestionBank $bank = null): CognitiveQuestionBank
    {
        return DB::transaction(function () use ($data, $bank): CognitiveQuestionBank {
            $bank ??= new CognitiveQuestionBank();

            $creatorId = $bank->created_by ?: (int) ($data['created_by'] ?? auth()->id() ?? User::query()->value('id'));
            if ($creatorId <= 0) {
                throw ValidationException::withMessages([
                    'title' => 'A creator user is required before creating a question bank.',
                ]);
            }

            $bank->fill([
                'title' => trim((string) ($data['title'] ?? '')),
                'slug' => trim((string) ($data['slug'] ?? '')),
                'description' => $this->nullableString($data['description'] ?? null),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'created_by' => $creatorId,
            ])->save();

            return $bank->fresh(['creator']) ?? $bank;
        });
    }

    public function createOrUpdateBankQuestion(array $data, ?UploadedFile $image = null, ?CognitiveBankQuestion $question = null): CognitiveBankQuestion
    {
        $payload = $this->validatedQuestionPayload($data);

        return DB::transaction(function () use ($payload, $image, $question): CognitiveBankQuestion {
            $question ??= new CognitiveBankQuestion();
            $oldImagePath = $question->question_image;

            $question->fill($payload);

            if ($image) {
                $question->question_image = $image->store('cognitive-questions', 'public');
            }

            $question->save();

            if ($image && $oldImagePath && $oldImagePath !== $question->question_image) {
                Storage::disk('public')->delete($oldImagePath);
            }

            return $question->fresh(['questionBank', 'sections']) ?? $question;
        });
    }

    public function assignQuestionsToSection(int $sectionId, array $bankQuestionIds): void
    {
        DB::transaction(function () use ($sectionId, $bankQuestionIds): void {
            $section = CognitiveAssessmentSection::query()->lockForUpdate()->findOrFail($sectionId);
            $questionIds = collect($bankQuestionIds)
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values();

            $questions = CognitiveBankQuestion::query()
                ->whereIn('id', $questionIds)
                ->get()
                ->keyBy('id');

            if ($questions->count() !== $questionIds->count()) {
                throw ValidationException::withMessages([
                    'bank_question_ids' => 'One or more selected questions could not be found.',
                ]);
            }

            foreach ($questionIds as $index => $questionId) {
                $question = $questions->get($questionId);

                if (! $question?->is_active) {
                    throw ValidationException::withMessages([
                        'bank_question_ids' => 'Only active bank questions can be assigned to a section.',
                    ]);
                }

                if ((string) $question->skill !== (string) $section->skill) {
                    throw ValidationException::withMessages([
                        'bank_question_ids' => 'Assigned questions must match the section skill.',
                    ]);
                }

                CognitiveAssessmentSectionQuestion::query()->updateOrCreate(
                    [
                        'section_id' => $section->id,
                        'bank_question_id' => $questionId,
                    ],
                    [
                        'sort_order' => $index + 1,
                    ]
                );
            }

            $this->updateSectionTotalMarks($section->id);
        });
    }

    public function removeQuestionFromSection(int $sectionId, int $bankQuestionId): void
    {
        DB::transaction(function () use ($sectionId, $bankQuestionId): void {
            $section = CognitiveAssessmentSection::query()->lockForUpdate()->findOrFail($sectionId);

            $usedInAttempt = CognitiveAssessmentAttempt::query()
                ->where('assessment_id', $section->assessment_id)
                ->whereHas('responses', fn ($query) => $query->where('bank_question_id', $bankQuestionId))
                ->exists();

            if ($usedInAttempt) {
                throw ValidationException::withMessages([
                    'bank_question_ids' => 'This question has already been used in student attempts and cannot be removed from the section.',
                ]);
            }

            CognitiveAssessmentSectionQuestion::query()
                ->where('section_id', $section->id)
                ->where('bank_question_id', $bankQuestionId)
                ->delete();

            $remainingIds = CognitiveAssessmentSectionQuestion::query()
                ->where('section_id', $section->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->pluck('bank_question_id');

            foreach ($remainingIds as $index => $questionId) {
                CognitiveAssessmentSectionQuestion::query()
                    ->where('section_id', $section->id)
                    ->where('bank_question_id', (int) $questionId)
                    ->update(['sort_order' => $index + 1]);
            }

            $this->updateSectionTotalMarks($section->id);
        });
    }

    /**
     * @return EloquentCollection<int, CognitiveBankQuestion>
     */
    public function getSectionQuestions(int $sectionId): EloquentCollection
    {
        $section = CognitiveAssessmentSection::query()
            ->with('questionAssignments.bankQuestion.questionBank')
            ->findOrFail($sectionId);

        return $section->questionAssignments
            ->pluck('bankQuestion')
            ->filter()
            ->values();
    }

    public function deleteQuestionImageIfNeeded(CognitiveBankQuestion $question): void
    {
        if (! empty($question->question_image)) {
            Storage::disk('public')->delete((string) $question->question_image);
        }
    }

    public function deleteQuestionBank(CognitiveQuestionBank $bank): void
    {
        if ($bank->bankQuestions()->whereHas('responses')->exists()) {
            throw ValidationException::withMessages([
                'bank' => 'This question bank contains questions already used in student attempts and cannot be deleted.',
            ]);
        }

        DB::transaction(function () use ($bank): void {
            $bank->load('bankQuestions');

            foreach ($bank->bankQuestions as $question) {
                $this->deleteQuestionImageIfNeeded($question);
            }

            $bank->delete();
        });
    }

    public function deleteBankQuestion(CognitiveBankQuestion $question): void
    {
        if ($question->responses()->exists()) {
            throw ValidationException::withMessages([
                'question' => 'This question has already been used in student attempts and cannot be deleted.',
            ]);
        }

        DB::transaction(function () use ($question): void {
            $sectionIds = $question->sectionAssignments()->pluck('section_id');
            $question->sections()->detach();
            $this->deleteQuestionImageIfNeeded($question);
            $question->delete();

            foreach ($sectionIds as $sectionId) {
                $this->updateSectionTotalMarks((int) $sectionId);
            }
        });
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function validatedQuestionPayload(array $data): array
    {
        $options = collect($data['options'] ?? [])
            ->map(fn ($option): string => trim((string) $option))
            ->filter(fn (string $option): bool => $option !== '')
            ->values();
        $correctAnswer = trim((string) ($data['correct_answer'] ?? ''));

        if ($options->count() < 2) {
            throw ValidationException::withMessages([
                'options' => 'At least two options are required for an objective question.',
            ]);
        }

        if (! $options->contains($correctAnswer)) {
            throw ValidationException::withMessages([
                'correct_answer' => 'The correct answer must match one of the options.',
            ]);
        }

        return [
            'question_bank_id' => (int) ($data['question_bank_id'] ?? 0),
            'skill' => (string) ($data['skill'] ?? ''),
            'question_type' => trim((string) ($data['question_type'] ?? '')),
            'difficulty_level' => $this->nullableString($data['difficulty_level'] ?? null),
            'question_text' => $this->nullableString($data['question_text'] ?? null),
            'explanation' => $this->nullableString($data['explanation'] ?? null),
            'options' => $options->all(),
            'correct_answer' => $correctAnswer,
            'marks' => max((int) ($data['marks'] ?? 1), 1),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function updateSectionTotalMarks(int $sectionId): void
    {
        $section = CognitiveAssessmentSection::query()->findOrFail($sectionId);
        $totalMarks = CognitiveAssessmentSectionQuestion::query()
            ->where('section_id', $sectionId)
            ->join('cognitive_bank_questions', 'cognitive_bank_questions.id', '=', 'cognitive_assessment_section_questions.bank_question_id')
            ->sum('cognitive_bank_questions.marks');

        $section->forceFill([
            'total_marks' => (int) $totalMarks,
        ])->save();
    }

    private function nullableString(mixed $value): ?string
    {
        $resolved = trim((string) $value);

        return $resolved === '' ? null : $resolved;
    }
}
