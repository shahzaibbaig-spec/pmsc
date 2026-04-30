<?php

namespace App\Http\Controllers\CareerCounselor\Kcat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kcat\StoreKcatQuestionRequest;
use App\Models\KcatQuestion;
use App\Models\KcatSection;
use App\Models\KcatTest;
use App\Services\Kcat\KcatTestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class KcatQuestionController extends Controller
{
    public function __construct(private readonly KcatTestService $testService) {}

    public function create(KcatTest $test): View
    {
        return view('career-counselor.kcat.questions.create', ['test' => $test->load('sections'), 'question' => null]);
    }

    public function store(StoreKcatQuestionRequest $request): RedirectResponse
    {
        $section = KcatSection::query()->with('test')->findOrFail((int) $request->validated('kcat_section_id'));
        $question = $this->testService->addQuestion($section, $request->validated(), $request->user());
        return redirect()->route('career-counselor.kcat.tests.show', $question->kcat_test_id)->with('success', 'KCAT question added.');
    }

    public function edit(KcatQuestion $question): View
    {
        return view('career-counselor.kcat.questions.edit', ['question' => $question->load(['options', 'test.sections']), 'test' => $question->test]);
    }

    public function update(StoreKcatQuestionRequest $request, KcatQuestion $question): RedirectResponse
    {
        $this->testService->updateQuestion($question, $request->validated(), $request->user());
        return redirect()->route('career-counselor.kcat.tests.show', $question->kcat_test_id)->with('success', 'KCAT question updated.');
    }

    public function destroy(KcatQuestion $question): RedirectResponse
    {
        $question->update(['is_active' => false]);
        $this->testService->refreshTotals($question->test);
        return back()->with('success', 'KCAT question deactivated.');
    }
}
