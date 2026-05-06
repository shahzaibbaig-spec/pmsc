<?php

namespace App\Modules\Results\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\FeeBlockOverride;
use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Services\AssessmentMarkingModeService;
use App\Modules\Exams\Enums\ExamType;
use App\Modules\Results\Requests\ConfigureAssessmentMarkingModeRequest;
use App\Modules\Results\Requests\PublishResultsRequest;
use App\Modules\Results\Requests\StudentResultPreviewRequest;
use App\Modules\Fees\Services\FeeDefaulterService;
use App\Modules\Results\Services\ResultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class PrincipalResultController extends Controller
{
    public function __construct(
        private readonly ResultService $resultService,
        private readonly FeeDefaulterService $feeDefaulterService,
        private readonly AssessmentMarkingModeService $markingModeService,
    )
    {
    }

    public function index(): View
    {
        return $this->generator(request());
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
        $markingModeContext = null;
        if ($defaultClassId !== null) {
            try {
                $markingModeContext = $this->markingModeService->classExamModeContext(
                    (int) $defaultClassId,
                    $session,
                    $examType
                );
            } catch (RuntimeException) {
                $markingModeContext = null;
            }
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
            'markingModeContext' => $markingModeContext,
        ]);
    }

    public function students(Request $request): JsonResponse
    {
        $request->validate([
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'session' => ['required', 'string', 'max:20'],
        ]);

        $studentIds = StudentClassHistory::query()
            ->where('class_id', (int) $request->input('class_id'))
            ->where('session', (string) $request->input('session'))
            ->pluck('student_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $students = ($studentIds->isNotEmpty()
            ? Student::query()->whereIn('id', $studentIds)
            : Student::query()->where('class_id', (int) $request->input('class_id'))->where('status', 'active'))
            ->orderBy('name')
            ->orderBy('student_id')
            ->get(['id', 'student_id', 'name']);

        return response()->json([
            'students' => $students,
        ]);
    }

    public function examScopes(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'session' => ['required', 'string', 'max:20'],
            'exam_type' => ['required', 'string', 'in:'.implode(',', array_column(ExamType::options(), 'value'))],
        ]);

        $labels = Exam::query()
            ->where('class_id', (int) $validated['class_id'])
            ->where('session', (string) $validated['session'])
            ->where('exam_type', (string) $validated['exam_type'])
            ->select('exam_label')
            ->whereNotNull('exam_label')
            ->distinct()
            ->orderBy('exam_label')
            ->get()
            ->map(function (Exam $exam): array {
                $label = trim((string) $exam->exam_label);

                return [
                    'value' => $label,
                    'label' => $label,
                ];
            })
            ->filter(fn (array $row): bool => $row['value'] !== '')
            ->values()
            ->all();

        return response()->json([
            'scopes' => $labels,
        ]);
    }

    public function preview(StudentResultPreviewRequest $request): JsonResponse
    {
        try {
            $payload = $this->resultService->generateStudentResult(
                (int) $request->input('student_id'),
                $request->string('session')->toString(),
                $request->string('exam_type')->toString(),
                $request->filled('exam_label') ? $request->string('exam_label')->toString() : null,
            );
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], 422);
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
                $request->filled('exam_label') ? $request->string('exam_label')->toString() : null,
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
                $request->filled('exam_label') ? $request->string('exam_label')->toString() : null,
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Results published notifications sent.',
            'summary' => $payload,
        ]);
    }

    public function markingModeContext(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'session' => ['required', 'string', 'max:20'],
            'exam_type' => ['required', 'string', 'in:'.implode(',', array_column(ExamType::options(), 'value'))],
        ]);

        try {
            $context = $this->markingModeService->classExamModeContext(
                (int) $validated['class_id'],
                (string) $validated['session'],
                (string) $validated['exam_type']
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($context);
    }

    public function updateMarkingMode(ConfigureAssessmentMarkingModeRequest $request): JsonResponse
    {
        try {
            $result = $this->markingModeService->configureClassExamMarkingMode(
                (int) $request->input('class_id'),
                $request->string('session')->toString(),
                $request->string('exam_type')->toString(),
                $request->string('marking_mode')->toString()
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Assessment marking mode updated successfully for the selected class and exam scope.',
            'summary' => $result,
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
