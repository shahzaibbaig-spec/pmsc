<?php

namespace App\Modules\Analytics\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Modules\Analytics\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrincipalAnalyticsDashboardController extends Controller
{
    public function __construct(private readonly AnalyticsService $analyticsService)
    {
    }

    public function index(Request $request): View
    {
        $sessions = $this->analyticsService->sessionOptions();
        $defaultSession = $sessions[1] ?? ($sessions[0] ?? now()->year.'-'.(now()->year + 1));

        $validated = $request->validate([
            'session' => ['nullable', 'regex:/^\d{4}-\d{4}$/'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
        ]);

        $selectedSession = $this->normalizeSession(
            isset($validated['session']) ? (string) $validated['session'] : null,
            $defaultSession
        );
        $selectedClassId = isset($validated['class_id']) ? (int) $validated['class_id'] : null;

        return view('modules.principal.analytics.dashboard', [
            'sessions' => $sessions,
            'classes' => $this->analyticsService->classOptions(),
            'selectedSession' => $selectedSession,
            'selectedClassId' => $selectedClassId,
            'dashboard' => $this->analyticsService->dashboard($selectedSession, $selectedClassId),
        ]);
    }

    public function classDrilldown(Request $request, SchoolClass $schoolClass): View
    {
        $sessions = $this->analyticsService->sessionOptions();
        $defaultSession = $sessions[1] ?? ($sessions[0] ?? now()->year.'-'.(now()->year + 1));

        $validated = $request->validate([
            'session' => ['nullable', 'regex:/^\d{4}-\d{4}$/'],
        ]);

        $selectedSession = $this->normalizeSession(
            isset($validated['session']) ? (string) $validated['session'] : null,
            $defaultSession
        );

        return view('modules.principal.analytics.class-drilldown', [
            'sessions' => $sessions,
            'selectedSession' => $selectedSession,
            'payload' => $this->analyticsService->classDrilldown((int) $schoolClass->id, $selectedSession),
        ]);
    }

    public function teacherDrilldown(Request $request, Teacher $teacher): View
    {
        $sessions = $this->analyticsService->sessionOptions();
        $defaultSession = $sessions[1] ?? ($sessions[0] ?? now()->year.'-'.(now()->year + 1));

        $validated = $request->validate([
            'session' => ['nullable', 'regex:/^\d{4}-\d{4}$/'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
        ]);

        $selectedSession = $this->normalizeSession(
            isset($validated['session']) ? (string) $validated['session'] : null,
            $defaultSession
        );
        $selectedClassId = isset($validated['class_id']) ? (int) $validated['class_id'] : null;

        return view('modules.principal.analytics.teacher-drilldown', [
            'sessions' => $sessions,
            'classes' => $this->analyticsService->classOptions(),
            'selectedSession' => $selectedSession,
            'selectedClassId' => $selectedClassId,
            'payload' => $this->analyticsService->teacherDrilldown(
                (int) $teacher->id,
                $selectedSession,
                $selectedClassId
            ),
        ]);
    }

    private function normalizeSession(?string $session, string $fallback): string
    {
        $candidate = trim((string) $session);
        if ($candidate === '') {
            return $fallback;
        }

        if (! preg_match('/^(\d{4})-(\d{4})$/', $candidate, $matches)) {
            return $fallback;
        }

        if ((int) $matches[2] !== ((int) $matches[1] + 1)) {
            return $fallback;
        }

        return $candidate;
    }
}

