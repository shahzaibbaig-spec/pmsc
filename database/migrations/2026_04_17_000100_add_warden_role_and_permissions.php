<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ROLE_NAME = 'Warden';

    private const NEW_PERMISSIONS = [
        'view_student_discipline_reports',
        'view_student_academic_records',
        'view_student_profiles_basic',
    ];

    private const WARDEN_PERMISSIONS = [
        'view_all_daily_diary',
        'view_student_discipline_reports',
        'view_student_academic_records',
        'view_student_profiles_basic',
    ];

    private const EXTRA_ROLE_PERMISSION_MAP = [
        'Admin' => self::NEW_PERMISSIONS,
        'Principal' => self::NEW_PERMISSIONS,
    ];

    public function up(): void
    {
        if (
            ! Schema::hasTable('roles')
            || ! Schema::hasTable('permissions')
            || ! Schema::hasTable('role_has_permissions')
        ) {
            return;
        }

        $now = now();

        $wardenRoleId = DB::table('roles')
            ->where('name', self::ROLE_NAME)
            ->where('guard_name', 'web')
            ->value('id');

        if (! $wardenRoleId) {
            DB::table('roles')->insert([
                'name' => self::ROLE_NAME,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $allPermissionNames = array_values(array_unique(array_merge(
            self::NEW_PERMISSIONS,
            self::WARDEN_PERMISSIONS
        )));

        foreach ($allPermissionNames as $permissionName) {
            $exists = DB::table('permissions')
                ->where('name', $permissionName)
                ->where('guard_name', 'web')
                ->exists();

            if (! $exists) {
                DB::table('permissions')->insert([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $roleMap = DB::table('roles')
            ->where('guard_name', 'web')
            ->whereIn('name', array_merge([self::ROLE_NAME], array_keys(self::EXTRA_ROLE_PERMISSION_MAP)))
            ->pluck('id', 'name');

        $permissionMap = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', $allPermissionNames)
            ->pluck('id', 'name');

        $wardenRoleId = $roleMap->get(self::ROLE_NAME);
        if ($wardenRoleId) {
            foreach (self::WARDEN_PERMISSIONS as $permissionName) {
                $permissionId = $permissionMap->get($permissionName);
                if (! $permissionId) {
                    continue;
                }

                $alreadyLinked = DB::table('role_has_permissions')
                    ->where('role_id', $wardenRoleId)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (! $alreadyLinked) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permissionId,
                        'role_id' => $wardenRoleId,
                    ]);
                }
            }
        }

        foreach (self::EXTRA_ROLE_PERMISSION_MAP as $roleName => $permissionNames) {
            $roleId = $roleMap->get($roleName);
            if (! $roleId) {
                continue;
            }

            foreach ($permissionNames as $permissionName) {
                $permissionId = $permissionMap->get($permissionName);
                if (! $permissionId) {
                    continue;
                }

                $alreadyLinked = DB::table('role_has_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (! $alreadyLinked) {
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
            ! Schema::hasTable('roles')
            || ! Schema::hasTable('permissions')
            || ! Schema::hasTable('role_has_permissions')
        ) {
            return;
        }

        $roleMap = DB::table('roles')
            ->where('guard_name', 'web')
            ->whereIn('name', array_merge([self::ROLE_NAME], array_keys(self::EXTRA_ROLE_PERMISSION_MAP)))
            ->pluck('id', 'name');

        $permissionMap = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', array_values(array_unique(array_merge(self::NEW_PERMISSIONS, self::WARDEN_PERMISSIONS))))
            ->pluck('id', 'name');

        $wardenRoleId = $roleMap->get(self::ROLE_NAME);
        if ($wardenRoleId) {
            $permissionIds = collect(self::WARDEN_PERMISSIONS)
                ->map(fn (string $permissionName): ?int => $permissionMap->get($permissionName))
                ->filter()
                ->values()
                ->all();

            if ($permissionIds !== []) {
                DB::table('role_has_permissions')
                    ->where('role_id', $wardenRoleId)
                    ->whereIn('permission_id', $permissionIds)
                    ->delete();
            }
        }

        foreach (self::EXTRA_ROLE_PERMISSION_MAP as $roleName => $permissionNames) {
            $roleId = $roleMap->get($roleName);
            if (! $roleId) {
                continue;
            }

            $permissionIds = collect($permissionNames)
                ->map(fn (string $permissionName): ?int => $permissionMap->get($permissionName))
                ->filter()
                ->values()
                ->all();

            if ($permissionIds !== []) {
                DB::table('role_has_permissions')
                    ->where('role_id', $roleId)
                    ->whereIn('permission_id', $permissionIds)
                    ->delete();
            }
        }

        DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', self::NEW_PERMISSIONS)
            ->delete();
    }
};