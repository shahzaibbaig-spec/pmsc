<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ROLE_NAME = 'Warden';

    private const PERMISSIONS = [
        'manage_hostel_rooms',
        'assign_students_to_rooms',
        'view_hostel_room_allocations',
        'manage_hostel_leave',
        'mark_night_attendance',
        'view_night_attendance',
    ];

    private const WARDEN_PERMISSIONS = [
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
    ];

    private const ADDITIONAL_ROLE_PERMISSION_MAP = [
        'Admin' => self::PERMISSIONS,
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

        foreach (self::PERMISSIONS as $permissionName) {
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

        $requiredRoles = array_merge([self::ROLE_NAME], array_keys(self::ADDITIONAL_ROLE_PERMISSION_MAP));

        $roleMap = DB::table('roles')
            ->where('guard_name', 'web')
            ->whereIn('name', $requiredRoles)
            ->pluck('id', 'name');

        $permissionMap = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', array_values(array_unique(array_merge(self::PERMISSIONS, self::WARDEN_PERMISSIONS))))
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

        foreach (self::ADDITIONAL_ROLE_PERMISSION_MAP as $roleName => $permissionNames) {
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
            ->whereIn('name', array_merge([self::ROLE_NAME], array_keys(self::ADDITIONAL_ROLE_PERMISSION_MAP)))
            ->pluck('id', 'name');

        $permissionMap = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', self::PERMISSIONS)
            ->pluck('id', 'name');

        $wardenRoleId = $roleMap->get(self::ROLE_NAME);
        if ($wardenRoleId) {
            $permissionIds = collect(self::PERMISSIONS)
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

        foreach (self::ADDITIONAL_ROLE_PERMISSION_MAP as $roleName => $permissionNames) {
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
            ->whereIn('name', self::PERMISSIONS)
            ->delete();
    }
};
