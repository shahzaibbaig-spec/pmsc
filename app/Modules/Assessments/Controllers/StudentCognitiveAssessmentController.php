<?php

namespace App\Modules\Assessments\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CognitiveAssessment;
use App\Models\CognitiveAssessmentAttempt;
use App\Models\Student;
use App\Modules\Assessments\Requests\SaveCognitiveAssessmentResponsesRequest;
use App\Services\CognitiveAssessmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use RuntimeException;

class StudentCognitiveAssessmentController extends Controller
{
    public function __construct(private readonly CognitiveAssessmentService $service)
    {
    }

    public function index(): View
    {
        $student = $this->currentStudent();

        if (! $student) {
            return view('student.assessments.index', [
                'student' => null,
                'assessment' => null,
                'attempt' => null,
                'visible' => false,
                'message' => 'Student profile is not linked to this login yet. Please ask Admin to align student name or email with a student record.',
            ]);
        }

        if (! $this->moduleReady()) {
            return view('student.assessments.index', [
                'student' => $student,
                'assessment' => null,
                'attempt' => null,
                'visible' => false,
                'message' => 'Cognitive assessment module is not available yet.',
            ]);
        }

        $payload = $this->assessmentPagePayload($student);

        return view('student.assessments.index', [
            'student' => $student,
            'assessment' => $payload['assessment'],
            'attempt' => $payload['attempt'],
            'visible' => $payload['visible'],
            'message' => $payload['message'],
        ]);
    }

    public function showLevelFour(): View
    {
        $student = $this->currentStudent();

        if (! $student) {
            return view('student.assessments.cognitive-skills-level-4.index', [
                'student' => null,
                'assessment' => null,
                'attempt' => null,
                'visible' => false,
                'message' => 'Student profile is not linked to this login yet. Please ask Admin to align student name or email with a student record.',
            ]);
        }

        if (! $this->moduleReady()) {
            return view('student.assessments.cognitive-skills-level-4.index', [
                'student' => $student,
                'assessment' => null,
                'attempt' => null,
                'visible' => false,
                'message' => 'Cognitive assessment module is not available yet.',
            ]);
        }

        $payload = $this->assessmentPagePayload($student);
        if (! $payload['visible']) {
            abort(403, $payload['message'] ?? 'You are not authorized to access Cognitive Skills Assessment Test Level 4.');
        }

        return view('student.assessments.cognitive-skills-level-4.index', [
            'student' => $student,
            'assessment' => $payload['assessment'],
            'attempt' => $payload['attempt'],
            'visible' => $payload['visible'],
            'message' => $payload['message'],
        ]);
    }

    public function start(): RedirectResponse
    {
        $student = $this->currentStudent();

        if (! $student) {
            return redirect()
                ->route('student.assessments.cognitive-skills-level-4.index')
                ->withErrors(['assessment' => 'Student profile is not linked to this login.']);
        }

        try {
            $attempt = $this->service->startAttempt($student);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('student.assessments.cognitive-skills-level-4.index')
                ->withErrors(['assessment' => $exception->getMessage()]);
        }

        if ($attempt->status === CognitiveAssessmentAttempt::STATUS_GRADED) {
            return redirect()
                ->route('student.assessments.cognitive-skills-level-4.result', $attempt)
                ->with('status', 'You have already completed Cognitive Skills Assessment Test Level 4.');
        }

        return redirect()->route('student.assessments.cognitive-skills-level-4.attempt', $attempt);
    }

    public function attempt(CognitiveAssessmentAttempt $attempt): View|RedirectResponse
    {
        $student = $this->authorizeStudentAttempt($attempt);
        $attempt = $attempt->fresh(['student.classRoom']) ?? $attempt;

        if ($attempt->status === CognitiveAssessmentAttempt::STATUS_IN_PROGRESS && $attempt->isExpired()) {
            $attempt = $this->service->submitAttempt($attempt, true);

            return redirect()
                ->route('student.assessments.cognitive-skills-level-4.result', $attempt)
                ->with('status', 'Time expired, so your assessment was auto-submitted.');
        }

        if ($attempt->status !== CognitiveAssessmentAttempt::STATUS_IN_PROGRESS) {
            return redirect()->route('student.assessments.cognitive-skills-level-4.result', $attempt);
        }

        $assessment = $this->service->resolveAssessment();
        if ((int) $attempt->assessment_id !== (int) $assessment->id) {
            abort(404);
        }

        $remainingSeconds = max(0, now()->diffInSeconds($attempt->expires_at, false));
        $attemptView = $this->service->buildAttemptViewData($attempt);

        return view('student.assessments.cognitive-skills-level-4.attempt', [
            'student' => $student,
            'assessment' => $assessment,
            'attempt' => $attempt,
            'attemptView' => $attemptView,
            'remainingSeconds' => $remainingSeconds,
        ]);
    }

