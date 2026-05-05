<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('permission:copy_teacher_assignments|manage_teacher_assignments|assign_teachers')
            ->get('/_test/permission-pipe', static fn () => response('ok'))
            ->name('test.permission-pipe');
    }

    public function test_pipe_delimited_permissions_allow_access_when_user_has_any_single_permission(): void
    {
        Permission::query()->create([
            'name' => 'assign_teachers',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('assign_teachers');

        $this->actingAs($user)
            ->get(route('test.permission-pipe'))
            ->assertOk();
    }

    public function test_pipe_delimited_permissions_still_block_access_when_user_has_none(): void
    {
        Permission::query()->create([
            'name' => 'assign_teachers',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('test.permission-pipe'))
            ->assertForbidden();
    }
}
