<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Principal\BulkTeacherAssignmentRequest;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\TeacherAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TeacherAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureTeacherProfiles();

        $defaultSession = $this->academicSessionForDate(now()->toDateString());
        $sessions = collect(array_merge([$defaultSession], $this->availableSessions()))
            ->filter(static fn ($value): bool => is_string($value) && trim($value) !== '')
            ->unique()
            ->values()
            ->all();

        $selectedSession = trim((string) $request->query('session', $defaultSession));
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
                                ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                        })
                        ->orWhereHas('classRoom', fn ($classQuery) => $classQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('section', 'like', "%{$search}%"))
                        ->orWhereHas('subject', fn ($subjectQuery) => $subjectQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('session')
            ->orderBy('teacher_id')
            ->orderBy('class_id')
            ->orderByDesc('is_class_teacher')
            ->orderBy('subject_id')
            ->get();

        $groupedAssignments = $assignments
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

        return view('principal.teacher-assignments.index', [
            'assignmentsGrouped' => $groupedAssignments,
            'sessions' => $sessions,
            'selectedSession' => $selectedSession,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        $this->ensureTeacherProfiles();

        $defaultSession = $this->academicSessionForDate(now()->toDateString());
        $sessions = collect(array_merge([$defaultSession], $this->availableSessions()))
            ->filter(static fn ($value): bool => is_string($value) && trim($value) !== '')
            ->unique()
            ->values()
            ->all();

        $teachers = Teacher::query()
            ->with('user:id,name,email')
            ->orderBy('teacher_id')
            ->get(['id', 'teacher_id', 'user_id', 'designation', 'employee_code']);

        $classes = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        $subjects = Subject::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return view('principal.teacher-assignments.create', [
            'teachers' => $teachers,
            'classes' => $classes,
            'subjects' => $subjects,
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

        $message = 'Bulk assignment saved. '
            .$summary['created_subject_assignments'].' subject assignment(s) created, '
            .$summary['skipped_duplicates'].' duplicate(s) skipped.';

        if ($summary['class_teacher_assigned']) {
            $message .= ' Class teacher assignment created.';
        }

        return redirect()
            ->route('principal.teacher-assignments.index', ['session' => $payload['session']])
            ->with('success', $message);
    }

    public function destroy(int $assignmentId, TeacherAssignmentService $service): RedirectResponse
    {
        $service->removeAssignment($assignmentId);

        return redirect()
            ->route('principal.teacher-assignments.index')
            ->with('success', 'Assignment deleted successfully.');
    }

    private function ensureTeacherProfiles(): void
    {
        $teacherUsers = User::role('Teacher')->orderBy('id')->get(['id']);

        foreach ($teacherUsers as $user) {
            $exists = Teacher::query()->where('user_id', $user->id)->exists();
            if ($exists) {
                continue;
            }

            Teacher::query()->create([
                'teacher_id' => $this->nextTeacherCode(),
                'user_id' => $user->id,
                'designation' => 'Teacher',
                'employee_code' => null,
            ]);
        }
    }

    private function nextTeacherCode(): string
    {
        $lastId = (int) Teacher::query()->max('id');
        $next = $lastId + 1;

        return 'T-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function academicSessionForDate(string $date): string
    {
        $dateTime = Carbon::parse($date);
        $startYear = $dateTime->month >= 7 ? $dateTime->year : ($dateTime->year - 1);

        return $startYear.'-'.($startYear + 1);
    }

    private function availableSessions(): array
    {
        $storedSessions = TeacherAssignment::query()
            ->select('session')
            ->distinct()
            ->orderByDesc('session')
            ->pluck('session')
            ->filter(static fn ($value): bool => is_string($value) && trim($value) !== '')
            ->values()
            ->all();

        $fallbackSessions = $this->sessionOptions();

        return collect(array_merge($storedSessions, $fallbackSessions))
            ->filter(static fn ($value): bool => is_string($value) && trim($value) !== '')
            ->unique()
            ->values()
            ->all();
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
