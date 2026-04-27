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
            'view_career_counselor_panel',
            'create_career_profile',
            'update_career_profile',
            'view_career_profile',
            'create_counseling_session',
            'view_counseling_sessions',
            'view_all_career_records',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $careerCounselor = Role::firstOrCreate(['name' => 'Career Counselor', 'guard_name' => 'web']);
        $careerCounselor->givePermissionTo([
            'view_career_counselor_panel',
            'create_career_profile',
            'update_career_profile',
            'view_career_profile',
            'create_counseling_session',
            'view_counseling_sessions',
        ]);

        Role::firstOrCreate(['name' => 'Principal', 'guard_name' => 'web'])
            ->givePermissionTo(['view_all_career_records']);

        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
            ->givePermissionTo($permissions);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
