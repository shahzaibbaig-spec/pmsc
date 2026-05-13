<?php

namespace App\Http\Controllers\SportsTeacher;

use App\Http\Controllers\Controller;
use App\Models\StudentSportsObservation;
use App\Services\SportsObservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentSearchController extends Controller
{
    public function __construct(private readonly SportsObservationService $sportsObservationService)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'term' => ['nullable', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:100'],
            'session' => ['nullable', 'string', 'max:20'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
        ]);

        return response()->json([
            'data' => $this->sportsObservationService->searchStudents(
                (string) ($validated['term'] ?? $validated['q'] ?? ''),
                [
                    'session' => $validated['session'] ?? null,
                    'class_id' => $validated['class_id'] ?? null,
                ]
            ),
            'issue_options' => StudentSportsObservation::ISSUE_LABELS,
        ]);
    }
}
