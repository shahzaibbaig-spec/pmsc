<?php

namespace App\Modules\Accountant\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FeeChallan;
use App\Models\FeePayment;
use App\Models\PayrollItem;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountantDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
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
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->sum('total_amount'), 2);

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

