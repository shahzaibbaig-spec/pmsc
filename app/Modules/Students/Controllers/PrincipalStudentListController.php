<?php

namespace App\Modules\Students\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrincipalStudentListController extends Controller
{
    public function index(): View
    {
        $classes = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        return view('modules.principal.students.list', [
            'classes' => $classes,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
            'sort' => ['nullable', 'string', 'in:student_id,name,father_name,class_name,status'],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 10);
        $sort = (string) ($validated['sort'] ?? 'id');
        $dir = (string) ($validated['dir'] ?? 'desc');

        $sortColumn = match ($sort) {
            'student_id' => 'student_id',
            'name' => 'name',
            'father_name' => 'father_name',
            'class_name' => 'class_id',
            'status' => 'status',
            default => 'id',
        };

        $contains = '%'.$search.'%';
        $prefix = $search.'%';

        $query = Student::query()
            ->with('classRoom:id,name,section')
            ->when($search !== '', function ($builder) use ($contains, $prefix): void {
                $builder->where(function ($q) use ($contains, $prefix): void {
                    $q->where('name', 'like', $contains)
                        ->orWhere('student_id', 'like', $prefix)
                        ->orWhere('father_name', 'like', $contains)
                        ->orWhereHas('classRoom', function ($classQuery) use ($contains): void {
                            $classQuery->where('name', 'like', $contains)
                                ->orWhere('section', 'like', $contains);
                        });
                });
            })
            ->orderBy($sortColumn, $dir)
            ->orderBy('id', 'desc');

        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => collect($paginator->items())->map(function (Student $student): array {
                return [
                    'id' => $student->id,
                    'student_id' => $student->student_id,
                    'name' => $student->name,
                    'father_name' => $student->father_name,
                    'class_name' => trim(($student->classRoom?->name ?? '').' '.($student->classRoom?->section ?? '')),
                    'status' => $student->status,
                    'profile_url' => route('principal.students.show', $student),
                    'id_card_url' => route('idcards.single', ['student' => $student]),
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
