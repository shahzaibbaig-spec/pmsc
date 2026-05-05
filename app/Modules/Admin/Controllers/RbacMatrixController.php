<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RbacMatrixController extends Controller
{
    private const MATRIX_ROLES = [
        'Admin',
        'Principal',
        'Accountant',
        'Teacher',
        'Doctor',
        'Warden',
        'Student',
    ];

    private const MATRIX_PERMISSIONS = [
        'manage_users',
        'manage_school_settings',
        'assign_roles',
        'manage_subjects',
        'assign_subjects',
        'manage_subject_assignments',
        'assign_teachers',
        'manage_teacher_assignments',
        'copy_teacher_assignments',
        'view_attendance',
        'mark_attendance',
        'enter_marks',
        'view_own_mark_entries',
        'edit_own_mark_entries',
        'delete_own_mark_entries',
        'view_mark_edit_logs',
        'generate_results',
        'view_medical_requests',
        'create_medical_requests',
        'create_direct_medical_visit',
        'view_all_medical_records',
        'create_cbc_report',
        'view_cbc_report',
        'print_cbc_report',
        'view_all_cbc_reports',
        'view_teacher_performance',
        'manage_teacher_acr',
        'view_teacher_acr',
        'finalize_teacher_acr',
        'view_fee_structure',
        'create_fee_structure',
        'edit_fee_structure',
        'delete_fee_structure',
        'generate_fee_challans',
        'view_fee_challans',
        'record_fee_payment',
        'view_fee_reports',
        'view_payroll',
        'manage_payroll',
        'generate_salary_sheet',
        'view_salary_slips',
        'edit_salary_structure',
        'manage_payroll_profiles',
        'generate_payroll',
        'view_payroll_reports',
        'view_own_inventory',
        'create_inventory_demand',
        'view_own_inventory_demands',
        'review_inventory_demands',
        'fulfill_inventory_demands',
        'submit_device_declaration',
        'review_device_declarations',
        'create_daily_diary',
        'edit_own_daily_diary',
        'view_own_daily_diary_entries',
        'view_student_daily_diary',
        'view_all_daily_diary',
        'monitor_daily_diary',
        'view_student_discipline_reports',
        'view_student_academic_records',
        'view_student_profiles_basic',
        'manage_hostel_rooms',
        'assign_students_to_rooms',
        'view_hostel_room_allocations',
        'manage_hostel_leave',
        'mark_night_attendance',
        'view_night_attendance',
        'take_cognitive_assessment',
        'view_own_cognitive_results',
        'view_cognitive_assessment_reports',
        'manage_student_cognitive_assessment_access',
        'reset_student_cognitive_assessment',
        'view_cognitive_profile_reports',
        'manage_cognitive_question_banks',
        'manage_cognitive_assessment_setup',
    ];

    public function index(): View
    {
        return view('modules.admin.rbac-matrix');
    }

    public function data(): JsonResponse
    {
        foreach (self::MATRIX_ROLES as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }

        $roles = Role::query()
            ->with('permissions:id,name,guard_name')
            ->whereIn('name', self::MATRIX_ROLES)
            ->where('guard_name', 'web')
            ->get(['id', 'name'])
            ->sortBy(fn (Role $role) => array_search($role->name, self::MATRIX_ROLES, true) !== false
                ? array_search($role->name, self::MATRIX_ROLES, true)
                : 999)
            ->values();

        $permissions = collect(self::MATRIX_PERMISSIONS)
            ->map(fn (string $permission) => Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']))
            ->values();

        return response()->json([
            'roles' => $roles->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->values(),
            ])->values(),
            'permissions' => $permissions,
        ]);
    }

    public function toggle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role' => [
                'required',
                'string',
                Rule::exists('roles', 'name')->where(fn ($query) => $query->where('guard_name', 'web')),
            ],
            'permission' => ['required', 'string', Rule::in(self::MATRIX_PERMISSIONS)],
            'enabled' => ['required', 'boolean'],
        ]);

        Permission::firstOrCreate(['name' => $validated['permission'], 'guard_name' => 'web']);
        $role = Role::where('name', $validated['role'])->firstOrFail();

        if ($validated['enabled']) {
            $role->givePermissionTo($validated['permission']);
        } else {
            $role->revokePermissionTo($validated['permission']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json(['message' => 'RBAC matrix updated.']);
    }

    public function save(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'changes' => ['required', 'array', 'min:1'],
            'changes.*.role' => [
                'required',
                'string',
                Rule::exists('roles', 'name')->where(fn ($query) => $query->where('guard_name', 'web')),
            ],
            'changes.*.permission' => ['required', 'string', Rule::in(self::MATRIX_PERMISSIONS)],
            'changes.*.enabled' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($validated): void {
            foreach ($validated['changes'] as $change) {
                Permission::firstOrCreate([
                    'name' => $change['permission'],
                    'guard_name' => 'web',
                ]);

                $role = Role::query()->where('name', $change['role'])->firstOrFail();

                if ((bool) $change['enabled']) {
                    $role->givePermissionTo($change['permission']);
                } else {
                    $role->revokePermissionTo($change['permission']);
                }
            }
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'message' => 'RBAC matrix changes saved successfully.',
            'saved' => count($validated['changes']),
        ]);
    }
}
