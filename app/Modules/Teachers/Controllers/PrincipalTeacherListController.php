<?php

namespace App\Modules\Teachers\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrincipalTeacherListController extends Controller
{
    public function index(): View
    {
        return view('modules.principal.teachers.list');
    }

    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
            'sort' => ['nullable', 'string', 'in:name,email,employee_code,designation,assignments_count,status'],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 10);
        $sort = (string) ($validated['sort'] ?? 'name');
        $dir = (string) ($validated['dir'] ?? 'asc');

        $sortColumn = match ($sort) {
            'name' => 'users.name',
            'email' => 'users.email',
            'employee_code' => 'teachers.employee_code',
            'designation' => 'teachers.designation',
            'assignments_count' => 'assignments_count',
            'status' => 'users.status',
            default => 'users.name',
        };

        $contains = '%'.$search.'%';
        $prefix = $search.'%';

        $query = Teacher::query()
            ->join('users', 'users.id', '=', 'teachers.user_id')
            ->select([
                'teachers.*',
                'users.name as user_name',
                'users.email as user_email',
                'users.status as user_status',
            ])
            ->withCount('assignments')
            ->when($search !== '', function ($builder) use ($contains, $prefix): void {
                $builder->where(function ($q) use ($contains, $prefix): void {
                    $q->where('users.name', 'like', $contains)
                        ->orWhere('users.email', 'like', $contains)
                        ->orWhere('teachers.teacher_id', 'like', $prefix)
                        ->orWhere('teachers.employee_code', 'like', $prefix)
                        ->orWhere('teachers.designation', 'like', $contains);
                });
            })
            ->orderBy($sortColumn, $dir)
            ->orderBy('teachers.id');

        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => collect($paginator->items())->map(function (Teacher $teacher): array {
                return [
                    'id' => $teacher->id,
                    'teacher_id' => $teacher->teacher_id,
                    'name' => (string) ($teacher->getAttribute('user_name') ?? ''),
                    'email' => (string) ($teacher->getAttribute('user_email') ?? ''),
                    'employee_code' => $teacher->employee_code,
                    'designation' => $teacher->designation,
                    'status' => (string) ($teacher->getAttribute('user_status') ?? 'active'),
                    'assignments_count' => (int) ($teacher->assignments_count ?? 0),
                ];
            })->values()->all(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'sort' => [
                'by' => $sort,
                'dir' => $dir,
            ],
        ]);
    }
}
