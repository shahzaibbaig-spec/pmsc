<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $permissions = [
        'assign_section_heads',
        'conduct_lesson_observation',
        'conduct_notebook_observation',
        'view_lesson_observations',
        'view_notebook_observations',
        'print_observations',
        'comment_on_own_observation',
        'view_teacher_performance',
        'manage_principal_teacher_communication',
        'reply_principal_teacher_communication',
    ];

    /**
     * @var array<int, string>
     */
    private array $sectionHeadRoles = [
        'Early Years Section Head',
        'Middle School Section Head',
        'Senior School Section Head',
    ];

    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($this->permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        foreach ($this->sectionHeadRoles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web'])
                ->givePermissionTo([
                    'conduct_lesson_observation',
                    'conduct_notebook_observation',
                    'view_lesson_observations',
                    'view_notebook_observations',
                    'print_observations',
                ]);
        }

        Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web'])
            ->givePermissionTo([
                'comment_on_own_observation',
                'reply_principal_teacher_communication',
            ]);

        Role::firstOrCreate(['name' => 'Principal', 'guard_name' => 'web'])
            ->givePermissionTo($this->permissions);

        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
            ->givePermissionTo($this->permissions);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', array_merge(['Principal', 'Admin', 'Teacher'], $this->sectionHeadRoles))
            ->get();

        foreach ($roles as $role) {
            $assigned = array_values(array_intersect($this->permissions, $role->permissions->pluck('name')->all()));
            if ($assigned !== []) {
                $role->revokePermissionTo($assigned);
            }
        }

        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $this->permissions)
            ->delete();

        Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $this->sectionHeadRoles)
            ->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
