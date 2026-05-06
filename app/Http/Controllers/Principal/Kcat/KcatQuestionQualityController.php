<?php

namespace App\Http\Controllers\Principal\Kcat;

use App\Http\Controllers\Controller;
use App\Models\KcatQuestion;
use App\Models\KcatSection;
use App\Services\Kcat\KcatQuestionQualityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KcatQuestionQualityController extends Controller
{
    public function __construct(private readonly KcatQuestionQualityService $qualityService) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['section_id', 'difficulty', 'review_status', 'discrimination_flag', 'only_flagged']);

        return view('principal.kcat.question-quality.index', [
            'questions' => $this->qualityService->flagWeakQuestions($filters),
            'sections' => KcatSection::query()->orderBy('sort_order')->orderBy('name')->get(),
            'summary' => $this->qualityService->reviewStatsSummary(),
            'filters' => $filters,
        ]);
    }

    public function show(KcatQuestion $question): View
    {
        return view('principal.kcat.question-quality.show', [
            'question' => $question->load(['section', 'test', 'options', 'reviews.reviewer']),
            'analysis' => $this->qualityService->analyzeQuestion($question),
        ]);
    }
}

