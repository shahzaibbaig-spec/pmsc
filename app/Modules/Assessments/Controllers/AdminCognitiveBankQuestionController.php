<?php

namespace App\Modules\Assessments\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CognitiveBankQuestion;
use App\Models\CognitiveQuestionBank;
use App\Modules\Assessments\Requests\StoreCognitiveBankQuestionRequest;
use App\Modules\Assessments\Requests\UpdateCognitiveBankQuestionRequest;
use App\Services\CognitiveQuestionBankService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminCognitiveBankQuestionController extends Controller
{
    public function __construct(private readonly CognitiveQuestionBankService $questionBankService)
    {
    }

    public function create(CognitiveQuestionBank $bank): View
    {
        return view('admin.assessments.cognitive-skills-level-4.questions.create', [
            'bank' => $bank,
            'question' => new CognitiveBankQuestion([
                'question_bank_id' => $bank->id,
                'skill' => old('skill'),
                'question_type' => old('question_type', 'mcq'),
                'marks' => 1,
                'sort_order' => 0,
                'is_active' => true,
            ]),
            'skillOptions' => $this->questionBankService->skills(),
            'questionTypes' => $this->questionBankService->questionTypes(),
            'imageRecommendedTypes' => $this->questionBankService->imageRecommendedTypes(),
        ]);
    }

    public function store(StoreCognitiveBankQuestionRequest $request, CognitiveQuestionBank $bank): RedirectResponse
    {
        $this->questionBankService->createOrUpdateBankQuestion(
            $request->validated(),
            $request->file('question_image')
        );

        return redirect()
            ->route('admin.assessments.cognitive-skills-level-4.question-banks.show', $bank)
            ->with('status', 'Bank question created successfully.');
    }

    public function edit(CognitiveBankQuestion $question): View
    {
        $question->load('questionBank');

        return view('admin.assessments.cognitive-skills-level-4.questions.edit', [
            'bank' => $question->questionBank,
            'question' => $question,
            'skillOptions' => $this->questionBankService->skills(),
            'questionTypes' => $this->questionBankService->questionTypes(),
            'imageRecommendedTypes' => $this->questionBankService->imageRecommendedTypes(),
        ]);
    }

    public function update(UpdateCognitiveBankQuestionRequest $request, CognitiveBankQuestion $question): RedirectResponse
    {
        $question = $this->questionBankService->createOrUpdateBankQuestion(
            $request->validated(),
            $request->file('question_image'),
            $question
        );

        return redirect()
            ->route('admin.assessments.cognitive-skills-level-4.question-banks.show', $question->questionBank)
            ->with('status', 'Bank question updated successfully.');
    }

    public function destroy(CognitiveBankQuestion $question): RedirectResponse
    {
        $question->load('questionBank');
        $bank = $question->questionBank;

        $this->questionBankService->deleteBankQuestion($question);

        return redirect()
            ->route('admin.assessments.cognitive-skills-level-4.question-banks.show', $bank)
            ->with('status', 'Bank question deleted successfully.');
    }
}
