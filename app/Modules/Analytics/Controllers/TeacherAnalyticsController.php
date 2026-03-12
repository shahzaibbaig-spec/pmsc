<?php

namespace App\Modules\Analytics\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Modules\Analytics\Requests\TeacherAnalyticsDataRequest;
use App\Modules\Analytics\Requests\TeacherAnalyticsDetailRequest;
use App\Modules\Analytics\Services\TeacherAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use RuntimeException;

class TeacherAnalyticsController extends Controller
{
    public function __construct(private readonly TeacherAnalyticsService $service)
    {
    }

    public function index(): View
    {
        $sessions = $this->service->sessionOptions();
        $defaultSession = $sessions[1] ?? ($sessions[0] ?? now()->year.'-'.(now()->year + 1));
        $filters = $this->service->filters();

        return view('modules.principal.analytics.teachers', [
            'sessions' => $sessions,
            'defaultSession' => $defaultSession,
            'teachers' => $filters['teachers'],
            'classes' => $filters['classes'],
            'subjects' => $filters['subjects'],
        ]);
    }

    public function data(TeacherAnalyticsDataRequest $request): JsonResponse
    {
        try {
            $payload = $this->service->tableData(
                $request->string('session')->toString(),
                $request->filled('teacher_id') ? (int) $request->input('teacher_id') : null,
                $request->filled('class_id') ? (int) $request->input('class_id') : null,
                $request->filled('subject_id') ? (int) $request->input('subject_id') : null,
                $request->filled('search') ? $request->string('search')->toString() : null,
                (int) $request->input('page', 1),
                (int) $request->input('per_page', 15),
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($payload);
    }

    public function detail(TeacherAnalyticsDetailRequest $request, Teacher $teacher): JsonResponse
    {
        try {
            $payload = $this->service->teacherDetail(
                (int) $teacher->id,
                $request->string('session')->toString(),
                $request->filled('class_id') ? (int) $request->input('class_id') : null,
                $request->filled('subject_id') ? (int) $request->input('subject_id') : null,
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($payload);
    }
}

