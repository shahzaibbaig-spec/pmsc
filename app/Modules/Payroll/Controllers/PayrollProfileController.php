<?php

namespace App\Modules\Payroll\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PayrollProfile;
use App\Modules\Payroll\Requests\StorePayrollProfileRequest;
use App\Modules\Payroll\Requests\UpdatePayrollProfileRequest;
use App\Modules\Payroll\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollProfileController extends Controller
{
    public function __construct(private readonly PayrollService $service)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:active,inactive'],
            'search' => ['nullable', 'string', 'max:150'],
        ]);

        $profiles = PayrollProfile::query()
            ->with([
                'user:id,name,email,status',
                'allowancesRows:id,payroll_profile_id,title,amount',
                'deductionsRows:id,payroll_profile_id,title,amount',
            ])
            ->when(($filters['status'] ?? null) !== null && $filters['status'] !== '', function ($query) use ($filters): void {
                $query->where('status', (string) $filters['status']);
            })
            ->when(($filters['search'] ?? null) !== null && trim((string) $filters['search']) !== '', function ($query) use ($filters): void {
                $search = trim((string) $filters['search']);
                $query->whereHas('user', function ($subQuery) use ($search): void {
                    $subQuery
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('modules.principal.payroll.profiles.index', [
            'profiles' => $profiles,
            'eligibleUsers' => $this->service->eligibleUsers(),
            'filters' => [
                'status' => $filters['status'] ?? '',
                'search' => $filters['search'] ?? '',
            ],
            'canManage' => $request->user()?->can('manage_payroll') ?? false,
            'canEdit' => $request->user()?->can('edit_salary_structure') ?? false,
        ]);
    }

    public function store(StorePayrollProfileRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->service->createProfile(
            [
                'user_id' => (int) $validated['user_id'],
                'basic_salary' => round((float) $validated['basic_salary'], 2),
                'allowances' => round((float) ($validated['allowances'] ?? 0), 2),
                'deductions' => round((float) ($validated['deductions'] ?? 0), 2),
                'bank_name' => isset($validated['bank_name']) ? trim((string) $validated['bank_name']) : null,
                'account_no' => isset($validated['account_no']) ? trim((string) $validated['account_no']) : null,
                'status' => (string) ($validated['status'] ?? 'active'),
            ],
            $validated['allowance_items'] ?? [],
            $validated['deduction_items'] ?? [],
        );

        return redirect()
            ->route('principal.payroll.profiles.index')
            ->with('status', 'Payroll profile created successfully.');
    }

    public function edit(PayrollProfile $payrollProfile): View
    {
        $payrollProfile->load([
            'user:id,name,email,status',
            'allowancesRows:id,payroll_profile_id,title,amount',
            'deductionsRows:id,payroll_profile_id,title,amount',
        ]);

        return view('modules.principal.payroll.profiles.edit', [
            'profile' => $payrollProfile,
            'eligibleUsers' => $this->service->eligibleUsers(),
        ]);
    }

    public function update(UpdatePayrollProfileRequest $request, PayrollProfile $payrollProfile): RedirectResponse
    {
        $validated = $request->validated();

        $this->service->updateProfile(
            $payrollProfile,
            [
                'user_id' => (int) $validated['user_id'],
                'basic_salary' => round((float) $validated['basic_salary'], 2),
                'allowances' => round((float) ($validated['allowances'] ?? 0), 2),
                'deductions' => round((float) ($validated['deductions'] ?? 0), 2),
                'bank_name' => isset($validated['bank_name']) ? trim((string) $validated['bank_name']) : null,
                'account_no' => isset($validated['account_no']) ? trim((string) $validated['account_no']) : null,
                'status' => (string) ($validated['status'] ?? 'active'),
            ],
            $validated['allowance_items'] ?? [],
            $validated['deduction_items'] ?? [],
        );

        return redirect()
            ->route('principal.payroll.profiles.index')
            ->with('status', 'Payroll profile updated successfully.');
    }
}
