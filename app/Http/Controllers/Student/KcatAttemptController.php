<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\KcatAssignment;
use App\Models\KcatAttempt;
use App\Models\KcatQuestion;
use App\Models\Student;
use App\Services\CognitiveAssessmentService;
use App\Services\Kcat\KcatAttemptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KcatAttemptController extends Controller
{
    public function __construct(
        private readonly KcatAttemptService $attemptService,
        private readonly CognitiveAssessmentService $studentResolver,
    ) {}

    public function index(Request $request): View
    {
        $student = $this->studentForUser($request);
        return view('student.kcat.assignments.index', [
            'assignments' => KcatAssignment::query()
                ->with('test')
                ->where(function ($query) use ($student): void {
                    $query->where('student_id', $student->id)->orWhere('class_id', $student->class_id);
                })
                ->latest('assigned_at')
                ->get(),
        ]);
    }

    public function start(Request $request, KcatAssignment $assignment): RedirectResponse
    {
        $attempt = $this->attemptService->startAttempt($assignment, $this->studentForUser($request));
        return redirect()->route('student.kcat.attempts.question', [$attempt, 0]);
    }

    public function question(KcatAttempt $attempt, int $index = 0): View
    {
        abort_unless((int) $attempt->student_id === (int) $this->studentForUser(request())->id, 403);
        abort_unless($attempt->status === 'in_progress', 403);
        $questions = $attempt->test->questions()->where('is_active', true)->with('options')->get();
        return view('student.kcat.attempts.question', ['attempt' => $attempt, 'questions' => $questions, 'question' => $questions->get($index), 'index' => $index]);
    }

    public function answer(Request $request, KcatAttempt $attempt, KcatQuestion $question): RedirectResponse
    {
        abort_unless((int) $attempt->student_id === (int) $this->studentForUser($request)->id, 403);
        $validated = $request->validate(['selected_option_id' => ['nullable', 'exists:kcat_question_options,id'], 'answer_text' => ['nullable', 'string'], 'next_index' => ['nullable', 'integer']]);
        $this->attemptService->saveAnswer($attempt, $question, $validated);
        return redirect()->route('student.kcat.attempts.question', [$attempt, (int) ($validated['next_index'] ?? 0)]);
    }

    public function submit(Request $request, KcatAttempt $attempt): RedirectResponse
    {
        abort_unless((int) $attempt->student_id === (int) $this->studentForUser($request)->id, 403);
        $this->attemptService->submitAttempt($attempt);
        return redirect()->route('student.kcat.attempts.result', $attempt);
    }

    public function result(Request $request, KcatAttempt $attempt): View
    {
        abort_unless((int) $attempt->student_id === (int) $this->studentForUser($request)->id, 403);
        return view('student.kcat.attempts.result', ['attempt' => $attempt->load(['test', 'scores.section', 'latestReportNote'])]);
    }

    private function studentForUser(Request $request): Student
    {
        $student = $this->studentResolver->resolveStudentForUser($request->user());
        abort_unless($student, 403);

        return $student;
    }
}
