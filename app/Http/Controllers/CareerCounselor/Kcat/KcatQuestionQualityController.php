<?php

namespace App\Http\Controllers\CareerCounselor\Kcat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kcat\StoreKcatQuestionReviewRequest;
use App\Models\KcatQuestion;
use App\Models\KcatSection;
use App\Services\Kcat\KcatQuestionQualityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KcatQuestionQualityController extends Controller
{
    public function __construct(private readonly KcatQuestionQualityService $qualityService) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['section_id', 'difficulty', 'review_status', 'discrimination_flag', 'only_flagged']);

        return view('career-counselor.kcat.question-quality.index', [
            'questions' => $this->qualityService->flagWeakQuestions($filters),
            'sections' => KcatSection::query()->orderBy('sort_order')->orderBy('name')->get(),
            'summary' => $this->qualityService->reviewStatsSummary(),
            'filters' => $filters,
        ]);
    }

    public function show(KcatQuestion $question): View
    {
        return view('career-counselor.kcat.question-quality.show', [
            'question' => $question->load(['section', 'test', 'options', 'reviews.reviewer']),
            'analysis' => $this->qualityService->analyzeQuestion($question),
        ]);
    }

    public function storeReview(StoreKcatQuestionReviewRequest $request, KcatQuestion $question): RedirectResponse
    {
        $this->qualityService->submitReview($question, $request->validated(), $request->user());
        return back()->with('success', 'Question review saved.');
    }

    public function approve(Request $request, KcatQuestion $question): RedirectResponse
    {
        $this->qualityService->approveQuestion($question, $request->user());
        return back()->with('success', 'Question approved.');
    }

    public function retire(Request $request, KcatQuestion $question): RedirectResponse
    {
        $this->qualityService->retireQuestion($question, $request->user());
        return back()->with('success', 'Question retired. It will remain linked with previous attempts.');
    }
}

