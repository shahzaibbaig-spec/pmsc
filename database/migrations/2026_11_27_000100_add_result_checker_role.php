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
        $permission = Permission::findOrCreate('view_all_student_results', 'web');
        Role::findOrCreate('Result Checker', 'web')->givePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Role::findByName('Result Checker', 'web')->delete();
        Permission::findByName('view_all_student_results', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
