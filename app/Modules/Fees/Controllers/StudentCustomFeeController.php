<?php

namespace App\Modules\Fees\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FeeChallan;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentFeeStructure;
use App\Modules\Fees\Requests\StoreStudentCustomFeeRequest;
use App\Modules\Fees\Services\FeeManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class StudentCustomFeeController extends Controller
{
    public function __construct(private readonly FeeManagementService $service)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'session' => ['nullable', 'string', 'max:20'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'search' => ['nullable', 'string', 'max:150'],
        ]);

        $sessions = $this->availableSessions();
        $selectedSession = (string) ($filters['session'] ?? (in_array('2026-2027', $sessions, true) ? '2026-2027' : ($sessions[0] ?? now()->year.'-'.(now()->year + 1))));

        $classes = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        $students = Student::query()
            ->with('classRoom:id,name,section')
            ->where('status', 'active')
            ->when(($filters['class_id'] ?? null) !== null && $filters['class_id'] !== '', function ($builder) use ($filters): void {
                $builder->where('class_id', (int) $filters['class_id']);
            })
            ->when(($filters['search'] ?? null) !== null && trim((string) $filters['search']) !== '', function ($builder) use ($filters): void {
                $search = trim((string) $filters['search']);
                $builder->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', '%'.$search.'%')
                        ->orWhere('student_id', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $studentRows = $students->getCollection();
        $classIds = $studentRows->pluck('class_id')->filter()->unique()->values();
        $studentIds = $studentRows->pluck('id')->values();

        $structuresByClass = collect();
        if ($classIds->isNotEmpty()) {
            $structuresByClass = FeeStructure::query()
                ->where('session', $selectedSession)
                ->where('is_active', true)
                ->whereIn('class_id', $classIds)
                ->orderBy('title')
                ->get(['id', 'class_id', 'title', 'fee_type', 'amount'])
                ->groupBy('class_id');
        }

        $customByStudent = collect();
        if ($studentIds->isNotEmpty() && $this->studentCustomFeeTableExists()) {
            $customByStudent = StudentFeeStructure::query()
                ->where('session', $selectedSession)
                ->whereIn('student_id', $studentIds)
                ->with('creator:id,name')
                ->get([
                    'id',
                    'student_id',
                    'session',
                    'tuition_fee',
                    'computer_fee',
                    'exam_fee',
                    'is_active',
                    'created_by',
                    'updated_at',
                ])
                ->keyBy('student_id');
        }

        $students->setCollection($studentRows->map(function (Student $student) use ($structuresByClass, $customByStudent): array {
            $defaultBreakdown = $this->service->customFeeBreakdownFromStructures(
                $structuresByClass->get($student->class_id, collect())
            );
            $defaultTotal = $this->service->customFeeTotal(
                $defaultBreakdown[FeeManagementService::STUDENT_CUSTOM_FEE_TUITION],
                $defaultBreakdown[FeeManagementService::STUDENT_CUSTOM_FEE_COMPUTER],
                $defaultBreakdown[FeeManagementService::STUDENT_CUSTOM_FEE_EXAM]
            );

            /** @var StudentFeeStructure|null $custom */
            $custom = $customByStudent->get($student->id);
            $customBreakdown = null;
            $customTotal = null;
            $status = 'Default';

            if ($custom !== null) {
                $customBreakdown = [
                    FeeManagementService::STUDENT_CUSTOM_FEE_TUITION => round((float) $custom->tuition_fee, 2),
                    FeeManagementService::STUDENT_CUSTOM_FEE_COMPUTER => round((float) $custom->computer_fee, 2),
                    FeeManagementService::STUDENT_CUSTOM_FEE_EXAM => round((float) $custom->exam_fee, 2),
                ];
                $customTotal = $this->service->customFeeTotal(
                    $customBreakdown[FeeManagementService::STUDENT_CUSTOM_FEE_TUITION],
                    $customBreakdown[FeeManagementService::STUDENT_CUSTOM_FEE_COMPUTER],
                    $customBreakdown[FeeManagementService::STUDENT_CUSTOM_FEE_EXAM]
                );
                $status = $custom->is_active ? 'Custom Active' : 'Custom Inactive';
            }

            return [
                'student' => $student,
                'class_name' => trim(($student->classRoom?->name ?? 'Class').' '.($student->classRoom?->section ?? '')),
                'default_breakdown' => $defaultBreakdown,
                'default_total' => $defaultTotal,
                'custom' => $custom,
                'custom_breakdown' => $customBreakdown,
                'custom_total' => $customTotal,
                'status' => $status,
            ];
        }));

        return view('modules.principal.fees.custom-fees.index', [
            'students' => $students,
            'classes' => $classes,
            'sessions' => $sessions,
            'selectedSession' => $selectedSession,
            'filters' => [
                'session' => $selectedSession,
                'class_id' => $filters['class_id'] ?? '',
                'search' => $filters['search'] ?? '',
            ],
            'feeFieldLabels' => $this->feeFieldLabels(),
        ]);
    }

    public function store(StoreStudentCustomFeeRequest $request): RedirectResponse
    {
        if (! $this->studentCustomFeeTableExists()) {
            return back()->with('error', 'Student custom fee table is missing. Please run migrations on this server.');
        }

        $validated = $request->validated();

        $this->service->upsertStudentCustomFee([
            'student_id' => (int) $validated['student_id'],
            'session' => (string) $validated['session'],
            'tuition_fee' => (float) $validated['tuition_fee'],
            'computer_fee' => (float) $validated['computer_fee'],
            'exam_fee' => (float) $validated['exam_fee'],
            'is_active' => true,
        ], (int) ($request->user()?->id ?? 0));

        return back()->with('status', 'Student custom fee saved successfully.');
    }

    public function reset(StudentFeeStructure $studentFeeStructure): RedirectResponse
    {
        if ($studentFeeStructure->is_active) {
            $studentFeeStructure->forceFill([
                'is_active' => false,
            ])->save();
        }

        return back()->with('status', 'Student custom fee reset successfully. Class fee will be used for future challans.');
    }

    private function availableSessions(): array
    {
        $sessions = [];
        if ($this->studentCustomFeeTableExists()) {
            $sessions = StudentFeeStructure::query()
                ->select('session')
                ->distinct()
                ->orderByDesc('session')
                ->pluck('session')
                ->values()
                ->all();
        }

        if (empty($sessions)) {
            $sessions = FeeStructure::query()
                ->select('session')
                ->distinct()
                ->orderByDesc('session')
                ->pluck('session')
                ->values()
                ->all();
        }

        if (empty($sessions)) {
            $sessions = FeeChallan::query()
                ->select('session')
                ->distinct()
                ->orderByDesc('session')
                ->pluck('session')
                ->values()
                ->all();
        }

        $futureSessions = collect($this->service->sessionOptions(0, 5))
            ->filter(static function (string $session): bool {
                [$startYear] = array_pad(explode('-', $session, 2), 2, null);

                return (int) $startYear >= 2026;
            });

        return collect($sessions)
            ->merge($futureSessions)
            ->filter(static fn ($session): bool => is_string($session) && trim($session) !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function feeFieldLabels(): array
    {
        return [
            FeeManagementService::STUDENT_CUSTOM_FEE_TUITION => 'Tuition Fee',
            FeeManagementService::STUDENT_CUSTOM_FEE_COMPUTER => 'Computer Fee',
            FeeManagementService::STUDENT_CUSTOM_FEE_EXAM => 'Exam Fee',
        ];
    }

    private function studentCustomFeeTableExists(): bool
    {
        return Schema::hasTable('student_fee_structures');
    }
}
