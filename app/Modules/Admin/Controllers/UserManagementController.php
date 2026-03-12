<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Admin\Requests\AssignRoleRequest;
use App\Modules\Admin\Requests\StoreUserRequest;
use App\Modules\Admin\Requests\UpdateUserRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index()
    {
        $roles = Role::query()
            ->orderBy('name')
            ->pluck('name')
            ->values();

        return view('modules.admin.users.index', compact('roles'));
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

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = User::query()->create([
                'name' => $request->string('name')->toString(),
                'email' => $request->string('email')->toString(),
                'password' => $request->string('password')->toString(),
                'status' => $request->string('status')->toString(),
            ]);

            $user->syncRoles([$request->string('role')->toString()]);

            return $user;
        });

        return response()->json([
            'message' => 'User created successfully.',
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
}
