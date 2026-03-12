<?php

namespace App\Modules\Accountant\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FeeChallan;
use App\Models\FeePayment;
use App\Models\PayrollItem;
use App\Models\Student;
use App\Modules\Fees\Services\FeeManagementService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountantDashboardController extends Controller
{
    public function __invoke(Request $request, FeeManagementService $feeManagementService): View
    {
        $feeManagementService->processLateFees();

        $currentMonth = now()->format('Y-m');
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        $totalStudents = Student::query()
            ->where(function ($query): void {
                $query->where('status', 'active')
                    ->orWhereNull('status');
            })
            ->count();

        $pendingFees = round((float) FeeChallan::query()
            ->withSum('payments as paid_amount', 'amount_paid')
            ->whereIn('status', ['unpaid', 'partial', 'partially_paid'])
            ->get(['id', 'total_amount'])
            ->sum(function (FeeChallan $challan): float {
                $total = (float) $challan->total_amount;
                $paid = (float) ($challan->paid_amount ?? 0);

                return max($total - $paid, 0);
            }), 2);

        $monthlyPayroll = round((float) PayrollItem::query()
            ->whereHas('payrollRun', function ($query) use ($currentMonth): void {
                $query->where('month', $currentMonth);
            })
            ->sum('net_salary'), 2);

        $recentPaymentsCount = FeePayment::query()
            ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->count();

        $recentPayments = FeePayment::query()
            ->with(['challan.student:id,name,student_id'])
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->limit(8)
            ->get(['id', 'fee_challan_id', 'amount_paid', 'payment_date', 'payment_method', 'reference_no']);

        return view('modules.accountant.dashboard', [
            'stats' => [
                'total_students' => $totalStudents,
                'pending_fees' => $pendingFees,
                'monthly_payroll' => $monthlyPayroll,
                'recent_payments' => $recentPaymentsCount,
            ],
            'recentPayments' => $recentPayments,
            'currentMonthLabel' => now()->format('F Y'),
        ]);
    }
}
