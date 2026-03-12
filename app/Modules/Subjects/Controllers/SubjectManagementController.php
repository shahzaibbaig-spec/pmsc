<?php

namespace App\Modules\Subjects\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Modules\Subjects\Requests\StoreSubjectRequest;
use App\Modules\Subjects\Requests\UpdateSubjectRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectManagementController extends Controller
{
    public function index(): View
    {
        return view('modules.principal.subjects.index');
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

        $subjects = Subject::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->paginate($perPage);

        $rows = collect($subjects->items())->map(fn (Subject $subject): array => [
            'id' => $subject->id,
            'name' => $subject->name,
            'code' => $subject->code,
            'is_default' => (bool) $subject->is_default,
        ])->values();

        return response()->json([
            'data' => $rows,
            'meta' => [
                'current_page' => $subjects->currentPage(),
                'last_page' => $subjects->lastPage(),
                'total' => $subjects->total(),
                'per_page' => $subjects->perPage(),
            ],
        ]);
    }

    public function store(StoreSubjectRequest $request): JsonResponse
    {
        $subject = Subject::query()->create([
            'name' => $request->string('name')->toString(),
            'code' => $request->string('code')->toString() ?: null,
            'is_default' => false,
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Subject added successfully.',
            'subject_id' => $subject->id,
        ], 201);
    }

    public function update(UpdateSubjectRequest $request, Subject $subject): JsonResponse
    {
        $subject->update([
            'name' => $request->string('name')->toString(),
            'code' => $request->string('code')->toString() ?: null,
        ]);

        return response()->json(['message' => 'Subject updated successfully.']);
    }

    public function destroy(Subject $subject): JsonResponse
    {
        if ($subject->is_default) {
            return response()->json(['message' => 'Default Federal Board subjects cannot be deleted.'], 422);
        }

        if ($subject->students()->exists() || $subject->studentAssignments()->exists() || $subject->results()->exists() || $subject->teacherAssignments()->exists()) {
            return response()->json(['message' => 'Cannot delete subject because it is already assigned or used in records.'], 422);
        }

        $subject->delete();

        return response()->json(['message' => 'Subject deleted successfully.']);
    }
}
