<?php

namespace Tests\Feature;

use App\Models\SchoolSetting;
use App\Models\User;
use App\Modules\Results\Services\ResultService;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExamPassPercentageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        config(['app.key' => 'base64:'.base64_encode(str_repeat('t', 32))]);
        $this->withoutVite();
        foreach (['0001_01_01_000000_create_users_table.php',
            '2026_03_05_093953_create_permission_tables.php',
            '2026_03_05_220000_create_notifications_table.php',
            '2026_03_05_200000_create_school_settings_table.php',
            '2026_11_26_000100_add_exam_pass_percentage_to_school_settings.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        Cache::flush();
    }

    public function test_principal_can_save_school_threshold_without_changing_federal_grade_bands(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('Principal', 'web'));
        $this->actingAs($user)->get(route('principal.exams.settings.edit'))->assertOk();
        $this->assertSame(40.0, SchoolSetting::examPassPercentage());
        $this->actingAs($user)->post(route('principal.exams.settings.update'), [
            'exam_pass_percentage' => '45.50',
        ])->assertRedirect(route('principal.exams.settings.edit'));
        $this->assertSame(45.5, SchoolSetting::examPassPercentage());
        $service = app(ResultService::class);
        $this->assertSame('U', $service->computeGrade(39.99));
        $this->assertSame('D', $service->computeGrade(40));
        $this->assertSame('D', $service->computeGrade(45.5));
        $this->assertSame('B++', $service->computeGrade(85));

        $this->post(route('principal.exams.settings.update'), ['exam_pass_percentage' => 90])->assertSessionHasNoErrors();
        $this->assertSame(90.0, SchoolSetting::examPassPercentage());
        $this->assertSame('B++', $service->computeGrade(85));
        $this->assertSame('A', $service->computeGrade(90));
    }

    public function test_invalid_percentages_are_rejected(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('Principal', 'web'));
        foreach ([-1, 101, 'abc', '', '45.555'] as $value) {
            $this->actingAs($user)->post(route('principal.exams.settings.update'), [
                'exam_pass_percentage' => $value,
            ])->assertSessionHasErrors('exam_pass_percentage');
        }
        $this->assertSame(40.0, SchoolSetting::examPassPercentage());
    }

    public function test_teacher_cannot_read_or_change_exam_settings(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('Teacher', 'web'));
        $this->actingAs($user)->get(route('principal.exams.settings.edit'))->assertForbidden();
        $this->post(route('principal.exams.settings.update'), ['exam_pass_percentage' => 20])->assertForbidden();
    }
}
