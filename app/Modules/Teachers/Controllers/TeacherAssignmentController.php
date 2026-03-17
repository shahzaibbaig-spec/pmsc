<?php

namespace App\Modules\Teachers\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Modules\Teachers\Requests\StoreTeacherAssignmentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TeacherAssignmentController extends Controller
{
    public function index(): View
    {
        $this->ensureTeacherProfiles();
        $defaultSession = $this->academicSessionForDate(now()->toDateString());
        $sessions = collect(array_merge([$defaultSession], $this->availableSessions()))
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->unique()
            ->values()
            ->all();

        return view('modules.principal.teachers.assignments', [
            'defaultSession' => $defaultSession,
            'sessions' => $sessions,
        ]);
    }

    public function options(): JsonResponse
    {
        $this->ensureTeacherProfiles();

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

        return response()->json([
            'teachers' => $teachers->map(fn (Teacher $teacher): array => [
                'id' => $teacher->id,
                'teacher_id' => $teacher->teacher_id,
                'name' => $teacher->user?->name,
                'email' => $teacher->user?->email,
                'designation' => $teacher->designation,
                'employee_code' => $teacher->employee_code,
            ])->values(),
            'classes' => $classes,
            'subjects' => $subjects,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'session' => ['nullable', 'string', 'max:20'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $search = (string) $request->input('search', '');
        $session = (string) $request->input('session', '');
        $perPage = (int) $request->input('per_page', 10);

        $query = TeacherAssignment::query()
            ->with([
                'teacher:id,teacher_id,user_id,designation,employee_code',
                'teacher.user:id,name,email',
                'classRoom:id,name,section',
                'subject:id,name',
            ])
            ->when($session !== '', fn ($q) => $q->where('session', $session))
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($w) use ($search): void {
                    $w->where('session', 'like', "%{$search}%")
                        ->orWhereHas('teacher', function ($tq) use ($search): void {
                            $tq->where('teacher_id', 'like', "%{$search}%")
                                ->orWhere('employee_code', 'like', "%{$search}%")
                                ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
                        })
                        ->orWhereHas('classRoom', fn ($cq) => $cq
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('section', 'like', "%{$search}%"))
                        ->orWhereHas('subject', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('id');

        $assignments = $query->paginate($perPage);

        $rows = collect($assignments->items())->map(function (TeacherAssignment $assignment): array {
            return [
                'id' => $assignment->id,
                'teacher_name' => $assignment->teacher?->user?->name,
                'teacher_id_code' => $assignment->teacher?->teacher_id,
                'employee_code' => $assignment->teacher?->employee_code,
                'designation' => $assignment->teacher?->designation,
                'class_name' => trim(($assignment->classRoom?->name ?? '').' '.($assignment->classRoom?->section ?? '')),
                'subject_name' => $assignment->subject?->name,
                'is_class_teacher' => $assignment->is_class_teacher,
                'session' => $assignment->session,
            ];
        })->values();

        return response()->json([
            'data' => $rows,
            'meta' => [
                'current_page' => $assignments->currentPage(),
                'last_page' => $assignments->lastPage(),
                'total' => $assignments->total(),
                'per_page' => $assignments->perPage(),
            ],
        ]);
    }

    public function store(StoreTeacherAssignmentRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $isClassTeacher = (bool) $payload['is_class_teacher'];

        if (! $isClassTeacher && empty($payload['subject_id'])) {
            return response()->json(['message' => 'Subject is required for subject teacher assignment.'], 422);
        }

        if ($isClassTeacher) {
            $payload['subject_id'] = null;
        }

        $result = DB::transaction(function () use ($payload, $isClassTeacher): array {
            if ($isClassTeacher) {
                $assignment = TeacherAssignment::query()->updateOrCreate(
                    [
                        'class_id' => $payload['class_id'],
                        'session' => $payload['session'],
                        'is_class_teacher' => true,
                    ],
                    [
                        'teacher_id' => $payload['teacher_id'],
                        'subject_id' => null,
                    ]
                );

                SchoolClass::query()
                    ->whereKey((int) $payload['class_id'])
                    ->update(['class_teacher_id' => (int) $payload['teacher_id']]);

                return [
                    'created' => $assignment->wasRecentlyCreated,
                    'message' => $assignment->wasRecentlyCreated
                        ? 'Class teacher assigned successfully.'
                        : 'Class teacher assignment updated successfully.',
                ];
            }

            $assignment = TeacherAssignment::query()->updateOrCreate(
                [
                    'class_id' => $payload['class_id'],
                    'subject_id' => $payload['subject_id'],
                    'session' => $payload['session'],
                    'is_class_teacher' => false,
                ],
                [
                    'teacher_id' => $payload['teacher_id'],
                ]
            );

            return [
                'created' => $assignment->wasRecentlyCreated,
                'message' => $assignment->wasRecentlyCreated
                    ? 'Subject teacher assigned successfully.'
                    : 'Subject teacher assignment updated successfully.',
            ];
        });

        return response()->json(['message' => $result['message']]);
    }

    public function destroy(TeacherAssignment $teacherAssignment): JsonResponse
    {
        $teacherAssignment->delete();

        return response()->json(['message' => 'Assignment deleted successfully.']);
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
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->values()
            ->all();

        $fallbackSessions = $this->sessionOptions();

        return collect(array_merge($storedSessions, $fallbackSessions))
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
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
