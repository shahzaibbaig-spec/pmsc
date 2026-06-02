<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    private const ROLE_NAME = 'School Psychiatrist';
    private const USER_EMAIL = 'maryam@kort.edu.pk';
    private const USER_NAME = 'Maryam';

    /**
     * @var array<int, string>
     */
    private array $permissions = [
        'view_school_psychiatrist_panel',
        'submit_psychiatrist_feedback',
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

        $psychiatristRole = Role::firstOrCreate([
            'name' => self::ROLE_NAME,
            'guard_name' => 'web',
        ]);

        $psychiatristRole->givePermissionTo([
            'view_school_psychiatrist_panel',
            'submit_psychiatrist_feedback',
            'view_all_student_discipline_reports',
            'view_all_sports_observations',
        ]);

        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
            ->givePermissionTo($this->permissions);

        $maryam = User::firstOrCreate(
            ['email' => self::USER_EMAIL],
            [
                'name' => self::USER_NAME,
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
                'must_change_password' => true,
                'password_changed_at' => null,
            ]
        );

        $maryam->syncRoles([self::ROLE_NAME]);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', [self::ROLE_NAME, 'Admin'])
            ->get();

        foreach ($roles as $role) {
            $assigned = array_values(array_intersect($this->permissions, $role->permissions->pluck('name')->all()));
            if ($assigned !== []) {
                $role->revokePermissionTo($assigned);
            }
        }

        $maryam = User::query()->where('email', self::USER_EMAIL)->first();
        if ($maryam && $maryam->hasRole(self::ROLE_NAME)) {
            $maryam->removeRole(self::ROLE_NAME);
        }

        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $this->permissions)
            ->delete();

        Role::query()
            ->where('name', self::ROLE_NAME)
            ->where('guard_name', 'web')
            ->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
