<?php

namespace App\Modules\Fees\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Modules\Fees\Requests\StoreFeeStructureRequest;
use App\Modules\Fees\Requests\UpdateFeeStructureRequest;
use App\Modules\Fees\Services\FeeManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeeStructureController extends Controller
{
    public function __construct(private readonly FeeManagementService $service)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'session' => ['nullable', 'string', 'max:20'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'is_active' => ['nullable', 'in:0,1'],
            'fee_type' => ['nullable', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:150'],
        ]);

        $query = FeeStructure::query()
            ->with([
                'classRoom:id,name,section',
                'creator:id,name',
            ])
            ->when(($filters['session'] ?? null) !== null && $filters['session'] !== '', function ($builder) use ($filters): void {
                $builder->where('session', (string) $filters['session']);
            })
            ->when(($filters['class_id'] ?? null) !== null && $filters['class_id'] !== '', function ($builder) use ($filters): void {
                $builder->where('class_id', (int) $filters['class_id']);
            })
            ->when(($filters['is_active'] ?? null) !== null && $filters['is_active'] !== '', function ($builder) use ($filters): void {
                $builder->where('is_active', (int) $filters['is_active'] === 1);
            })
            ->when(($filters['fee_type'] ?? null) !== null && $filters['fee_type'] !== '', function ($builder) use ($filters): void {
                $builder->where('fee_type', (string) $filters['fee_type']);
            })
            ->when(($filters['title'] ?? null) !== null && trim((string) $filters['title']) !== '', function ($builder) use ($filters): void {
                $builder->where('title', 'like', '%'.trim((string) $filters['title']).'%');
            })
            ->latest();

        $structures = $query->paginate(15)->withQueryString();
        $classes = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        $sessions = FeeStructure::query()
            ->select('session')
            ->distinct()
            ->orderByDesc('session')
            ->pluck('session')
            ->values()
            ->all();

        if (empty($sessions)) {
            $sessions = $this->service->sessionOptions();
        }

        $feeTypes = FeeStructure::query()
            ->select('fee_type')
            ->distinct()
            ->orderBy('fee_type')
            ->pluck('fee_type')
            ->filter()
            ->values()
            ->all();

        if (empty($feeTypes)) {
            $feeTypes = $this->defaultFeeTypes();
        }

        return view('modules.principal.fees.structures.index', [
            'structures' => $structures,
            'classes' => $classes,
            'sessions' => $sessions,
            'feeTypes' => $feeTypes,
            'filters' => [
                'session' => $filters['session'] ?? '',
                'class_id' => $filters['class_id'] ?? '',
                'is_active' => $filters['is_active'] ?? '',
                'fee_type' => $filters['fee_type'] ?? '',
                'title' => $filters['title'] ?? '',
            ],
            'canCreate' => $request->user()?->can('create_fee_structure') ?? false,
            'canEdit' => $request->user()?->can('edit_fee_structure') ?? false,
            'canDelete' => $request->user()?->can('delete_fee_structure') ?? false,
        ]);
    }

    public function create(): View
    {
        $classes = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        return view('modules.principal.fees.structures.create', [
            'classes' => $classes,
            'sessions' => $this->service->sessionOptions(),
            'feeTypes' => $this->defaultFeeTypes(),
            'defaultSession' => $this->service->sessionOptions()[1] ?? $this->service->sessionOptions()[0] ?? now()->year.'-'.(now()->year + 1),
        ]);
    }

    public function store(StoreFeeStructureRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        FeeStructure::query()->create([
            'session' => (string) $validated['session'],
            'class_id' => (int) $validated['class_id'],
            'title' => trim((string) $validated['title']),
            'amount' => round((float) $validated['amount'], 2),
            'fee_type' => trim((string) $validated['fee_type']),
            'is_monthly' => $request->boolean('is_monthly'),
            'is_active' => $request->boolean('is_active', true),
            'created_by' => (int) $request->user()->id,
        ]);

        return redirect()
            ->route('principal.fees.structures.index')
            ->with('status', 'Fee structure created successfully.');
    }

    public function edit(FeeStructure $feeStructure): View
    {
        $classes = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        return view('modules.principal.fees.structures.edit', [
            'feeStructure' => $feeStructure,
            'classes' => $classes,
            'sessions' => $this->service->sessionOptions(),
            'feeTypes' => $this->defaultFeeTypes(),
        ]);
    }

    public function update(UpdateFeeStructureRequest $request, FeeStructure $feeStructure): RedirectResponse
    {
        $validated = $request->validated();

        $feeStructure->forceFill([
            'session' => (string) $validated['session'],
            'class_id' => (int) $validated['class_id'],
            'title' => trim((string) $validated['title']),
            'amount' => round((float) $validated['amount'], 2),
            'fee_type' => trim((string) $validated['fee_type']),
            'is_monthly' => $request->boolean('is_monthly'),
            'is_active' => $request->boolean('is_active', true),
        ])->save();

        return redirect()
            ->route('principal.fees.structures.index')
            ->with('status', 'Fee structure updated successfully.');
    }

    public function destroy(FeeStructure $feeStructure): RedirectResponse
    {
        $feeStructure->delete();

        return redirect()
            ->route('principal.fees.structures.index')
            ->with('status', 'Fee structure deleted successfully.');
    }

    private function defaultFeeTypes(): array
    {
        return [
            'tuition',
            'admission',
            'exam',
            'transport',
            'library',
            'sports',
            'miscellaneous',
        ];
    }
}
