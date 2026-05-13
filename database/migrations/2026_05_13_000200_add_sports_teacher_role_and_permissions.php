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
        'view_class_wise_student_lists',
        'view_sports_teacher_panel',
        'create_sports_observation',
        'view_own_sports_observations',
        'view_all_sports_observations',
        'print_sports_observations',
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

        $sportsTeacher = Role::firstOrCreate(['name' => 'Sports Teacher', 'guard_name' => 'web']);
        $sportsTeacher->givePermissionTo([
            'view_sports_teacher_panel',
            'create_sports_observation',
            'view_own_sports_observations',
        ]);

        Role::firstOrCreate(['name' => 'Principal', 'guard_name' => 'web'])
            ->givePermissionTo([
                'view_class_wise_student_lists',
                'view_all_sports_observations',
                'print_sports_observations',
            ]);

        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
            ->givePermissionTo($this->permissions);

        Role::firstOrCreate(['name' => 'Warden', 'guard_name' => 'web'])
            ->givePermissionTo(['view_all_sports_observations']);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['Sports Teacher', 'Principal', 'Admin', 'Warden'])
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
            ->where('name', 'Sports Teacher')
            ->where('guard_name', 'web')
            ->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
