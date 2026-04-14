<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use App\Modules\Admin\Requests\AssignRoleRequest;
use App\Modules\Admin\Requests\StoreUserRequest;
use App\Modules\Admin\Requests\UpdateUserRequest;
use App\Services\TeacherAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index(TeacherAssignmentService $assignmentService)
    {
        $roles = Role::query()
            ->orderBy('name')
            ->pluck('name')
            ->values();

        $defaultSession = $this->academicSessionForDate(now()->toDateString());
        $assignmentSessions = collect(array_merge([$defaultSession], $assignmentService->availableSessions()))
            ->filter(static fn ($value): bool => is_string($value) && trim($value) !== '')
            ->unique()
            ->values();

        $assignmentClasses = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        $assignmentSubjects = Subject::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return view('modules.admin.users.index', compact(
            'roles',
            'assignmentSessions',
            'assignmentClasses',
            'assignmentSubjects',
            'defaultSession'
        ));
    }

    public function data(Request $request): JsonResponse
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $search = (string) $request->input('search', '');
        $perPage = (int) $request->input('per_page', 10);
        $searchPrefix = $search !== '' ? $search.'%' : null;
        $searchContains = $search !== '' ? '%'.$search.'%' : null;

        $users = User::query()
            ->with('roles:id,name')
            ->when($search !== '', function ($query) use ($searchContains, $searchPrefix): void {
                $query->where(function ($q) use ($searchContains, $searchPrefix): void {
                    $q->where('name', 'like', $searchContains)
                        ->orWhere('email', 'like', $searchPrefix)
                        ->orWhereHas('roles', function ($roleQuery) use ($searchContains): void {
                            $roleQuery->where('name', 'like', $searchContains);
                        });
                });
            })
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        $rows = collect($users->items())->map(function (User $user): array {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name,
                'status' => $user->status ?? 'active',
            ];
        })->values();

        return response()->json([
            'data' => $rows,
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
                'per_page' => $users->perPage(),
            ],
        ]);
    }

    public function store(StoreUserRequest $request, TeacherAssignmentService $assignmentService): JsonResponse
    {
        $validated = $request->validated();
        $role = (string) ($validated['role'] ?? '');
        $assignmentSummary = null;

        $user = DB::transaction(function () use ($validated, $role, $assignmentService, &$assignmentSummary): User {
            $user = User::query()->create([
                'name' => (string) $validated['name'],
                'email' => (string) $validated['email'],
                'password' => (string) $validated['password'],
                'status' => (string) $validated['status'],
            ]);

            $user->syncRoles([$role]);

            if (strcasecmp($role, 'Teacher') === 0) {
                $teacher = $assignmentService->ensureTeacherProfileForUser((int) $user->id, 'Teacher');

                $classIds = is_array($validated['assignment_class_ids'] ?? null)
                    ? $validated['assignment_class_ids']
                    : [];
                $subjectIds = is_array($validated['assignment_subject_ids'] ?? null)
                    ? $validated['assignment_subject_ids']
                    : [];
                $classTeacherClassId = isset($validated['class_teacher_class_id'])
                    ? (int) $validated['class_teacher_class_id']
                    : null;

                $hasAssignmentPayload = ! empty($classIds)
                    || ! empty($subjectIds)
                    || $classTeacherClassId !== null;

                if ($hasAssignmentPayload) {
                    $session = trim((string) ($validated['assignment_session'] ?? ''));
                    if ($session === '') {
                        $session = $this->academicSessionForDate(now()->toDateString());
                    }

                    $assignmentSummary = $assignmentService->assignBulk(
                        (int) $teacher->id,
                        $session,
                        $classIds,
                        $subjectIds,
                        $classTeacherClassId
                    );
                }
            }

            return $user;
        });

        $message = 'User created successfully.';
        if (strcasecmp($role, 'Teacher') === 0 && is_array($assignmentSummary)) {
            $message = 'Teacher user created and initial assignments saved. '
                .(int) ($assignmentSummary['created_subject_assignments'] ?? 0).' subject assignment(s) created, '
                .(int) ($assignmentSummary['skipped_duplicates'] ?? 0).' duplicate(s) skipped.';
            if ((bool) ($assignmentSummary['class_teacher_assigned'] ?? false)) {
                $message .= ' Class teacher assignment created.';
            }
        }

        return response()->json([
            'message' => $message,
            'user_id' => $user->id,
        ], 201);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        DB::transaction(function () use ($request, $user): void {
            $payload = [
                'name' => $request->string('name')->toString(),
                'email' => $request->string('email')->toString(),
                'status' => $request->string('status')->toString(),
            ];

            if ($request->filled('password')) {
                $payload['password'] = $request->string('password')->toString();
            }

            $user->update($payload);
        });

        return response()->json(['message' => 'User updated successfully.']);
    }

    public function destroy(User $user, Request $request): JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }

    public function assignRole(AssignRoleRequest $request, User $user): JsonResponse
    {
        $user->syncRoles([$request->string('role')->toString()]);

        return response()->json(['message' => 'Role assigned successfully.']);
    }

    private function academicSessionForDate(string $date): string
    {
        $dateTime = Carbon::parse($date);
        $startYear = $dateTime->month >= 7 ? $dateTime->year : ($dateTime->year - 1);

        return $startYear.'-'.($startYear + 1);
    }
}
