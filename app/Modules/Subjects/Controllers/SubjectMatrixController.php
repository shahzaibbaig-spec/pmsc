<?php

namespace App\Modules\Subjects\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Modules\Subjects\Requests\BulkAssignSubjectMatrixRequest;
use App\Modules\Subjects\Requests\SubjectMatrixQueryRequest;
use App\Modules\Subjects\Requests\ToggleSubjectMatrixRequest;
use App\Modules\Subjects\Services\SubjectMatrixService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use RuntimeException;

class SubjectMatrixController extends Controller
{
    public function __construct(private readonly SubjectMatrixService $service)
    {
    }

    public function index(): View
    {
        $classes = SchoolClass::query()
            ->withCount('students')
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        $defaultClass = $classes->firstWhere('students_count', '>', 0) ?? $classes->first();

        $sessions = $this->service->sessionOptions();
        $defaultSession = $sessions[1] ?? ($sessions[0] ?? now()->year.'-'.(now()->year + 1));

        return view('modules.principal.subjects.matrix', [
            'classes' => $classes,
            'sessions' => $sessions,
            'defaultSession' => $defaultSession,
            'defaultClassId' => $defaultClass?->id,
        ]);
    }

    public function data(SubjectMatrixQueryRequest $request): JsonResponse
    {
        $payload = $this->service->matrix(
            (int) $request->input('class_id'),
            $request->string('session')->toString()
        );

        return response()->json($payload);
    }

    public function toggle(ToggleSubjectMatrixRequest $request): JsonResponse
    {
        try {
            $this->service->toggle(
                (int) $request->input('class_id'),
                (int) $request->input('student_id'),
                (int) $request->input('subject_id'),
                $request->string('session')->toString(),
                (bool) $request->boolean('assigned')
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'Assignment updated.']);
    }

    public function bulkAssign(BulkAssignSubjectMatrixRequest $request): JsonResponse
    {
        $affected = $this->service->bulkAssign(
            (int) $request->input('class_id'),
            (int) $request->input('subject_id'),
            $request->string('session')->toString(),
            (bool) $request->boolean('assigned')
        );

        return response()->json([
            'message' => 'Bulk assignment updated.',
            'affected' => $affected,
        ]);
    }
}
