<?php

namespace App\Modules\Subjects\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\SubjectGroup;
use App\Modules\Subjects\Requests\AssignStudentSubjectGroupRequest;
use App\Modules\Subjects\Requests\AssignClassStudentSubjectsRequest;
use App\Modules\Subjects\Requests\StoreClassCustomSubjectRequest;
use App\Modules\Subjects\Requests\StoreSubjectGroupRequest;
use App\Modules\Subjects\Requests\StudentSubjectAssignmentMatrixQueryRequest;
use App\Modules\Subjects\Requests\SubjectGroupQueryRequest;
use App\Modules\Subjects\Requests\UpdateSubjectGroupRequest;
use App\Modules\Subjects\Requests\UpdateStudentSubjectAssignmentsRequest;
use App\Modules\Subjects\Services\StudentSubjectAssignmentMatrixService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;

class StudentSubjectAssignmentMatrixController extends Controller
{
    public function __construct(private readonly StudentSubjectAssignmentMatrixService $service)
    {
    }

    public function index(): View
    {
        $classes = SchoolClass::query()
            ->withCount('students')
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        $defaultClass = $classes->firstWhere('students_count', '>', 0) ?? $classes->first();
        $sessions = $this->service->sessionOptions();
        $defaultSession = $sessions[1] ?? ($sessions[0] ?? now()->year.'-'.(now()->year + 1));

        return view('modules.principal.subjects.student-assignment-matrix', [
            'classes' => $classes,
            'sessions' => $sessions,
            'defaultSession' => $defaultSession,
            'defaultClassId' => $defaultClass?->id,
        ]);
    }

    public function data(StudentSubjectAssignmentMatrixQueryRequest $request): JsonResponse
    {
        $payload = $this->service->matrix(
            (int) $request->input('class_id'),
            $request->string('session')->toString(),
            $request->input('search'),
            (int) ($request->input('page', 1)),
            (int) ($request->input('per_page', 20))
        );

        return response()->json($payload);
    }

    public function update(UpdateStudentSubjectAssignmentsRequest $request): JsonResponse
    {
        try {
            $assigned = $this->service->replaceStudentAssignments(
                (int) $request->input('student_id'),
                $request->string('session')->toString(),
                $request->input('subjects', []),
                (int) $request->user()->id
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Student subject assignment updated.',
            'assigned_count' => $assigned,
            'updated_at' => now()->toDateTimeString(),
        ]);
    }

    public function assignClass(AssignClassStudentSubjectsRequest $request): JsonResponse
    {
        $payload = $request->validated();
        Log::info('Bulk class subject assignment request received.', [
            'user_id' => (int) $request->user()->id,
            'session' => (string) ($payload['session'] ?? ''),
            'class_id' => (int) ($payload['class_id'] ?? 0),
            'subject_ids' => $payload['subject_ids'] ?? [],
        ]);

        try {
            $result = $this->service->replaceClassAssignments(
                (int) ($payload['class_id'] ?? 0),
                (string) ($payload['session'] ?? ''),
                $payload['subject_ids'] ?? [],
                (int) $request->user()->id
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Subjects assigned to class successfully.',
            'students_count' => $result['students_count'] ?? 0,
            'subjects_count' => $result['subjects_count'] ?? 0,
            'assignments_created' => $result['assignments_created'] ?? 0,
        ]);
    }

    public function storeCustomSubject(StoreClassCustomSubjectRequest $request): JsonResponse
    {
        try {
            $result = $this->service->createCustomSubjectForClass(
                (int) $request->input('class_id'),
                $request->string('name')->toString()
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $message = ($result['already_attached'] ?? false)
            ? 'Subject already exists and is available for all classes.'
            : (($result['was_created'] ?? false)
                ? 'Custom subject added and made available for all classes.'
                : 'Subject made available for all classes.');

        return response()->json([
            'message' => $message,
            'subject' => $result['subject'] ?? null,
        ], 201);
    }

    public function subjectGroups(SubjectGroupQueryRequest $request): JsonResponse
    {
        $groups = $this->service->subjectGroups(
            (int) $request->input('class_id'),
            $request->string('session')->toString()
        );

        return response()->json([
            'groups' => $groups,
        ]);
    }

    public function storeSubjectGroup(StoreSubjectGroupRequest $request): JsonResponse
    {
        try {
            $group = $this->service->createSubjectGroup(
                $request->string('session')->toString(),
                (int) $request->input('class_id'),
                trim((string) $request->input('name')),
                $request->input('description'),
                $request->input('subjects', []),
                (int) $request->user()->id,
                $request->boolean('is_active', true),
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Subject group created successfully.',
            'group' => $group,
        ]);
    }

    public function updateSubjectGroup(UpdateSubjectGroupRequest $request, SubjectGroup $subjectGroup): JsonResponse
    {
        try {
            $group = $this->service->updateSubjectGroup(
                $subjectGroup,
                trim((string) $request->input('name')),
                $request->input('description'),
                $request->input('subjects', []),
                $request->has('is_active') ? $request->boolean('is_active') : null
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Subject group updated successfully.',
            'group' => $group,
        ]);
    }

    public function assignGroup(AssignStudentSubjectGroupRequest $request): JsonResponse
    {
        try {
            $result = $this->service->assignSubjectGroupToStudent(
                (int) $request->input('student_id'),
                $request->string('session')->toString(),
                $request->filled('group_id') ? (int) $request->input('group_id') : null,
                (int) $request->user()->id
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Student group assignment updated.',
            'group_id' => $result['group_id'],
            'assigned_count' => $result['assigned_count'],
            'skipped_due_common' => $result['skipped_due_common'],
            'updated_at' => $result['updated_at'],
        ]);
    }
}