    public function saveResponses(
        SaveCognitiveAssessmentResponsesRequest $request,
        CognitiveAssessmentAttempt $attempt
    ): JsonResponse {
        $this->authorizeStudentAttempt($attempt);

        try {
            $attempt = $this->service->saveResponses(
                $attempt,
                $request->validated('responses', [])
            );
        } catch (RuntimeException $exception) {
            $freshAttempt = $attempt->fresh();
            $statusCode = $freshAttempt?->status === CognitiveAssessmentAttempt::STATUS_GRADED ? 409 : 422;

            return response()->json([
                'message' => $exception->getMessage(),
                'redirect_url' => $freshAttempt && $freshAttempt->status === CognitiveAssessmentAttempt::STATUS_GRADED
                    ? route('student.assessments.cognitive-skills-level-4.result', $freshAttempt)
                    : null,
            ], $statusCode);
        }

        return response()->json([
            'message' => 'Responses saved.',
            'saved_at' => now()->format('H:i:s'),
            'remaining_seconds' => max(0, now()->diffInSeconds($attempt->expires_at, false)),
        ]);
    }

    public function submit(SaveCognitiveAssessmentResponsesRequest $request, CognitiveAssessmentAttempt $attempt): RedirectResponse
    {
        $this->authorizeStudentAttempt($attempt);

        try {
            $responses = $request->validated('responses', []);
            if ($responses !== []) {
                $attempt = $this->service->saveResponses($attempt, $responses);
            }

            $attempt = $attempt->fresh() ?? $attempt;
            if ($attempt->status === CognitiveAssessmentAttempt::STATUS_IN_PROGRESS) {
                $attempt = $this->service->submitAttempt($attempt);
            }
        } catch (RuntimeException $exception) {
            $freshAttempt = $attempt->fresh();
            if ($freshAttempt?->status === CognitiveAssessmentAttempt::STATUS_GRADED) {
                return redirect()
                    ->route('student.assessments.cognitive-skills-level-4.result', $freshAttempt)
                    ->with('status', $exception->getMessage());
            }

            return redirect()
                ->route('student.assessments.cognitive-skills-level-4.attempt', $attempt)
                ->withErrors(['assessment' => $exception->getMessage()]);
        }

        return redirect()
            ->route('student.assessments.cognitive-skills-level-4.result', $attempt)
            ->with('status', 'Cognitive Skills Assessment Test Level 4 submitted successfully.');
    }

    public function result(CognitiveAssessmentAttempt $attempt): View|RedirectResponse
    {
        $student = $this->authorizeStudentAttempt($attempt);
        $attempt = $attempt->fresh(['student.classRoom']) ?? $attempt;

        if ($attempt->status === CognitiveAssessmentAttempt::STATUS_IN_PROGRESS && ! $attempt->isExpired()) {
            return redirect()->route('student.assessments.cognitive-skills-level-4.attempt', $attempt);
        }

        if ($attempt->status === CognitiveAssessmentAttempt::STATUS_IN_PROGRESS && $attempt->isExpired()) {
            $attempt = $this->service->submitAttempt($attempt, true);
        }

        $result = $this->service->buildStudentResult($attempt);

        return view('student.assessments.cognitive-skills-level-4.result', [
            'student' => $student,
            'assessment' => $attempt->assessment,
            'attempt' => $attempt,
            'result' => $result,
        ]);
    }

    private function currentStudent(): ?Student
    {
        $user = auth()->user();

        return $user ? $this->service->resolveStudentForUser($user) : null;
    }

    private function authorizeStudentAttempt(CognitiveAssessmentAttempt $attempt): Student
    {
        $user = auth()->user();
        $student = $this->currentStudent();

        if (
            ! $user
            || ! $student
            || ! $this->service->studentCanAccessAssessment($student)
            || $attempt->status === CognitiveAssessmentAttempt::STATUS_RESET
        ) {
            abort(403, 'You are not authorized to access Cognitive Skills Assessment Test Level 4.');
        }

        if (! $this->service->attemptBelongsToStudent($user, $attempt)) {
            abort(404);
        }

        return $student;
    }

    /**
     * @return array{assessment:CognitiveAssessment|null,attempt:CognitiveAssessmentAttempt|null,visible:bool,message:?string}
     */
    private function assessmentPagePayload(Student $student): array
    {
        try {
            $assessment = $this->service->resolveAssessment();
        } catch (RuntimeException $exception) {
            return [
                'assessment' => null,
                'attempt' => null,
                'visible' => false,
                'message' => $exception->getMessage(),
            ];
        }

        /** @var CognitiveAssessmentAttempt|null $attempt */
        $attempt = CognitiveAssessmentAttempt::query()
            ->with('student.classRoom')
            ->where('assessment_id', $assessment->id)
            ->where('student_id', $student->id)
            ->orderByDesc('id')
            ->first();

        if ($attempt && $attempt->status === CognitiveAssessmentAttempt::STATUS_IN_PROGRESS && $attempt->isExpired()) {
            $attempt = $this->service->submitAttempt($attempt, true);
        }

        $accessState = $this->service->studentAssessmentAccessState($student, $assessment);
        $message = $accessState['message'];

        if ($attempt?->status === CognitiveAssessmentAttempt::STATUS_RESET && $accessState['visible']) {
            $message = 'Your previous attempt was reset by the Principal. You can start a fresh attempt now.';
        }

        return [
            'assessment' => $assessment,
            'attempt' => $attempt,
            'visible' => $accessState['visible'],
            'message' => $message,
        ];
    }

    private function moduleReady(): bool
    {
        return Schema::hasTable('cognitive_assessments')
            && Schema::hasTable('cognitive_assessment_attempts')
            && Schema::hasTable('cognitive_assessment_questions')
            && Schema::hasTable('cognitive_assessment_student_assignments');
    }
}
