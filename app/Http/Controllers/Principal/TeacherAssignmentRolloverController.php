<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Principal\TeacherAssignmentRolloverRequest;
use App\Models\Teacher;
use App\Services\TeacherAssignmentRolloverService;
use App\Services\TeacherAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class TeacherAssignmentRolloverController extends Controller
{
    public function index(Request $request, TeacherAssignmentService $assignmentService): View
    {
        $defaultFromSession = $this->academicSessionForDate(now()->toDateString());
        $defaultToSession = $this->nextSession($defaultFromSession);

        return view('principal.teacher-assignments.rollover', [
            'sessions' => $this->sessions($assignmentService),
            'teachers' => $this->teachers(),
            'defaultFromSession' => (string) $request->old('from_session', $defaultFromSession),
            'defaultToSession' => (string) $request->old('to_session', $defaultToSession),
            'selectedTeacherIds' => collect($request->old('teacher_ids', []))->map(fn ($id) => (int) $id)->all(),
            'overwrite' => (bool) $request->old('overwrite', false),
            'preview' => null,
        ]);
    }

    public function preview(
        TeacherAssignmentRolloverRequest $request,
        TeacherAssignmentRolloverService $rolloverService,
        TeacherAssignmentService $assignmentService
    ): View {
        $payload = $request->validated();

        $preview = $rolloverService->previewRollover(
            (string) $payload['from_session'],
            (string) $payload['to_session'],
            $payload['teacher_ids'] ?? null
        );

        return view('principal.teacher-assignments.rollover', [
            'sessions' => $this->sessions($assignmentService),
            'teachers' => $this->teachers(),
            'defaultFromSession' => (string) $payload['from_session'],
            'defaultToSession' => (string) $payload['to_session'],
            'selectedTeacherIds' => collect($payload['teacher_ids'] ?? [])->map(fn ($id) => (int) $id)->all(),
            'overwrite' => (bool) ($payload['overwrite'] ?? false),
            'preview' => $preview,
        ]);
    }

    public function store(
        TeacherAssignmentRolloverRequest $request,
        TeacherAssignmentRolloverService $rolloverService
    ): RedirectResponse {
        $payload = $request->validated();

        $summary = $rolloverService->copyAssignmentsToNextSession(
            (string) $payload['from_session'],
            (string) $payload['to_session'],
            $payload['teacher_ids'] ?? null,
            (bool) ($payload['overwrite'] ?? false)
        );

        $message = 'Teacher assignment rollover completed. '
            .(int) ($summary['copied_count'] ?? 0).' assignment(s) copied, '
            .(int) ($summary['skipped_duplicates'] ?? 0).' duplicate(s) skipped, '
            .(int) ($summary['overwritten_count'] ?? 0).' overwritten, '
            .(int) ($summary['class_teacher_conflicts'] ?? 0).' class-teacher conflict(s), '
            .(int) ($summary['affected_teachers_count'] ?? 0).' teacher(s) affected.';

        return redirect()
            ->route('principal.teacher-assignments.rollover.index')
            ->with('success', $message);
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\Teacher>
     */
    private function teachers()
    {
        return Teacher::query()
            ->with('user:id,name,email')
            ->orderBy('teacher_id')
            ->get(['id', 'teacher_id', 'user_id', 'employee_code']);
    }

    private function academicSessionForDate(string $date): string
    {
        $dateTime = Carbon::parse($date);
        $startYear = $dateTime->month >= 7 ? $dateTime->year : ($dateTime->year - 1);

        return $startYear.'-'.($startYear + 1);
    }

    private function nextSession(string $session): string
    {
        if (! preg_match('/^(\d{4})-(\d{4})$/', $session, $matches)) {
            $currentStart = (int) now()->format('Y');

            return ($currentStart + 1).'-'.($currentStart + 2);
        }

        $start = (int) $matches[1];
        $end = (int) $matches[2];

        return ($start + 1).'-'.($end + 1);
    }

    private function sessions(TeacherAssignmentService $assignmentService): array
    {
        $defaultSession = $this->academicSessionForDate(now()->toDateString());

        return collect(array_merge([$defaultSession, $this->nextSession($defaultSession)], $assignmentService->availableSessions()))
            ->filter(static fn ($value): bool => is_string($value) && trim($value) !== '')
            ->unique()
            ->values()
            ->all();
    }
}

