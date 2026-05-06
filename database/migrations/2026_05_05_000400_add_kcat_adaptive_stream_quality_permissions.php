<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERMISSIONS = [
        'manage_kcat_adaptive_settings',
        'attempt_adaptive_kcat',
        'view_kcat_stream_recommendations',
        'override_kcat_stream_recommendation',
        'view_kcat_question_quality',
        'review_kcat_questions',
        'retire_kcat_questions',
        'view_kcat_question_analytics',
    ];

    private const ROLE_PERMISSION_MAP = [
        'Career Counselor' => [
            'manage_kcat_adaptive_settings',
            'view_kcat_stream_recommendations',
            'override_kcat_stream_recommendation',
            'view_kcat_question_quality',
            'review_kcat_questions',
        ],
        'Principal' => [
            'view_kcat_stream_recommendations',
            'view_kcat_question_analytics',
        ],
        'Admin' => self::PERMISSIONS,
        'Student' => [
            'attempt_adaptive_kcat',
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
            if (DB::table('permissions')->where('name', $permission)->where('guard_name', 'web')->exists()) {
                continue;
            }

            DB::table('permissions')->insert([
                'name' => $permission,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $roleMap = DB::table('roles')
            ->where('guard_name', 'web')
            ->whereIn('name', array_keys(self::ROLE_PERMISSION_MAP))
            ->pluck('id', 'name');

        $permissionMap = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', self::PERMISSIONS)
            ->pluck('id', 'name');

        foreach (self::ROLE_PERMISSION_MAP as $roleName => $permissions) {
            $roleId = $roleMap->get($roleName);
            if (! $roleId) {
                continue;
            }

            foreach ($permissions as $permissionName) {
                $permissionId = $permissionMap->get($permissionName);
                if (! $permissionId) {
                    continue;
                }

                if (
                    DB::table('role_has_permissions')
                        ->where('role_id', $roleId)
                        ->where('permission_id', $permissionId)
                        ->exists()
                ) {
                    continue;
                }

                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
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

