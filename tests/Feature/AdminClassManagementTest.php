<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminClassManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_create_and_rename_classes_without_principal_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin', 'web'));
        $this->actingAs($admin)->get(route('admin.classes.index'))
            ->assertOk()->assertSee('Add Class');

        $this->post(route('admin.classes.store'), ['name' => 'Grade 1', 'section' => 'A'])
            ->assertRedirect(route('admin.classes.index'));
        $classRoom = SchoolClass::query()->where('name', 'Grade 1')->firstOrFail();

        $this->get(route('admin.classes.index', ['edit' => $classRoom->id]))
            ->assertOk()->assertSee('Edit Class')->assertSee('Grade 1');
        $this->put(route('admin.classes.update', $classRoom), ['name' => 'Grade One', 'section' => 'B'])
            ->assertRedirect(route('admin.classes.index'));
        $this->assertDatabaseHas('school_classes', ['id' => $classRoom->id, 'name' => 'Grade One', 'section' => 'B']);

        $this->postJson(route('admin.classes.store'), ['name' => 'Grade One', 'section' => 'B'])
            ->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->putJson(route('admin.classes.update', $classRoom), ['name' => ''])
            ->assertUnprocessable()->assertJsonValidationErrors('name');
    }

    public function test_non_admin_cannot_access_admin_class_management(): void
    {
        $user = User::factory()->create();
        $classRoom = SchoolClass::query()->create(['name' => 'Grade 1', 'status' => 'active']);
        $this->actingAs($user)->get(route('admin.classes.index'))->assertForbidden();
        $this->postJson(route('admin.classes.store'), ['name' => 'Grade 2'])->assertForbidden();
        $this->putJson(route('admin.classes.update', $classRoom), ['name' => 'Renamed'])->assertForbidden();
        $this->assertSame('Grade 1', $classRoom->fresh()->name);
    }
}
