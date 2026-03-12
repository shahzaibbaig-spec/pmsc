<?php

namespace App\Modules\Analytics\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\StudentPerformanceFeature;
use App\Models\StudentRiskPrediction;
use App\Modules\Analytics\Requests\GeneratePredictionRequest;
use App\Modules\Analytics\Requests\PerformanceInsightDataRequest;
use App\Modules\Analytics\Services\FeatureBuilderService;
use App\Modules\Analytics\Services\PredictionService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PerformanceInsightsController extends Controller
{
    public function __construct(
        private readonly FeatureBuilderService $featureBuilderService,
        private readonly PredictionService $predictionService,
    ) {
    }

    public function index(): View
    {
        $classes = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        $sessions = $this->sessionOptions();

        return view('modules.principal.analytics.performance-insights', [
            'classes' => $classes,
            'sessions' => $sessions,
            'defaultSession' => $sessions[1] ?? ($sessions[0] ?? now()->year.'-'.(now()->year + 1)),
        ]);
    }

    public function data(PerformanceInsightDataRequest $request): JsonResponse
    {
        $session = (string) $request->input('session');
        $classId = $request->filled('class_id') ? (int) $request->input('class_id') : null;
        $targetExam = (string) $request->input('target_exam', 'final_term');
        $search = trim((string) $request->input('search', ''));
        $perPage = (int) $request->input('per_page', 20);

        $query = StudentPerformanceFeature::query()
            ->with([
                'student:id,student_id,name,father_name,class_id,status',
                'student.classRoom:id,name,section',
            ])
            ->where('session', $session)
            ->when($classId !== null, fn ($q) => $q->whereHas('student', fn ($sq) => $sq->where('class_id', $classId)))
            ->when($search !== '', function ($q) use ($search): void {
                $q->whereHas('student', function ($sq) use ($search): void {
                    $sq->where('name', 'like', '%'.$search.'%')
                        ->orWhere('student_id', 'like', $search.'%')
                        ->orWhere('father_name', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('attendance_rate')
            ->orderBy('student_id');

        $paginator = $query->paginate($perPage);
        $features = collect($paginator->items());
        $studentIds = $features->pluck('student_id')->filter()->unique()->values();

        $riskMap = StudentRiskPrediction::query()
            ->where('session', $session)
            ->where('target_exam', $targetExam)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        return response()->json([
            'data' => $features->map(function (StudentPerformanceFeature $feature) use ($riskMap): array {
                $student = $feature->student;
                $risk = $student ? $riskMap->get($student->id) : null;

                $avgScore = $this->simpleAverage([
                    $feature->avg_class_test,
                    $feature->avg_bimonthly,
                    $feature->avg_first_term,
                ]);

                return [
                    'feature_id' => (int) $feature->id,
                    'student_id' => $student?->student_id,
                    'student_name' => $student?->name ?? 'Student',
                    'class_name' => trim(($student?->classRoom?->name ?? '').' '.($student?->classRoom?->section ?? '')),
                    'attendance_rate' => round((float) $feature->attendance_rate, 2),
                    'avg_score' => $avgScore !== null ? round($avgScore, 2) : null,
                    'avg_class_test' => $feature->avg_class_test !== null ? round((float) $feature->avg_class_test, 2) : null,
                    'avg_bimonthly' => $feature->avg_bimonthly !== null ? round((float) $feature->avg_bimonthly, 2) : null,
                    'avg_first_term' => $feature->avg_first_term !== null ? round((float) $feature->avg_first_term, 2) : null,
                    'trend_slope' => round((float) $feature->trend_slope, 4),
                    'last_assessment_score' => $feature->last_assessment_score !== null ? round((float) $feature->last_assessment_score, 2) : null,
                    'predicted_percentage' => $risk ? round((float) $risk->predicted_percentage, 2) : null,
                    'risk_level' => $risk?->risk_level,
                    'confidence' => $risk ? round((float) $risk->confidence, 2) : null,
                    'explanation' => $risk?->explanation,
                ];
            })->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
            ],
        ]);
    }

    public function predict(GeneratePredictionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $session = (string) $data['session'];
        $targetExam = (string) $data['target_exam'];
        $classId = isset($data['class_id']) ? (int) $data['class_id'] : null;

        $featureBuildResult = $this->featureBuilderService->buildForSession($session);
        $predictionSummary = $this->predictionService->generate(
            $session,
            $targetExam,
            $classId
        );

        return response()->json([
            'message' => 'Predictions generated successfully.',
            'features' => $featureBuildResult,
            'summary' => $predictionSummary,
        ]);
    }

    private function simpleAverage(array $values): ?float
    {
        $valid = array_values(array_filter($values, fn ($value): bool => $value !== null));
        if (empty($valid)) {
            return null;
        }

        return array_sum($valid) / count($valid);
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
