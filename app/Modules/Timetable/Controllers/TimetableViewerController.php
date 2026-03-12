<?php

namespace App\Modules\Timetable\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\Teacher;
use App\Modules\Timetable\Requests\ClassTimetableApiRequest;
use App\Modules\Timetable\Requests\TeacherTimetableApiRequest;
use App\Modules\Timetable\Services\TimetableViewerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class TimetableViewerController extends Controller
{
    public function principalViewer(): View
    {
        $sessions = $this->sessionOptions();
        $classSections = ClassSection::query()
            ->with('classRoom:id,name,section')
            ->orderBy('class_id')
            ->orderBy('section_name')
            ->get(['id', 'class_id', 'section_name'])
            ->map(function (ClassSection $section): array {
                $classLabel = trim(($section->classRoom?->name ?? '').' '.($section->classRoom?->section ?? ''));

                return [
                    'id' => (int) $section->id,
                    'display_name' => trim($classLabel.' - '.$section->section_name),
                ];
            })
            ->values();

        return view('modules.principal.timetable.viewer', [
            'sessions' => $sessions,
            'defaultSession' => $sessions[1] ?? ($sessions[0] ?? now()->year.'-'.(now()->year + 1)),
            'classSections' => $classSections,
        ]);
    }

    public function teacherViewer(Request $request): View
    {
        $teacher = Teacher::query()
            ->where('user_id', (int) $request->user()->id)
            ->first(['id']);

        abort_unless($teacher, 403, 'Teacher profile not found.');

        $sessions = $this->sessionOptions();

        return view('modules.teacher.timetable.viewer', [
            'sessions' => $sessions,
            'defaultSession' => $sessions[1] ?? ($sessions[0] ?? now()->year.'-'.(now()->year + 1)),
            'teacherId' => (int) $teacher->id,
        ]);
    }

    public function classApi(
        ClassTimetableApiRequest $request,
        TimetableViewerService $viewerService
    ): JsonResponse {
        try {
            $payload = $viewerService->classTimetable(
                (string) $request->input('session'),
                (int) $request->input('class_section_id')
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($payload);
    }

    public function teacherApi(
        TeacherTimetableApiRequest $request,
        TimetableViewerService $viewerService
    ): JsonResponse {
        $session = (string) $request->input('session');

        if ($request->user()?->hasRole('Teacher')) {
            $teacherId = Teacher::query()
                ->where('user_id', (int) $request->user()->id)
                ->value('id');

            if (! $teacherId) {
                return response()->json(['message' => 'Teacher profile not found.'], 422);
            }
        } else {
            $teacherId = (int) $request->input('teacher_id');
            if (! $teacherId) {
                return response()->json(['message' => 'Teacher is required.'], 422);
            }
        }

        try {
            $payload = $viewerService->teacherTimetable($session, (int) $teacherId);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($payload);
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
