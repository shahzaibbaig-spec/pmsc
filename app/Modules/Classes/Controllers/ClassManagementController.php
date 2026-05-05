<?php

namespace App\Modules\Classes\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Modules\Classes\Requests\AssignClassSubjectsRequest;
use App\Modules\Classes\Requests\CopyClassSubjectsRequest;
use App\Modules\Classes\Requests\StoreClassRequest;
use App\Modules\Classes\Requests\UpdateClassRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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

        $classes = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section', 'status'])
            ->map(static fn (SchoolClass $classRoom): array => [
                'id' => (int) $classRoom->id,
                'name' => (string) $classRoom->name,
                'section' => $classRoom->section,
                'status' => (string) ($classRoom->status ?? 'active'),
                'display_name' => trim((string) $classRoom->name.' '.(string) ($classRoom->section ?? '')),
            ])
            ->values();

        return response()->json([
            'subjects' => $subjects,
            'classes' => $classes,
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

    public function copySubjects(CopyClassSubjectsRequest $request): JsonResponse
    {
        $sourceClass = SchoolClass::query()
            ->with('subjects:id')
            ->findOrFail((int) $request->integer('source_class_id'));
        $targetClass = SchoolClass::query()
            ->with('subjects:id')
            ->findOrFail((int) $request->integer('target_class_id'));
        $copyMode = (string) $request->string('copy_mode', 'copy_missing_only');

        $this->validateCopySubjectsRequest($sourceClass, $targetClass, $copyMode);

        $sourceSubjectIds = $sourceClass->subjects
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($sourceSubjectIds === []) {
            throw ValidationException::withMessages([
                'source_class_id' => 'The source section has no assigned subjects.',
            ]);
        }

        $summary = [
            'total_source_subjects' => count($sourceSubjectIds),
            'copied_count' => 0,
            'skipped_count' => 0,
            'replaced_count' => 0,
        ];

        DB::transaction(function () use (
            $sourceSubjectIds,
            $sourceClass,
            $targetClass,
            $copyMode,
            &$summary
        ): void {
            $targetSubjectIds = $targetClass->subjects
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->filter(static fn (int $id): bool => $id > 0)
                ->unique()
                ->values()
                ->all();

            if ($copyMode === 'replace_target_subjects') {
                $summary['replaced_count'] = count($targetSubjectIds);
                $targetClass->subjects()->sync($sourceSubjectIds);
                $summary['copied_count'] = count($sourceSubjectIds);
                $summary['skipped_count'] = 0;

                return;
            }

            $missingSubjectIds = array_values(array_diff($sourceSubjectIds, $targetSubjectIds));
            if ($missingSubjectIds !== []) {
                $targetClass->subjects()->attach($missingSubjectIds);
            }

            $summary['copied_count'] = count($missingSubjectIds);
            $summary['skipped_count'] = count($sourceSubjectIds) - $summary['copied_count'];
        });

        $sourceLabel = trim((string) $sourceClass->name.' '.(string) ($sourceClass->section ?? ''));
        $targetLabel = trim((string) $targetClass->name.' '.(string) ($targetClass->section ?? ''));

        $message = 'Copied '.$summary['copied_count'].' subject(s) from '.$sourceLabel.' to '.$targetLabel
            .', skipped '.$summary['skipped_count'].'.';

        if ($summary['replaced_count'] > 0) {
            $message .= ' Replaced '.$summary['replaced_count'].' existing target subject(s).';
        }

        return response()->json([
            'message' => $message,
            'summary' => $summary,
        ]);
    }

    private function validateCopySubjectsRequest(
        SchoolClass $sourceClass,
        SchoolClass $targetClass,
        string $copyMode
    ): void {
        $errors = [];

        if (! in_array($copyMode, ['copy_missing_only', 'replace_target_subjects'], true)) {
            $errors['copy_mode'] = 'The selected copy mode is invalid.';
        }

        if ((int) $sourceClass->id === (int) $targetClass->id) {
            $errors['target_class_id'] = 'The source and target sections must be different.';
        }

        if (
            $this->normalizeClassName((string) $sourceClass->name)
            !== $this->normalizeClassName((string) $targetClass->name)
        ) {
            $errors['target_class_id'] = 'Subjects can only be copied between sections of the same class.';
        }

        if (
            $this->normalizeSectionName($sourceClass->section)
            === $this->normalizeSectionName($targetClass->section)
        ) {
            $errors['target_class_id'] = 'The source and target sections must be different.';
        }

        if (strtolower(trim((string) $targetClass->status)) !== 'active') {
            $errors['target_class_id'] = 'The target section must be active.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function normalizeClassName(string $name): string
    {
        return mb_strtolower(trim($name));
    }

    private function normalizeSectionName(?string $section): string
    {
        return mb_strtolower(trim((string) $section));
    }
}
