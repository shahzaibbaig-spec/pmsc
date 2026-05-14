<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\StudentDisciplineReport;
use App\Services\StudentDisciplineReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DisciplineStudentSearchController extends Controller
{
    public function __construct(private readonly StudentDisciplineReportService $disciplineReportService)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        return $this->search($request);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'term' => ['nullable', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:100'],
            'session' => ['nullable', 'string', 'max:20'],
        ]);

        $results = $this->disciplineReportService->searchStudentsForTeacher(
            $request->user(),
            (string) ($validated['term'] ?? $validated['q'] ?? ''),
            [
                'session' => $validated['session'] ?? null,
            ]
        );

        return response()->json([
            'data' => $results,
            'results' => $results,
            'issue_options' => StudentDisciplineReport::ISSUE_LABELS,
            'severity_options' => StudentDisciplineReport::SEVERITIES,
        ]);
    }
}
