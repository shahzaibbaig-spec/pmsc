<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'create_career_assessment',
            'view_career_assessment',
            'print_career_assessment',
            'manage_parent_meetings',
            'view_parent_meetings',
            'mark_urgent_guidance',
            'view_urgent_guidance_cases',
            'manage_career_visibility',
            'view_student_parent_career_summary',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'Career Counselor', 'guard_name' => 'web'])
            ->givePermissionTo([
                'create_career_assessment',
                'view_career_assessment',
                'print_career_assessment',
                'manage_parent_meetings',
                'view_parent_meetings',
                'mark_urgent_guidance',
                'manage_career_visibility',
            ]);

        Role::firstOrCreate(['name' => 'Principal', 'guard_name' => 'web'])
            ->givePermissionTo([
                'view_career_assessment',
                'print_career_assessment',
                'view_parent_meetings',
                'view_urgent_guidance_cases',
                'view_student_parent_career_summary',
            ]);

        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
            ->givePermissionTo($permissions);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
