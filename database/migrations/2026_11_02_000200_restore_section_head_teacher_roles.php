<?php

use App\Models\SectionHeadAssignment;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $teacherRole = Role::findOrCreate('Teacher', 'web');

        User::whereHas('roles', fn ($query) => $query
            ->where('guard_name', 'web')
            ->whereIn('name', array_keys(SectionHeadAssignment::TYPE_SCOPE_MAP)))
            ->chunkById(100, function ($users) use ($teacherRole): void {
                foreach ($users as $user) {
                    $user->assignRole($teacherRole);
                }
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Teacher access remains valid when a section head assignment ends.
    }
};
