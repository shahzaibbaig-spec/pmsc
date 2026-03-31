<?php

namespace App\Modules\Assessments\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CognitiveAssessmentSection;
use App\Models\CognitiveBankQuestion;
use App\Modules\Assessments\Requests\AssignSectionQuestionsRequest;
use App\Services\CognitiveQuestionBankService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminCognitiveAssessmentSectionQuestionController extends Controller
{
    public function __construct(private readonly CognitiveQuestionBankService $questionBankService)
    {
    }

    public function edit(CognitiveAssessmentSection $section): View
    {
        $section->load(['assessment', 'questionAssignments.bankQuestion.questionBank']);

        $availableQuestions = CognitiveBankQuestion::query()
            ->with('questionBank')
            ->where('skill', $section->skill)
            ->where('is_active', true)
            ->orderBy('question_bank_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $selectedIds = $section->questionAssignments
            ->pluck('bank_question_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $sortOrders = $section->questionAssignments
            ->mapWithKeys(fn ($assignment): array => [(string) $assignment->bank_question_id => (int) $assignment->sort_order])
            ->all();

        return view('admin.assessments.cognitive-skills-level-4.sections.questions', [
            'section' => $section,
            'availableQuestions' => $availableQuestions,
            'selectedIds' => $selectedIds,
            'sortOrders' => $sortOrders,
            'skillOptions' => $this->questionBankService->skills(),
        ]);
    }

    public function update(AssignSectionQuestionsRequest $request, CognitiveAssessmentSection $section): RedirectResponse
    {
        $data = $request->validated();
        $selectedIds = collect($data['bank_question_ids'])
            ->sortBy(function ($id, $index) use ($data): array {
                return [
                    (int) ($data['sort_orders'][(string) $id] ?? ($index + 1)),
                    $index,
                ];
            })
            ->map(fn ($id): int => (int) $id)
            ->values();

        DB::transaction(function () use ($section, $selectedIds): void {
            $currentIds = $section->questionAssignments()
                ->orderBy('sort_order')
                ->pluck('bank_question_id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            foreach (array_diff($currentIds, $selectedIds->all()) as $removedId) {
                $this->questionBankService->removeQuestionFromSection((int) $section->id, (int) $removedId);
            }

            $this->questionBankService->assignQuestionsToSection((int) $section->id, $selectedIds->all());
        });

        return redirect()
            ->route('admin.assessments.cognitive-skills-level-4.sections.questions.edit', $section)
            ->with('status', 'Section questions updated successfully.');
    }

    public function remove(CognitiveAssessmentSection $section, CognitiveBankQuestion $bankQuestion): RedirectResponse
    {
        $this->questionBankService->removeQuestionFromSection((int) $section->id, (int) $bankQuestion->id);

        return redirect()
            ->route('admin.assessments.cognitive-skills-level-4.sections.questions.edit', $section)
            ->with('status', 'Question removed from section successfully.');
    }
}
