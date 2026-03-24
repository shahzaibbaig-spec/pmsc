<?php

namespace App\Modules\Fees\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FeeInstallment;
use App\Models\FeeInstallmentPlan;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Modules\Fees\Services\FeeManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class FeeInstallmentPlanController extends Controller
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

        $classes = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        $sessions = FeeInstallmentPlan::query()
            ->select('session')
            ->distinct()
            ->orderByDesc('session')
            ->pluck('session')
            ->values()
            ->all();
        if (empty($sessions)) {
            $sessions = $this->service->sessionOptions();
        }

        $selectedSession = (string) ($filters['session'] ?? ($sessions[1] ?? ($sessions[0] ?? now()->year.'-'.(now()->year + 1))));
        $selectedClassId = $filters['class_id'] ?? '';
        $search = trim((string) ($filters['search'] ?? ''));

        $plans = FeeInstallmentPlan::query()
            ->with([
                'student:id,name,student_id,class_id',
                'student.classRoom:id,name,section',
                'installments:id,fee_installment_plan_id,installment_no,due_date,amount,paid_amount,status,paid_at,title',
                'creator:id,name',
            ])
            ->when($selectedSession !== '', function ($builder) use ($selectedSession): void {
                $builder->where('session', $selectedSession);
            })
            ->when($selectedClassId !== '' && $selectedClassId !== null, function ($builder) use ($selectedClassId): void {
                $builder->whereHas('student', function ($studentQuery) use ($selectedClassId): void {
                    $studentQuery->where('class_id', (int) $selectedClassId);
                });
            })
            ->when($search !== '', function ($builder) use ($search): void {
                $builder->whereHas('student', function ($studentQuery) use ($search): void {
                    $studentQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('student_id', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $students = Student::query()
            ->with('classRoom:id,name,section')
            ->where('status', 'active')
            ->when($selectedClassId !== '' && $selectedClassId !== null, function ($builder) use ($selectedClassId): void {
                $builder->where('class_id', (int) $selectedClassId);
            })
            ->when($search !== '', function ($builder) use ($search): void {
                $builder->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', '%'.$search.'%')
                        ->orWhere('student_id', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->limit(250)
            ->get(['id', 'name', 'student_id', 'class_id']);

        return view('modules.principal.fees.installment-plans.index', [
            'classes' => $classes,
            'sessions' => $sessions,
            'selectedSession' => $selectedSession,
            'plans' => $plans,
            'students' => $students,
            'filters' => [
                'session' => $selectedSession,
                'class_id' => $selectedClassId,
                'search' => $search,
            ],
            'planTypes' => $this->service->installmentPlanTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'session' => ['required', 'string', 'max:20'],
            'plan_name' => ['nullable', 'string', 'max:150'],
            'plan_type' => ['required', 'in:monthly,quarterly,custom'],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'number_of_installments' => ['required', 'integer', 'min:1', 'max:120'],
            'first_due_date' => ['required', 'date'],
            'custom_interval_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'deactivate_existing' => ['nullable', 'boolean'],
        ]);

        try {
            $this->service->createInstallmentPlan([
                'student_id' => (int) $validated['student_id'],
                'session' => (string) $validated['session'],
                'plan_name' => $validated['plan_name'] ?? null,
                'plan_type' => (string) $validated['plan_type'],
                'total_amount' => (float) $validated['total_amount'],
                'number_of_installments' => (int) $validated['number_of_installments'],
                'first_due_date' => (string) $validated['first_due_date'],
                'custom_interval_days' => isset($validated['custom_interval_days']) ? (int) $validated['custom_interval_days'] : null,
                'notes' => $validated['notes'] ?? null,
                'is_active' => true,
                'deactivate_existing' => $request->boolean('deactivate_existing', true),
            ], (int) ($request->user()?->id ?? 0));
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('principal.fees.installment-plans.index', $request->only(['session', 'class_id', 'search']))
            ->with('status', 'Installment plan created and schedule generated successfully.');
    }

    public function payInstallment(Request $request, FeeInstallment $feeInstallment): RedirectResponse
    {
        $validated = $request->validate([
            'amount_paid' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            $this->service->recordInstallmentPayment(
                $feeInstallment,
                (float) $validated['amount_paid']
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Installment payment recorded successfully.');
    }
}

