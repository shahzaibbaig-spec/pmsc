<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERMISSIONS = [
        'create_daily_diary',
        'edit_own_daily_diary',
        'view_own_daily_diary_entries',
        'view_student_daily_diary',
        'view_all_daily_diary',
        'monitor_daily_diary',
    ];

    private const ROLE_PERMISSION_MAP = [
        'Teacher' => [
            'create_daily_diary',
            'edit_own_daily_diary',
            'view_own_daily_diary_entries',
        ],
        'Student' => [
            'view_student_daily_diary',
        ],
        'Principal' => [
            'view_all_daily_diary',
            'monitor_daily_diary',
        ],
        'Admin' => [
            'view_all_daily_diary',
            'monitor_daily_diary',
        ],
    ];

    public function up(): void
    {
        if (
            ! Schema::hasTable('permissions')
            || ! Schema::hasTable('roles')
            || ! Schema::hasTable('role_has_permissions')
        ) {
            return;
        }

        $now = now();
        foreach (self::PERMISSIONS as $permission) {
            $exists = DB::table('permissions')
                ->where('name', $permission)
                ->where('guard_name', 'web')
                ->exists();

            if (! $exists) {
                DB::table('permissions')->insert([
                    'name' => $permission,
                    'guard_name' => 'web',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $roleMap = DB::table('roles')
            ->where('guard_name', 'web')
            ->whereIn('name', array_keys(self::ROLE_PERMISSION_MAP))
            ->pluck('id', 'name');

        $permissionMap = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', self::PERMISSIONS)
            ->pluck('id', 'name');

        foreach (self::ROLE_PERMISSION_MAP as $roleName => $permissionNames) {
            $roleId = $roleMap->get($roleName);
            if (! $roleId) {
                continue;
            }

            foreach ($permissionNames as $permissionName) {
                $permissionId = $permissionMap->get($permissionName);
                if (! $permissionId) {
                    continue;
                }

                $linkExists = DB::table('role_has_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (! $linkExists) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permissionId,
                        'role_id' => $roleId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (
            ! Schema::hasTable('permissions')
            || ! Schema::hasTable('role_has_permissions')
        ) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', self::PERMISSIONS)
            ->pluck('id')
            ->all();

        if ($permissionIds !== []) {
            DB::table('role_has_permissions')
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }

        DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', self::PERMISSIONS)
            ->delete();
    }
};

