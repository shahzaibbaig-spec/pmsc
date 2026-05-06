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
        $assignment->loadMissing('test');
        $hasActiveQuestions = $assignment->test
            ? $assignment->test->questions()->where('is_active', true)->whereNull('retired_at')->exists()
            : false;

        if (! $hasActiveQuestions) {
            return back()->with('error', 'This KCAT assignment has no active questions yet. Please contact your teacher/counselor.');
        }

        $attempt = $this->attemptService->startAttempt($assignment, $this->studentForUser($request));
        if ($attempt->is_adaptive) {
            return redirect()->route('student.kcat.attempts.adaptive.next', $attempt);
        }

        return redirect()->route('student.kcat.attempts.question', [$attempt, 0]);
    }

    public function question(KcatAttempt $attempt, int $index = 0): View
    {
        abort_unless((int) $attempt->student_id === (int) $this->studentForUser(request())->id, 403);
        abort_unless($attempt->status === 'in_progress', 403);
        if ($attempt->is_adaptive) {
            abort(404);
        }
        $questions = $attempt->test->questions()->where('is_active', true)->whereNull('retired_at')->with('options')->get();
        return view('student.kcat.attempts.question', ['attempt' => $attempt, 'questions' => $questions, 'question' => $questions->get($index), 'index' => $index]);
    }

    public function answer(Request $request, KcatAttempt $attempt, KcatQuestion $question): RedirectResponse
    {
        abort_unless((int) $attempt->student_id === (int) $this->studentForUser($request)->id, 403);
        if ($attempt->is_adaptive) {
            abort(404);
        }
        $validated = $request->validate([
            'selected_option_id' => ['nullable', 'exists:kcat_question_options,id'],
            'answer_text' => ['nullable', 'string'],
            'response_time_seconds' => ['nullable', 'integer', 'min:0', 'max:3600'],
            'next_index' => ['nullable', 'integer'],
        ]);
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
        return view('student.kcat.attempts.result', ['attempt' => $attempt->load(['test', 'scores.section', 'latestReportNote', 'streamRecommendations'])]);
    }

    public function adaptiveQuestion(Request $request, KcatAttempt $attempt): View|RedirectResponse
    {
        abort_unless((int) $attempt->student_id === (int) $this->studentForUser($request)->id, 403);
        abort_unless($attempt->status === 'in_progress', 403);
        abort_unless($attempt->is_adaptive, 404);

        $question = $this->attemptService->getNextAdaptiveQuestion($attempt->fresh(['test.sections', 'answers']) ?? $attempt);
        if (! $question) {
            $this->attemptService->submitAttempt($attempt);
            return redirect()->route('student.kcat.attempts.result', $attempt);
        }
        $question->loadMissing('section');

        $attempt = $attempt->fresh(['test.sections', 'answers']) ?? $attempt;
        $state = $attempt->adaptive_state ?? [];
        $requiredPerSection = max((int) ($attempt->test?->questions_per_section ?? 10), 1);
        $currentSection = $question->section;
        $currentSectionCode = $currentSection?->code;
        $sectionState = $currentSectionCode ? ($state['sections'][$currentSectionCode] ?? []) : [];
        $sectionAnswered = (int) ($sectionState['answered'] ?? 0);
        $totalAnswered = collect($state['sections'] ?? [])->sum(fn (array $row): int => (int) ($row['answered'] ?? 0));
        $totalRequired = $requiredPerSection * max((int) $attempt->test?->sections?->count(), 1);

        return view('student.kcat.attempts.adaptive-question', [
            'attempt' => $attempt,
            'question' => $question->load('options', 'section'),
            'requiredPerSection' => $requiredPerSection,
            'sectionAnswered' => $sectionAnswered,
            'totalAnswered' => $totalAnswered,
            'totalRequired' => $totalRequired,
        ]);
    }

    public function adaptiveAnswer(Request $request, KcatAttempt $attempt): RedirectResponse
    {
        abort_unless((int) $attempt->student_id === (int) $this->studentForUser($request)->id, 403);
        abort_unless($attempt->is_adaptive, 404);

        $validated = $request->validate([
            'selected_option_id' => ['nullable', 'exists:kcat_question_options,id'],
            'answer_text' => ['nullable', 'string'],
            'response_time_seconds' => ['nullable', 'integer', 'min:0', 'max:3600'],
        ]);

        $result = $this->attemptService->saveAdaptiveAnswer($attempt, $validated);
        if (($result['completed'] ?? false) === true) {
            return redirect()->route('student.kcat.attempts.result', $attempt);
        }

        return redirect()->route('student.kcat.attempts.adaptive.next', $attempt);
    }

    private function studentForUser(Request $request): Student
    {
        $student = $this->studentResolver->resolveStudentForUser($request->user());
        abort_unless($student, 403);

        return $student;
    }
}
