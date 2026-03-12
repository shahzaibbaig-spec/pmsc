<?php

namespace App\Modules\Fees\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FeeChallan;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Modules\Fees\Requests\RecordFeePaymentRequest;
use App\Modules\Fees\Services\FeeManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class FeePaymentController extends Controller
{
    public function __construct(private readonly FeeManagementService $service)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'session' => ['nullable', 'string', 'max:20'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'status' => ['nullable', 'in:pending,paid,all'],
            'student_name' => ['nullable', 'string', 'max:150'],
            'challan_number' => ['nullable', 'string', 'max:40'],
        ]);

        $status = (string) ($filters['status'] ?? 'pending');

        $query = FeeChallan::query()
            ->with([
                'student:id,name,student_id',
                'classRoom:id,name,section',
            ])
            ->withSum('payments as paid_amount', 'amount_paid')
            ->when(($filters['session'] ?? null) !== null && $filters['session'] !== '', function ($builder) use ($filters): void {
                $builder->where('session', (string) $filters['session']);
            })
            ->when(($filters['class_id'] ?? null) !== null && $filters['class_id'] !== '', function ($builder) use ($filters): void {
                $builder->where('class_id', (int) $filters['class_id']);
            })
            ->when(($filters['month'] ?? null) !== null && $filters['month'] !== '', function ($builder) use ($filters): void {
                $builder->where('month', (string) $filters['month']);
            })
            ->when($status === 'pending', function ($builder): void {
                $builder->whereIn('status', ['unpaid', 'partially_paid']);
            })
            ->when($status === 'paid', function ($builder): void {
                $builder->where('status', 'paid');
            })
            ->when(($filters['student_name'] ?? null) !== null && trim((string) $filters['student_name']) !== '', function ($builder) use ($filters): void {
                $studentName = trim((string) $filters['student_name']);
                $builder->whereHas('student', function ($subQuery) use ($studentName): void {
                    $subQuery->where('name', 'like', '%'.$studentName.'%');
                });
            })
            ->when(($filters['challan_number'] ?? null) !== null && trim((string) $filters['challan_number']) !== '', function ($builder) use ($filters): void {
                $builder->where('challan_number', 'like', '%'.trim((string) $filters['challan_number']).'%');
            })
            ->orderBy('due_date')
            ->orderByDesc('id');

        $challans = $query->paginate(15)->withQueryString();
        $challans->getCollection()->transform(function (FeeChallan $challan): FeeChallan {
            $paid = (float) ($challan->paid_amount ?? 0);
            $total = (float) $challan->total_amount;
            $challan->setAttribute('paid_amount', round($paid, 2));
            $challan->setAttribute('remaining_amount', round(max($total - $paid, 0), 2));
            $challan->setAttribute('month_label', $this->service->monthLabel((string) $challan->month));

            return $challan;
        });

        $classes = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        $sessions = FeeChallan::query()
            ->select('session')
            ->distinct()
            ->orderByDesc('session')
            ->pluck('session')
            ->values()
            ->all();
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
            $sessions = $this->service->sessionOptions();
        }

        return view('modules.principal.fees.payments.index', [
            'challans' => $challans,
            'classes' => $classes,
            'sessions' => $sessions,
            'filters' => [
                'session' => $filters['session'] ?? '',
                'class_id' => $filters['class_id'] ?? '',
                'month' => $filters['month'] ?? '',
                'status' => $status,
                'student_name' => $filters['student_name'] ?? '',
                'challan_number' => $filters['challan_number'] ?? '',
            ],
            'defaultPaymentDate' => now()->toDateString(),
        ]);
    }

    public function store(RecordFeePaymentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $challan = FeeChallan::query()->findOrFail((int) $validated['challan_id']);

        try {
            $this->service->recordPayment(
                $challan,
                (float) $validated['amount_paid'],
                (string) $validated['payment_date'],
                (int) $request->user()->id,
                $validated['payment_method'] ?? null,
                $validated['reference_no'] ?? null,
                $validated['notes'] ?? null,
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('principal.fees.payments.index')
            ->with('status', 'Fee payment recorded successfully.');
    }
}
