<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\TeacherEResourceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TeacherEResourceController extends Controller
{
    public function index(Request $request, TeacherEResourceService $service): View
    {
        $validated = $request->validate([
            'session' => ['nullable', 'string', 'max:20'],
        ]);

        $payload = $service->buildTeacherResourcesPayload(
            (int) auth()->id(),
            (string) ($validated['session'] ?? '')
        );

        return view('modules.teacher.e-resources.index', [
            'teacher' => $payload['teacher'],
            'sessions' => $payload['sessions'],
            'selectedSession' => $payload['selected_session'],
            'classResources' => $payload['class_resources'],
            'generalResources' => $payload['general_resources'],
        ]);
    }

    public function file(Request $request, TeacherEResourceService $service): BinaryFileResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'mode' => ['nullable', 'string', 'in:inline,download'],
        ]);

        $absolutePath = $service->resolveAbsolutePathForToken((string) $validated['token']);
        if (! is_string($absolutePath)) {
            abort(404, 'Resource file not found.');
        }

        $mode = (string) ($validated['mode'] ?? 'inline');
        $downloadName = basename($absolutePath);

        if ($mode === 'download') {
            return response()->download($absolutePath, $downloadName);
        }

        return response()->file($absolutePath, [
            'Content-Disposition' => 'inline; filename="'.$downloadName.'"',
        ]);
    }
}

