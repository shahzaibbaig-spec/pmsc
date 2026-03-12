<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage_users',
            'manage_school_settings',
            'assign_roles',
            'manage_subjects',
            'assign_subjects',
            'manage_subject_assignments',
            'assign_teachers',
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
            'view_teacher_performance',
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
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $principal = Role::firstOrCreate(['name' => 'Principal', 'guard_name' => 'web']);
        $teacher = Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);
        $doctor = Role::firstOrCreate(['name' => 'Doctor', 'guard_name' => 'web']);
        $student = Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'web']);
        $accountant = Role::firstOrCreate(['name' => 'Accountant', 'guard_name' => 'web']);

        $admin->syncPermissions($permissions);
        $principal->syncPermissions([
            'manage_subjects',
            'assign_subjects',
            'manage_subject_assignments',
            'assign_teachers',
            'view_attendance',
            'view_mark_edit_logs',
            'generate_results',
            'view_medical_requests',
            'create_medical_requests',
            'view_teacher_performance',
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
        ]);
        $teacher->syncPermissions([
            'view_attendance',
            'mark_attendance',
            'enter_marks',
            'view_own_mark_entries',
            'edit_own_mark_entries',
            'delete_own_mark_entries',
        ]);
        $doctor->syncPermissions(['view_medical_requests']);
        $student->syncPermissions([]);
        $accountant->syncPermissions([
            'view_fee_structure',
            'create_fee_structure',
            'edit_fee_structure',
            'delete_fee_structure',
            'generate_fee_challans',
            'view_fee_challans',
            'record_fee_payment',
            'view_fee_reports',
            'view_payroll',
            'manage_payroll_profiles',
            'generate_payroll',
            'view_salary_slips',
            'view_payroll_reports',
        ]);

        $this->createUserWithRole('System Admin', 'admin@school.test', 'Admin');
        $this->createUserWithRole('School Principal', 'principal@school.test', 'Principal');
        $this->createUserWithRole('Class Teacher', 'teacher@school.test', 'Teacher');
        $this->createUserWithRole('School Doctor', 'doctor@school.test', 'Doctor');
        $this->createUserWithRole('Student User', 'student@school.test', 'Student');
        $this->createUserWithRole('School Accountant', 'accountant@school.test', 'Accountant');
    }

    private function createUserWithRole(string $name, string $email, string $role): void
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }
    }
}
