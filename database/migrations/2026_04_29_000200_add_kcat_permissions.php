<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
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

        if (DB::connection()->pretending()) {
            return;
        }

        Role::firstOrCreate(['name' => 'Career Counselor', 'guard_name' => 'web'])->givePermissionTo([
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

        Role::firstOrCreate(['name' => 'Principal', 'guard_name' => 'web'])->givePermissionTo([
            'view_all_kcat_reports',
            'print_kcat_reports',
        ]);

        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])->givePermissionTo($permissions);
        Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'web'])->givePermissionTo(['attempt_kcat_test']);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
