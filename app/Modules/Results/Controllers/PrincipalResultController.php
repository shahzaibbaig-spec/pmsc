<?php

namespace App\Modules\Results\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FeeBlockOverride;
use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Modules\Exams\Enums\ExamType;
use App\Modules\Results\Requests\PublishResultsRequest;
use App\Modules\Results\Requests\StudentResultPreviewRequest;
use App\Modules\Fees\Services\FeeDefaulterService;
use App\Modules\Results\Services\ResultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class PrincipalResultController extends Controller
{
    public function __construct(
        private readonly ResultService $resultService,
        private readonly FeeDefaulterService $feeDefaulterService,
    )
    {
    }

    public function index(): View
    {
        $classes = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        $sessions = $this->sessionOptions();

        return view('modules.principal.results.index', [
            'classes' => $classes,
            'sessions' => $sessions,
            'defaultSession' => $sessions[1] ?? ($sessions[0] ?? now()->year.'-'.(now()->year + 1)),
            'examTypes' => ExamType::options(),
            'hasMarks' => Mark::query()->exists(),
        ]);
    }

    public function generator(Request $request): View
    {
        $classes = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        $sessions = $this->sessionOptions();
        $defaultSession = $sessions[1] ?? ($sessions[0] ?? now()->year.'-'.(now()->year + 1));
        $session = $request->filled('session') && in_array($request->string('session')->toString(), $sessions, true)
            ? $request->string('session')->toString()
            : $defaultSession;

        $examTypeValues = array_column(ExamType::options(), 'value');
        $defaultExamType = in_array('final_term', $examTypeValues, true)
            ? 'final_term'
            : ($examTypeValues[0] ?? 'first_term');
        $examType = $request->filled('exam_type') && in_array($request->string('exam_type')->toString(), $examTypeValues, true)
            ? $request->string('exam_type')->toString()
            : $defaultExamType;

        $defaultClassId = $request->filled('class_id') ? (int) $request->input('class_id') : null;
        if ($defaultClassId !== null && ! $classes->contains('id', $defaultClassId)) {
            $defaultClassId = null;
        }

        return view('modules.principal.results.generator', [
            'classes' => $classes,
            'sessions' => $sessions,
            'examTypes' => ExamType::options(),
            'defaultSession' => $session,
            'defaultExamType' => $examType,
            'defaultClassId' => $defaultClassId,
            'defaultStudentId' => $request->filled('student_id') ? (int) $request->input('student_id') : null,
            'hasMarks' => Mark::query()->exists(),
        ]);
    }

    public function students(Request $request): JsonResponse
    {
        $request->validate([
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
        ]);

        $students = Student::query()
            ->where('class_id', (int) $request->input('class_id'))
            ->where('status', 'active')
            ->orderBy('name')
            ->orderBy('student_id')
            ->get(['id', 'student_id', 'name']);

        return response()->json([
            'students' => $students,
        ]);
    }

    public function preview(StudentResultPreviewRequest $request): JsonResponse
    {
        try {
            $payload = $this->resultService->generateStudentResult(
                (int) $request->input('student_id'),
                $request->string('session')->toString(),
                $request->string('exam_type')->toString(),
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($payload);
    }

    public function card(StudentResultPreviewRequest $request): View|Response
    {
        $studentId = (int) $request->input('student_id');
        $session = $request->string('session')->toString();

        try {
            if ($this->feeDefaulterService->isStudentBlocked(FeeBlockOverride::TYPE_RESULT_CARD, $studentId, $session)) {
                $breakdown = $this->feeDefaulterService->dueBreakdownForStudent($studentId, $session);
                $totalDue = round((float) ($breakdown['total_due'] ?? 0), 2);

                throw new RuntimeException(sprintf(
                    'Official result card is blocked for this student due to unpaid dues (PKR %s).',
                    number_format($totalDue, 2)
                ));
            }

            $payload = $this->resultService->generateStudentResult(
                $studentId,
                $session,
                $request->string('exam_type')->toString(),
            );
        } catch (RuntimeException $exception) {
            return response($exception->getMessage(), 422);
        }

        return view('modules.reports.result-card', [
            'result' => $payload,
        ]);
    }

    public function publish(PublishResultsRequest $request): JsonResponse
    {
        try {
            $payload = $this->resultService->publishResults(
                (int) auth()->id(),
                (int) $request->input('class_id'),
                $request->string('session')->toString(),
                $request->string('exam_type')->toString(),
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Results published notifications sent.',
            'summary' => $payload,
        ]);
    }

    private function sessionOptions(int $backward = 1, int $forward = 3): array
    {
        $now = now();
        $currentStartYear = $now->month >= 7 ? $now->year : ($now->year - 1);

        $sessions = [];
        for ($year = $currentStartYear - $backward; $year <= $currentStartYear + $forward; $year++) {
            $sessions[] = $year.'-'.($year + 1);
        }

        return $sessions;
    }
}
