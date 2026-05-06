<?php

namespace App\Http\Controllers\CareerCounselor\Kcat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kcat\StoreKcatManualAttemptRequest;
use App\Models\KcatAttempt;
use App\Models\KcatTest;
use App\Models\Student;
use App\Services\Kcat\KcatAttemptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class KcatAttemptController extends Controller
{
    public function __construct(private readonly KcatAttemptService $attemptService) {}

    public function index(): View
    {
        return view('career-counselor.kcat.assignments.index', [
            'assignments' => collect(),
            'attempts' => KcatAttempt::query()->with(['student.classRoom', 'test'])->latest('submitted_at')->paginate(20),
        ]);
    }

    public function show(KcatAttempt $attempt): RedirectResponse
    {
        return redirect()->route('career-counselor.kcat.reports.show', $attempt);
    }

    public function manualEntry(KcatTest $test): View
    {
        return view('career-counselor.kcat.attempts.manual-entry', [
            'test' => $test->load([
                'sections.questions' => fn ($query) => $query->where('is_active', true)->whereNull('retired_at'),
                'sections.questions.options',
            ]),
        ]);
    }

    public function storeManualEntry(StoreKcatManualAttemptRequest $request, KcatTest $test): RedirectResponse
    {
        $student = Student::query()->findOrFail((int) $request->validated('student_id'));
        $attempt = $this->attemptService->manualEntry($test, $student, $request->validated('answers'), $request->user());
        return redirect()->route('career-counselor.kcat.reports.show', $attempt)->with('success', 'Manual KCAT attempt scored.');
    }
}
