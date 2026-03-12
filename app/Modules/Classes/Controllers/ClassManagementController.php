<?php

namespace App\Modules\Classes\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Modules\Classes\Requests\AssignClassSubjectsRequest;
use App\Modules\Classes\Requests\StoreClassRequest;
use App\Modules\Classes\Requests\UpdateClassRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClassManagementController extends Controller
{
    public function index(): View
    {
        return view('modules.principal.classes.index');
    }

    public function options(): JsonResponse
    {
        $subjects = Subject::query()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_default']);

        return response()->json([
            'subjects' => $subjects,
        ]);
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

        $classes = SchoolClass::query()
            ->with(['subjects:id,name,code'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('section', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->orderBy('section')
            ->paginate($perPage);

        $rows = collect($classes->items())->map(function (SchoolClass $classRoom): array {
            return [
                'id' => $classRoom->id,
                'name' => $classRoom->name,
                'section' => $classRoom->section,
                'display_name' => trim($classRoom->name.' '.($classRoom->section ?? '')),
                'subjects' => $classRoom->subjects->map(fn (Subject $subject): array => [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'code' => $subject->code,
                ])->values()->all(),
                'subject_count' => $classRoom->subjects->count(),
            ];
        })->values();

        return response()->json([
            'data' => $rows,
            'meta' => [
                'current_page' => $classes->currentPage(),
                'last_page' => $classes->lastPage(),
                'total' => $classes->total(),
                'per_page' => $classes->perPage(),
            ],
        ]);
    }

    public function store(StoreClassRequest $request): JsonResponse
    {
        $classRoom = SchoolClass::query()->create([
            'name' => $request->string('name')->toString(),
            'section' => $request->string('section')->toString() ?: null,
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Class created successfully.',
            'class_id' => $classRoom->id,
        ], 201);
    }

    public function update(UpdateClassRequest $request, SchoolClass $schoolClass): JsonResponse
    {
        $schoolClass->update([
            'name' => $request->string('name')->toString(),
            'section' => $request->string('section')->toString() ?: null,
        ]);

        return response()->json(['message' => 'Class updated successfully.']);
    }

    public function assignSubjects(AssignClassSubjectsRequest $request, SchoolClass $schoolClass): JsonResponse
    {
        $subjectIds = collect($request->input('subject_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        DB::transaction(function () use ($schoolClass, $subjectIds): void {
            $schoolClass->subjects()->sync($subjectIds);
        });

        return response()->json(['message' => 'Class subjects updated successfully.']);
    }
}

