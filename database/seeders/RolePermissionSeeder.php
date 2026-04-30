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
            'bulk_print_teacher_acr',
            'export_teacher_acr_summary',
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
            'take_cognitive_assessment',
            'view_own_cognitive_results',
            'view_cognitive_assessment_reports',
            'manage_student_cognitive_assessment_access',
            'reset_student_cognitive_assessment',
            'view_cognitive_profile_reports',
            'manage_cognitive_question_banks',
            'manage_cognitive_assessment_setup',
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
            'view_career_counselor_panel',
            'create_career_profile',
            'update_career_profile',
            'view_career_profile',
            'create_counseling_session',
            'view_counseling_sessions',
            'view_all_career_records',
            'create_career_assessment',
            'view_career_assessment',
            'print_career_assessment',
            'manage_parent_meetings',
            'view_parent_meetings',
            'mark_urgent_guidance',
            'view_urgent_guidance_cases',
            'manage_career_visibility',
            'view_student_parent_career_summary',
            'view_kcat_panel',
            'manage_kcat_tests',
            'manage_kcat_questions',
            'assign_kcat_tests',
            'attempt_kcat_test',
            'manually_enter_kcat_attempt',
            'view_kcat_reports',
            'print_kcat_reports',
            'view_all_kcat_reports',
            'manage_kcat_report_notes',
            'attach_kcat_to_career_profile',
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
        $warden = Role::firstOrCreate(['name' => 'Warden', 'guard_name' => 'web']);
        $careerCounselor = Role::firstOrCreate(['name' => 'Career Counselor', 'guard_name' => 'web']);

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
            'view_all_medical_records',
            'view_all_cbc_reports',
            'print_cbc_report',
            'view_teacher_performance',
            'manage_teacher_acr',
            'view_teacher_acr',
            'finalize_teacher_acr',
            'bulk_print_teacher_acr',
            'export_teacher_acr_summary',
            'review_inventory_demands',
            'fulfill_inventory_demands',
            'review_device_declarations',
            'view_cognitive_assessment_reports',
            'manage_student_cognitive_assessment_access',
            'reset_student_cognitive_assessment',
            'view_cognitive_profile_reports',
            'view_all_daily_diary',
            'monitor_daily_diary',
            'view_student_discipline_reports',
            'view_student_academic_records',
            'view_student_profiles_basic',
            'view_all_career_records',
            'view_career_assessment',
            'print_career_assessment',
            'view_parent_meetings',
            'view_urgent_guidance_cases',
            'view_student_parent_career_summary',
            'view_all_kcat_reports',
            'print_kcat_reports',
        ]);
        $teacher->syncPermissions([
            'view_attendance',
            'mark_attendance',
            'enter_marks',
            'view_own_mark_entries',
            'edit_own_mark_entries',
            'delete_own_mark_entries',
            'create_daily_diary',
            'edit_own_daily_diary',
            'view_own_daily_diary_entries',
            'view_own_inventory',
            'create_inventory_demand',
            'view_own_inventory_demands',
            'submit_device_declaration',
        ]);
        $doctor->syncPermissions([
            'view_medical_requests',
            'create_direct_medical_visit',
            'create_cbc_report',
            'view_cbc_report',
            'print_cbc_report',
        ]);
        $student->syncPermissions([
            'view_student_daily_diary',
            'take_cognitive_assessment',
            'view_own_cognitive_results',
            'attempt_kcat_test',
        ]);
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
        $warden->syncPermissions([
            'view_all_daily_diary',
            'view_student_discipline_reports',
            'view_student_academic_records',
            'view_student_profiles_basic',
            'manage_hostel_rooms',
            'assign_students_to_rooms',
            'view_hostel_room_allocations',
            'manage_hostel_leave',
            'mark_night_attendance',
            'view_night_attendance',
        ]);
        $careerCounselor->syncPermissions([
            'view_career_counselor_panel',
            'create_career_profile',
            'update_career_profile',
            'view_career_profile',
            'create_counseling_session',
            'view_counseling_sessions',
            'create_career_assessment',
            'view_career_assessment',
            'print_career_assessment',
            'manage_parent_meetings',
            'view_parent_meetings',
            'mark_urgent_guidance',
            'manage_career_visibility',
            'view_kcat_panel',
            'manage_kcat_tests',
            'manage_kcat_questions',
            'assign_kcat_tests',
            'manually_enter_kcat_attempt',
            'view_kcat_reports',
            'print_kcat_reports',
            'manage_kcat_report_notes',
            'attach_kcat_to_career_profile',
        ]);

        $this->createUserWithRole('System Admin', 'admin@pmsc.edu.pk', 'Admin');
        $this->createUserWithRole('School Principal', 'principal@pmsc.edu.pk', 'Principal');
        $this->createUserWithRole('Class Teacher', 'teacher@pmsc.edu.pk', 'Teacher');
        $this->createUserWithRole('School Doctor', 'doctor@pmsc.edu.pk', 'Doctor');
        $this->createUserWithRole('Student User', 'student@pmsc.edu.pk', 'Student');
        $this->createUserWithRole('School Accountant', 'accountant@pmsc.edu.pk', 'Accountant');
        $this->createUserWithRole('School Warden', 'warden@pmsc.edu.pk', 'Warden');
        $this->createUserWithRole('Career Counselor', 'career.counselor@pmsc.edu.pk', 'Career Counselor');
    }

    private function createUserWithRole(string $name, string $email, string $role): void
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'must_change_password' => $role === 'Teacher',
                'password_changed_at' => $role === 'Teacher' ? null : now(),
            ]
        );

        if ($role === 'Teacher' && ! $user->must_change_password) {
            $user->forceFill([
                'must_change_password' => true,
                'password_changed_at' => null,
            ])->save();
        }

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }
    }
}
