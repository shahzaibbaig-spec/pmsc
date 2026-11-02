<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name' => 'view_class_wise_student_lists', 'guard_name' => 'web',
        ]);
        Role::firstOrCreate(['name' => 'Warden', 'guard_name' => 'web'])
            ->givePermissionTo($permission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $role = Role::findByName('Warden', 'web');
        $role->revokePermissionTo('view_class_wise_student_lists');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
