<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClassSubjectCopyTest extends TestCase
{
    use RefreshDatabase;

    public function test_principal_can_copy_missing_subjects_between_sections_of_same_class(): void
    {
        $principal = $this->createPrincipalWithAssignSubjectsPermission();
        $sourceClass = $this->createClass('8', 'A');
        $targetClass = $this->createClass('8', 'B');

        [$math, $english, $urdu] = $this->createSubjects('Mathematics', 'English', 'Urdu');

        $sourceClass->subjects()->sync([$math->id, $english->id]);
        $targetClass->subjects()->sync([$math->id, $urdu->id]);

        $response = $this->actingAs($principal)->postJson(route('principal.classes.copy-subjects'), [
            'source_class_id' => $sourceClass->id,
            'target_class_id' => $targetClass->id,
            'copy_mode' => 'copy_missing_only',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('summary.total_source_subjects', 2)
            ->assertJsonPath('summary.copied_count', 1)
            ->assertJsonPath('summary.skipped_count', 1)
            ->assertJsonPath('summary.replaced_count', 0);

        $this->assertSame(
            [$math->id, $english->id, $urdu->id],
            $targetClass->refresh()->subjects()->pluck('subjects.id')->sort()->values()->all()
        );
    }

    public function test_principal_can_replace_target_subjects_from_source_section(): void
    {
        $principal = $this->createPrincipalWithAssignSubjectsPermission();
        $sourceClass = $this->createClass('8', 'A');
        $targetClass = $this->createClass('8', 'B');

        [$math, $english, $urdu] = $this->createSubjects('Mathematics', 'English', 'Urdu');

        $sourceClass->subjects()->sync([$math->id, $english->id]);
        $targetClass->subjects()->sync([$urdu->id]);

        $response = $this->actingAs($principal)->postJson(route('principal.classes.copy-subjects'), [
            'source_class_id' => $sourceClass->id,
            'target_class_id' => $targetClass->id,
            'copy_mode' => 'replace_target_subjects',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('summary.total_source_subjects', 2)
            ->assertJsonPath('summary.copied_count', 2)
            ->assertJsonPath('summary.skipped_count', 0)
            ->assertJsonPath('summary.replaced_count', 1);

        $this->assertSame(
            [$math->id, $english->id],
            $targetClass->refresh()->subjects()->pluck('subjects.id')->sort()->values()->all()
        );
    }

    public function test_copy_fails_for_different_class_names(): void
    {
        $principal = $this->createPrincipalWithAssignSubjectsPermission();
        $sourceClass = $this->createClass('8', 'A');
        $targetClass = $this->createClass('9', 'A');
        [$math] = $this->createSubjects('Mathematics');

        $sourceClass->subjects()->sync([$math->id]);

        $response = $this->actingAs($principal)->postJson(route('principal.classes.copy-subjects'), [
            'source_class_id' => $sourceClass->id,
            'target_class_id' => $targetClass->id,
            'copy_mode' => 'copy_missing_only',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['target_class_id']);
    }

    private function createPrincipalWithAssignSubjectsPermission(): User
    {
        $principalRole = Role::findOrCreate('Principal', 'web');
        $assignSubjectsPermission = Permission::findOrCreate('assign_subjects', 'web');
        $principalRole->givePermissionTo($assignSubjectsPermission);

        $user = User::factory()->create();
        $user->assignRole($principalRole);

        return $user;
    }

    private function createClass(string $name, string $section): SchoolClass
    {
        return SchoolClass::query()->create([
            'name' => $name,
            'section' => $section,
            'status' => 'active',
        ]);
    }

    /**
     * @return array<int, Subject>
     */
    private function createSubjects(string ...$names): array
    {
        return collect($names)->map(function (string $name, int $index): Subject {
            return Subject::query()->create([
                'name' => $name,
                'code' => 'SUB-COPY-'.($index + 1),
                'status' => 'active',
            ]);
        })->all();
    }
}
