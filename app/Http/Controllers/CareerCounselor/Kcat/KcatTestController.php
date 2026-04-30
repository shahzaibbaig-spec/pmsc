<?php

namespace App\Http\Controllers\CareerCounselor\Kcat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kcat\StoreKcatTestRequest;
use App\Models\KcatSection;
use App\Models\KcatTest;
use App\Services\Kcat\KcatTestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KcatTestController extends Controller
{
    public function __construct(private readonly KcatTestService $testService) {}

    public function index(): View
    {
        return view('career-counselor.kcat.tests.index', ['tests' => KcatTest::query()->withCount('questions')->latest()->paginate(20)]);
    }

    public function create(): View
    {
        return view('career-counselor.kcat.tests.create', ['test' => null]);
    }

    public function store(StoreKcatTestRequest $request): RedirectResponse
    {
        $test = $this->testService->createTest($request->validated(), $request->user());
        return redirect()->route('career-counselor.kcat.tests.show', $test)->with('success', 'KCAT test created.');
    }

    public function show(KcatTest $test): View
    {
        return view('career-counselor.kcat.tests.show', ['test' => $test->load(['sections.questions.options'])]);
    }

    public function edit(KcatTest $test): View
    {
        return view('career-counselor.kcat.tests.create', ['test' => $test]);
    }

    public function update(StoreKcatTestRequest $request, KcatTest $test): RedirectResponse
    {
        $this->testService->updateTest($test, $request->validated(), $request->user());
        return redirect()->route('career-counselor.kcat.tests.show', $test)->with('success', 'KCAT test updated.');
    }

    public function activate(Request $request, KcatTest $test): RedirectResponse
    {
        $this->testService->activateTest($test, $request->user());
        return back()->with('success', 'KCAT test activated.');
    }

    public function archive(Request $request, KcatTest $test): RedirectResponse
    {
        $this->testService->archiveTest($test, $request->user());
        return back()->with('success', 'KCAT test archived.');
    }

    public function storeSection(Request $request, KcatTest $test): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
        $this->testService->addSection($test, $validated);
        return back()->with('success', 'KCAT section added.');
    }

    public function updateSection(Request $request, KcatSection $section): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $section->update($validated);
        $this->testService->refreshTotals($section->test);

        return back()->with('success', 'KCAT section updated.');
    }

    public function destroySection(KcatSection $section): RedirectResponse
    {
        abort_if($section->questions()->exists(), 422, 'Remove questions before deleting this section.');

        $test = $section->test;
        $section->delete();
        $this->testService->refreshTotals($test);

        return back()->with('success', 'KCAT section deleted.');
    }
}
