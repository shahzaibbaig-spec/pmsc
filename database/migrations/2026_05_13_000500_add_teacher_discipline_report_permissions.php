<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERMISSIONS = [
        'create_student_discipline_report',
        'view_own_student_discipline_reports',
        'view_all_student_discipline_reports',
        'acknowledge_student_discipline_report',
        'resolve_student_discipline_report',
        'print_student_discipline_reports',
    ];

    private const ROLE_PERMISSION_MAP = [
        'Teacher' => [
            'create_student_discipline_report',
            'view_own_student_discipline_reports',
        ],
        'Principal' => [
            'view_all_student_discipline_reports',
            'acknowledge_student_discipline_report',
            'resolve_student_discipline_report',
            'print_student_discipline_reports',
        ],
        'Admin' => self::PERMISSIONS,
        'Warden' => [
            'view_all_student_discipline_reports',
            'acknowledge_student_discipline_report',
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

        $roleIds = DB::table('roles')
            ->where('guard_name', 'web')
            ->whereIn('name', array_keys(self::ROLE_PERMISSION_MAP))
            ->pluck('id', 'name');

        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', self::PERMISSIONS)
            ->pluck('id', 'name');

        foreach (self::ROLE_PERMISSION_MAP as $roleName => $permissionNames) {
            $roleId = $roleIds->get($roleName);
            if (! $roleId) {
                continue;
            }

            foreach ($permissionNames as $permissionName) {
                $permissionId = $permissionIds->get($permissionName);
                if (! $permissionId) {
                    continue;
                }

                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (! $exists) {
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

