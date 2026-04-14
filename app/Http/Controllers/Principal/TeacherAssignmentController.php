<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Principal\AssignClassTeacherRequest;
use App\Http\Requests\Principal\BulkTeacherAssignmentRequest;
use App\Http\Requests\Principal\ReplaceTeacherSessionAssignmentsRequest;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\TeacherAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TeacherAssignmentController extends Controller
{
    public function index(Request $request, TeacherAssignmentService $service): View
    {
        $this->ensureTeacherProfiles($service);

        $defaultSession = $this->academicSessionForDate(now()->toDateString());
        $sessions = collect(array_merge([$defaultSession], $service->availableSessions()))
            ->filter(static fn ($value): bool => is_string($value) && trim($value) !== '')
            ->unique()
            ->values()
            ->all();

        $selectedSession = trim((string) $request->query('session', $defaultSession));
        $classTeacherSession = $selectedSession !== '' ? $selectedSession : $defaultSession;
        $search = trim((string) $request->query('search', ''));

        $assignments = TeacherAssignment::query()
            ->with([
                'teacher:id,teacher_id,user_id,designation,employee_code',
                'teacher.user:id,name,email',
                'classRoom:id,name,section',
                'subject:id,name',
            ])
            ->when($selectedSession !== '', fn ($query) => $query->where('session', $selectedSession))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('session', 'like', "%{$search}%")
                        ->orWhereHas('teacher', function ($teacherQuery) use ($search): void {
                            $teacherQuery->where('teacher_id', 'like', "%{$search}%")
                                ->orWhere('employee_code', 'like', "%{$search}%")
                                ->orWhereHas('user', function ($userQuery) use ($search): void {
                                    $userQuery->where('name', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%");
                                });
                        })
                        ->orWhereHas('classRoom', function ($classQuery) use ($search): void {
                            $classQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('section', 'like', "%{$search}%");
                        })
                        ->orWhereHas('subject', fn ($subjectQuery) => $subjectQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('session')
            ->orderBy('teacher_id')
            ->orderBy('class_id')
            ->orderByDesc('is_class_teacher')
            ->orderBy('subject_id')
            ->get();

        $groupedAssignments = $this->groupAssignmentsByTeacherAndSession($assignments)->values();

        return view('principal.teacher-assignments.index', [
            'assignmentsGrouped' => $groupedAssignments,
            'sessions' => $sessions,
            'selectedSession' => $selectedSession,
            'search' => $search,
            'classTeacherRows' => $service->getClassTeacherAssignmentsBySession($classTeacherSession),
            'classTeacherSession' => $classTeacherSession,
            'classes' => $this->classes(),
            'subjects' => $this->subjects(),
        ]);
    }

    public function create(TeacherAssignmentService $service): View
    {
        $this->ensureTeacherProfiles($service);

        $defaultSession = $this->academicSessionForDate(now()->toDateString());
        $sessions = collect(array_merge([$defaultSession], $service->availableSessions()))
            ->filter(static fn ($value): bool => is_string($value) && trim($value) !== '')
            ->unique()
            ->values()
            ->all();

        return view('principal.teacher-assignments.create', [
            'teachers' => $this->teachers(),
            'classes' => $this->classes(),
            'subjects' => $this->subjects(),
            'defaultSession' => $defaultSession,
            'sessions' => $sessions,
        ]);
    }

    public function storeBulk(
        BulkTeacherAssignmentRequest $request,
        TeacherAssignmentService $service
    ): RedirectResponse {
        $payload = $request->validated();

        $summary = $service->assignBulk(
            (int) $payload['teacher_id'],
            (string) $payload['session'],
            $payload['class_ids'],
            $payload['subject_ids'],
            isset($payload['class_teacher_class_id']) ? (int) $payload['class_teacher_class_id'] : null
        );

        return redirect()
            ->route('principal.teacher-assignments.index', ['session' => $payload['session']])
            ->with('success', $this->bulkAssignmentMessage('Bulk assignment saved.', $summary));
    }

    public function search(Request $request, TeacherAssignmentService $service): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = trim((string) ($validated['q'] ?? ''));
        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $results = $service->searchTeachers($query, (int) ($validated['limit'] ?? 15));

        return response()->json($results->values()->all());
    }

    public function classTeacherMatrix(Request $request, TeacherAssignmentService $service): JsonResponse
    {
        $defaultSession = $this->academicSessionForDate(now()->toDateString());
        $selectedSession = trim((string) $request->query('session', $defaultSession));
        if ($selectedSession === '') {
            $selectedSession = $defaultSession;
        }

        $rows = $service->getClassTeacherAssignmentsBySession($selectedSession);

        $html = view('principal.teacher-assignments.partials.class-teacher-table', [
            'selectedSession' => $selectedSession,
            'classTeacherRows' => $rows,
        ])->render();

        return response()->json([
            'session' => $selectedSession,
            'html' => $html,
        ]);
    }

    public function showTeacher(int $teacherId, Request $request, TeacherAssignmentService $service): JsonResponse
    {
        $this->ensureTeacherProfiles($service);

        $teacher = Teacher::query()
            ->with('user:id,name,email')
            ->findOrFail($teacherId, ['id', 'teacher_id', 'user_id', 'designation', 'employee_code']);

        $defaultSession = $this->academicSessionForDate(now()->toDateString());
        $selectedSession = trim((string) $request->query('session', $defaultSession));

        $summary = $service->getTeacherAssignmentSummary((int) $teacher->id, $selectedSession);

        $sessions = collect(array_merge([$selectedSession], $service->availableSessions()))
            ->filter(static fn ($value): bool => is_string($value) && trim($value) !== '')
            ->unique()
            ->values()
            ->all();

        $html = view('principal.teacher-assignments.partials.teacher-panel', [
            'teacher' => $teacher,
            'summary' => $summary,
            'selectedSession' => $selectedSession,
            'sessions' => $sessions,
            'classes' => $this->classes(),
            'subjects' => $this->subjects(),
        ])->render();

        return response()->json(['html' => $html]);
    }

    public function storeBulkForTeacher(
        BulkTeacherAssignmentRequest $request,
        int $teacherId,
        TeacherAssignmentService $service
    ): RedirectResponse {
        $payload = $request->validated();

        $summary = $service->assignBulk(
            $teacherId,
            (string) $payload['session'],
            $payload['class_ids'],
            $payload['subject_ids'],
            isset($payload['class_teacher_class_id']) ? (int) $payload['class_teacher_class_id'] : null
        );

        return redirect()
            ->route('principal.teacher-assignments.index', [
                'session' => $payload['session'],
                'focus_teacher' => $teacherId,
            ])
            ->with('success', $this->bulkAssignmentMessage('Teacher assignments updated.', $summary));
    }

    public function replaceSessionAssignmentsForTeacher(
        ReplaceTeacherSessionAssignmentsRequest $request,
        int $teacherId,
        TeacherAssignmentService $service
    ): RedirectResponse {
        $payload = $request->validated();

        $summary = $service->replaceTeacherAssignmentsForSession(
            $teacherId,
            (string) $payload['session'],
            $payload['class_ids'] ?? [],
            $payload['subject_ids'] ?? [],
            isset($payload['class_teacher_class_id']) ? (int) $payload['class_teacher_class_id'] : null
        );

        $message = 'Teacher session assignments replaced. '
            .(int) ($summary['overwritten_count'] ?? 0).' existing assignment(s) replaced, '
            .(int) ($summary['created_subject_assignments'] ?? 0).' subject assignment(s) created, '
            .(int) ($summary['skipped_duplicates'] ?? 0).' duplicate(s) skipped.';

        if ((bool) ($summary['class_teacher_assigned'] ?? false)) {
            $message .= ' Class teacher assignment created.';
        }

        return redirect()
            ->route('principal.teacher-assignments.index', [
                'session' => (string) $payload['session'],
                'focus_teacher' => $teacherId,
            ])
            ->with('success', $message);
    }

    public function assignClassTeacher(
        AssignClassTeacherRequest $request,
        TeacherAssignmentService $service
    ): RedirectResponse|JsonResponse {
        $payload = $request->validated();

        $result = $service->assignOrReplaceClassTeacher(
            (int) $payload['teacher_id'],
            (int) $payload['class_id'],
            (string) $payload['session']
        );

        $message = ((string) ($result['status'] ?? '')) === 'unchanged'
            ? 'This teacher is already assigned as class teacher for this class.'
            : 'Class teacher updated successfully.';

        if ($request->expectsJson()) {
            return response()->json([
                'status' => (string) ($result['status'] ?? 'assigned'),
                'message' => $message,
                'session' => (string) $payload['session'],
                'class_id' => (int) $payload['class_id'],
            ]);
        }

        return redirect()
            ->route('principal.teacher-assignments.index', ['session' => (string) $payload['session']])
            ->with('success', $message);
    }

    public function destroy(int $assignmentId, TeacherAssignmentService $service): RedirectResponse
    {
        $service->removeAssignment($assignmentId);

        return redirect()
            ->route('principal.teacher-assignments.index')
            ->with('success', 'Assignment deleted successfully.');
    }

    private function ensureTeacherProfiles(TeacherAssignmentService $service): void
    {
        $teacherUsers = User::role('Teacher')->orderBy('id')->get(['id']);
        foreach ($teacherUsers as $user) {
            $service->ensureTeacherProfileForUser((int) $user->id, 'Teacher');
        }
    }

    private function teachers(): Collection
    {
        return Teacher::query()
            ->with('user:id,name,email')
            ->orderBy('teacher_id')
            ->get(['id', 'teacher_id', 'user_id', 'designation', 'employee_code']);
    }

    private function classes(): Collection
    {
        return SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);
    }

    private function subjects(): Collection
    {
        return Subject::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    private function academicSessionForDate(string $date): string
    {
        $dateTime = Carbon::parse($date);
        $startYear = $dateTime->month >= 7 ? $dateTime->year : ($dateTime->year - 1);

        return $startYear.'-'.($startYear + 1);
    }

    /**
     * @param Collection<int, TeacherAssignment> $assignments
     * @return Collection<int, array{
     *   teacher:\App\Models\Teacher|null,
     *   session:string|null,
     *   class_teacher_assignments:Collection<int, TeacherAssignment>,
     *   subject_assignments_by_class:Collection<int, array{class:\App\Models\SchoolClass|null,assignments:Collection<int, TeacherAssignment>}>
     * }>
     */
    private function groupAssignmentsByTeacherAndSession(Collection $assignments): Collection
    {
        return $assignments
            ->groupBy(static fn (TeacherAssignment $assignment): string => $assignment->teacher_id.'|'.$assignment->session)
            ->map(function (Collection $group): array {
                /** @var TeacherAssignment|null $first */
                $first = $group->first();

                $classTeacherAssignments = $group
                    ->where('is_class_teacher', true)
                    ->sortBy(static fn (TeacherAssignment $assignment): string => trim(
                        ($assignment->classRoom?->name ?? '').' '.($assignment->classRoom?->section ?? '')
                    ))
                    ->values();

                $subjectAssignmentsByClass = $group
                    ->where('is_class_teacher', false)
                    ->whereNotNull('subject_id')
                    ->groupBy('class_id')
                    ->map(function (Collection $classGroup): array {
                        /** @var TeacherAssignment|null $classFirst */
                        $classFirst = $classGroup->first();

                        return [
                            'class' => $classFirst?->classRoom,
                            'assignments' => $classGroup
                                ->sortBy(static fn (TeacherAssignment $assignment): string => $assignment->subject?->name ?? '')
                                ->values(),
                        ];
                    })
                    ->values();

                return [
                    'teacher' => $first?->teacher,
                    'session' => $first?->session,
                    'class_teacher_assignments' => $classTeacherAssignments,
                    'subject_assignments_by_class' => $subjectAssignmentsByClass,
                ];
            })
            ->values();
    }

    /**
     * @param array<string, mixed> $summary
     */
    private function bulkAssignmentMessage(string $prefix, array $summary): string
    {
        $message = $prefix.' '
            .(int) ($summary['created_subject_assignments'] ?? 0).' subject assignment(s) created, '
            .(int) ($summary['skipped_duplicates'] ?? 0).' duplicate(s) skipped.';

        if ((bool) ($summary['class_teacher_assigned'] ?? false)) {
            $message .= ' Class teacher assignment created.';
        }

        return $message;
    }
}
