<?php

namespace App\Modules\Search\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Search\Requests\StudentSearchRequest;
use App\Modules\Search\Services\StudentSearchService;
use Illuminate\Http\JsonResponse;

class StudentSearchController extends Controller
{
    public function __construct(private readonly StudentSearchService $service)
    {
    }

    public function students(StudentSearchRequest $request): JsonResponse
    {
        $results = $this->service->search(
            (string) $request->input('q', ''),
            10
        );

        return response()->json([
            'data' => $results,
        ]);
    }
}

