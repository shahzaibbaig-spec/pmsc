<?php

namespace App\Modules\Assessments\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CognitiveQuestionBank;
use App\Modules\Assessments\Requests\StoreCognitiveQuestionBankRequest;
use App\Modules\Assessments\Requests\UpdateCognitiveQuestionBankRequest;
use App\Services\CognitiveAssessmentService;
use App\Services\CognitiveQuestionBankService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use RuntimeException;

class AdminCognitiveQuestionBankController extends Controller
{
    public function __construct(
        private readonly CognitiveQuestionBankService $questionBankService,
        private readonly CognitiveAssessmentService $assessmentService
    ) {
    }

    public function index(): View
    {
        $banks = CognitiveQuestionBank::query()
            ->with('creator:id,name')
            ->withCount('bankQuestions')
            ->orderByDesc('is_active')
            ->orderBy('title')
            ->paginate(12);

        return view('admin.assessments.cognitive-skills-level-4.question-banks.index', [
            'banks' => $banks,
        ]);
    }

    public function create(): View
    {
        return view('admin.assessments.cognitive-skills-level-4.question-banks.create', [
            'bank' => new CognitiveQuestionBank([
                'is_active' => true,
            ]),
        ]);
    }

    public function store(StoreCognitiveQuestionBankRequest $request): RedirectResponse
    {
        $bank = $this->questionBankService->createOrUpdateQuestionBank($request->validated());

        return redirect()
            ->route('admin.assessments.cognitive-skills-level-4.question-banks.show', $bank)
            ->with('status', 'Question bank created successfully.');
    }

    public function show(CognitiveQuestionBank $bank): View
    {
        $bank->load([
            'creator:id,name',
            'bankQuestions.questionBank',
            'bankQuestions.sections',
        ]);

        return view('admin.assessments.cognitive-skills-level-4.question-banks.show', [
            'bank' => $bank,
            'assessmentSections' => $this->assessmentSections(),
            'skillOptions' => $this->questionBankService->skills(),
            'imageRecommendedTypes' => $this->questionBankService->imageRecommendedTypes(),
        ]);
    }

    public function edit(CognitiveQuestionBank $bank): View
    {
        return view('admin.assessments.cognitive-skills-level-4.question-banks.edit', [
            'bank' => $bank,
        ]);
    }

    public function update(UpdateCognitiveQuestionBankRequest $request, CognitiveQuestionBank $bank): RedirectResponse
    {
        $bank = $this->questionBankService->createOrUpdateQuestionBank($request->validated(), $bank);

        return redirect()
            ->route('admin.assessments.cognitive-skills-level-4.question-banks.show', $bank)
            ->with('status', 'Question bank updated successfully.');
    }

    public function destroy(CognitiveQuestionBank $bank): RedirectResponse
    {
        $this->questionBankService->deleteQuestionBank($bank);

        return redirect()
            ->route('admin.assessments.cognitive-skills-level-4.question-banks.index')
            ->with('status', 'Question bank deleted successfully.');
    }

    /**
     * @return Collection<int, mixed>
     */
    private function assessmentSections(): Collection
    {
        try {
            return $this->assessmentService->resolveAssessment()->sections;
        } catch (RuntimeException) {
            return collect();
        }
    }
}
