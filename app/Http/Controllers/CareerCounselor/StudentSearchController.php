<?php

namespace App\Http\Controllers\CareerCounselor;

use App\Http\Controllers\Controller;
use App\Services\CareerCounselorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentSearchController extends Controller
{
    public function __construct(private readonly CareerCounselorService $careerCounselorService)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'term' => ['nullable', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return response()->json([
            'data' => $this->careerCounselorService->searchStudents($validated['term'] ?? $validated['q'] ?? ''),
        ]);
    }
}
