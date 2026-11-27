<?php

namespace Tests\Feature;

use App\Models\{Exam, Mark, SchoolClass, Student, Subject, Teacher, TeacherAssignment, User};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\{Role, Permission};
use Tests\TestCase;

class PrepGradeSavingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('t', 32))]);
        $this->withoutVite();
        // Keep this fixture isolated from legacy migrations that require MySQL.
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        foreach (['0001_01_01_000000_create_users_table.php',
            '2026_03_05_093953_create_permission_tables.php',
            '2026_03_05_220000_create_notifications_table.php',
            '2026_03_05_130000_create_student_management_tables.php',
            '2026_03_05_140000_create_teacher_module_tables.php',
            '2026_05_14_000100_create_section_head_assignments_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        Schema::create('student_class_histories', function (Blueprint $table) {
            $table->id();
            $table->integer('student_id');
            $table->integer('class_id');
            $table->string('session');
        });
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            foreach (['class_id', 'subject_id', 'teacher_id', 'total_marks'] as $column) {
                $table->integer($column)->nullable();
            }
            foreach (['exam_type', 'session', 'exam_label', 'marking_mode', 'topic', 'exam_date'] as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamps();
        });
        Schema::create('marks', function (Blueprint $table) {
            $table->id();
            foreach (['exam_id', 'student_id', 'teacher_id', 'total_marks', 'obtained_marks'] as $column) {
                $table->integer($column)->nullable();
            }
            $table->string('grade')->nullable();
            $table->string('session');
            $table->timestamps();
        });
        Schema::table('exams', function (Blueprint $table) {
            $table->string('exam_group')->nullable();
            $table->integer('sequence_number')->nullable();
            $table->timestamp('locked_at')->nullable();
        });
        Schema::table('teacher_assignments', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('student_results', function (Blueprint $table) {
            $table->integer('class_id')->nullable();
            $table->integer('exam_id')->nullable();
            $table->string('session')->nullable();
        });
        foreach (['2026_04_14_000700_create_teacher_result_entry_logs_table.php',
            '2026_04_23_000200_create_result_lock_tables.php',
            '2026_11_27_000200_add_exam_correction_windows.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        $this->mock(\App\Services\TeacherPerformanceSyncService::class, function ($mock) {
            $mock->shouldReceive('syncAfterMarksChange')->andReturnNull();
        });
    }

    private function context(): array
    {
        $user = User::factory()->create();
        Role::findOrCreate('Teacher', 'web')->givePermissionTo(Permission::findOrCreate('enter_marks', 'web'));
        $user->assignRole('Teacher');
        $teacher = Teacher::create(['teacher_id' => 'PREP-T', 'user_id' => $user->id]);
        $class = SchoolClass::create(['name' => 'Prep', 'status' => 'active']);
        $subject = Subject::create(['name' => 'English', 'code' => 'PREP-E']);
        $student = Student::create(['student_id' => 'PREP-S', 'name' => 'Prep Student', 'class_id' => $class->id, 'status' => 'active']);
        TeacherAssignment::create(['teacher_id' => $teacher->id, 'class_id' => $class->id, 'subject_id' => $subject->id, 'session' => '2026-2027']);
        $payload = ['class_id' => $class->id, 'subject_id' => $subject->id, 'session' => '2026-2027', 'exam_type' => 'first_term',
            'total_marks' => null, 'records' => [['student_id' => $student->id, 'grade' => 'A', 'obtained_marks' => null]]];
        $this->actingAs($user);
        return [$payload, $student, $teacher];
    }

    public function test_prep_grades_are_saved_reloaded_and_updated_without_numeric_marks(): void
    {
        [$payload, $student] = $this->context();
        $url = route('teacher.exams.save');
        $this->postJson($url, $payload)->assertOk();
        $this->assertDatabaseHas('marks', ['student_id' => $student->id, 'grade' => 'A', 'obtained_marks' => null, 'total_marks' => null]);
        $this->getJson(route('teacher.exams.sheet', collect($payload)->except(['records', 'total_marks'])->all()))
            ->assertOk()->assertJsonPath('marking_mode', 'grade')->assertJsonPath('students.0.grade', 'A');
        $payload['exam_id'] = Exam::first()->id;
        $payload['records'][0]['grade'] = 'B';
        $this->postJson($url, $payload)->assertOk();
        $this->assertDatabaseCount('marks', 1);
        $this->assertDatabaseHas('marks', ['student_id' => $student->id, 'grade' => 'B']);
        $this->assertDatabaseHas('teacher_result_entry_logs', ['old_grade' => 'A', 'new_grade' => 'B', 'action_type' => 'updated']);
        $payload['records'][0]['grade'] = null;
        $this->postJson($url, $payload)->assertOk();
        $this->assertDatabaseCount('marks', 0);
    }

    public function test_numeric_mode_for_prep_still_saves_marks_and_rejects_grades(): void
    {
        [$payload, $student, $teacher] = $this->context();
        Exam::create(['class_id' => $payload['class_id'], 'subject_id' => $payload['subject_id'], 'teacher_id' => $teacher->id,
            'session' => $payload['session'], 'exam_type' => 'first_term', 'exam_label' => 'Midterm', 'marking_mode' => 'numeric', 'total_marks' => 50]);
        $payload['total_marks'] = 50;
        $payload['records'][0]['grade'] = null;
        $payload['records'][0]['obtained_marks'] = 42;
        $this->postJson(route('teacher.exams.save'), $payload)->assertOk();
        $this->assertDatabaseHas('marks', ['student_id' => $student->id, 'obtained_marks' => 42, 'total_marks' => 50, 'grade' => null]);
        $payload['records'][0]['grade'] = 'A';
        $this->postJson(route('teacher.exams.save'), $payload)->assertUnprocessable();
    }

    public function test_invalid_grades_and_locked_prep_results_are_not_saved(): void
    {
        [$payload, $student, $teacher] = $this->context();
        $payload['records'][0]['grade'] = 'INVALID';
        $this->postJson(route('teacher.exams.save'), $payload)->assertUnprocessable();
        $this->assertDatabaseCount('marks', 0);
        $exam = Exam::create(['class_id' => $payload['class_id'], 'subject_id' => $payload['subject_id'], 'teacher_id' => $teacher->id,
            'session' => $payload['session'], 'exam_type' => 'first_term', 'exam_label' => 'Midterm', 'marking_mode' => 'grade', 'locked_at' => now()]);
        $payload['records'][0]['grade'] = 'A';
        $payload['exam_id'] = $exam->id;
        $this->postJson(route('teacher.exams.save'), $payload)->assertUnprocessable();
        $this->assertDatabaseCount('marks', 0);
    }

    private function principal(): User
    {
        Role::findOrCreate('Principal', 'web')->givePermissionTo(Permission::findOrCreate('generate_results', 'web'));
        $user = User::factory()->create();
        $user->assignRole('Principal');
        return $user;
    }

    public function test_principal_can_reopen_old_entries_and_timer_blocks_saves_at_the_deadline(): void
    {
        [$payload, $student, $teacher] = $this->context();
        $exam = Exam::create(['class_id' => $payload['class_id'], 'subject_id' => $payload['subject_id'], 'teacher_id' => $teacher->id,
            'session' => $payload['session'], 'exam_type' => 'first_term', 'exam_label' => 'Midterm', 'marking_mode' => 'grade',
            'locked_at' => now()->subDays(10), 'created_at' => now()->subDays(20)]);
        $mark = Mark::create(['exam_id' => $exam->id, 'student_id' => $student->id, 'teacher_id' => $teacher->id,
            'session' => $payload['session'], 'grade' => 'B', 'created_at' => now()->subDays(20)]);
        $exam->forceFill(['created_at' => now()->subDays(20)])->save();
        $mark->forceFill(['created_at' => now()->subDays(20)])->save();
        $payload['exam_id'] = $exam->id;
        $this->postJson(route('teacher.exams.save'), $payload)->assertUnprocessable();
        $principal = $this->principal();
        $window = ['session' => $payload['session'], 'class_id' => $payload['class_id'], 'exam_id' => $exam->id,
            'reason' => 'Correct transcribed grade', 'correction_minutes' => 30];
        $this->actingAs($principal)->post(route('principal.result-locks.corrections.open'), $window)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertTrue($exam->fresh()->correctionsAreOpen());
        $this->get(route('principal.result-locks.index', collect($window)->only(['session', 'class_id', 'exam_id'])->all()))
            ->assertOk()->assertSee('Corrections open')->assertSee('Pakistan time');
        $this->actingAs($teacher->user)->postJson(route('teacher.exams.save'), $payload)->assertOk();
        $this->assertSame('A', $mark->fresh()->grade);
        $this->assertTrue($mark->fresh()->created_at->lt(now()->subDays(7)));
        $this->travelTo($exam->fresh()->corrections_close_at);
        $payload['records'][0]['grade'] = 'C';
        $this->postJson(route('teacher.exams.save'), $payload)->assertUnprocessable();
        $this->assertSame('A', $mark->fresh()->grade);
        $this->assertTrue($exam->fresh()->entriesAreLocked());
        $this->assertDatabaseHas('result_lock_logs', ['action' => 'unlock', 'exam_id' => $exam->id, 'performed_by' => $principal->id]);
        $this->travelBack();
    }

    public function test_indefinite_correction_window_can_be_closed_and_reopened_after_a_final_lock(): void
    {
        [$payload, $student, $teacher] = $this->context();
        $this->postJson(route('teacher.exams.save'), $payload)->assertOk();
        $exam = Exam::first();
        $principal = $this->principal();
        $window = ['session' => $payload['session'], 'class_id' => $payload['class_id'], 'exam_id' => $exam->id, 'reason' => 'Review corrections'];
        $this->actingAs($principal)->post(route('principal.result-locks.corrections.open'), $window)->assertSessionHasNoErrors();
        $this->assertNull($exam->fresh()->corrections_close_at);
        $this->travel(10)->days();
        $this->assertTrue($exam->fresh()->correctionsAreOpen());
        $this->post(route('principal.result-locks.corrections.close'), $window)->assertSessionHasNoErrors();
        $this->assertFalse($exam->fresh()->correctionsAreOpen());
        $this->assertTrue($exam->fresh()->entriesAreLocked());
        $this->assertDatabaseHas('result_locks', ['exam_id' => $exam->id, 'lock_type' => 'final', 'unlocked_at' => null]);
        $this->actingAs($teacher->user)->postJson(route('teacher.exams.save'), $payload)->assertUnprocessable();
        $this->actingAs($principal)->post(route('principal.result-locks.corrections.open'), $window)->assertSessionHasNoErrors();
        $this->assertTrue($exam->fresh()->correctionsAreOpen());
        $this->actingAs($teacher->user)->postJson(route('teacher.exams.save'), $payload)->assertOk();
        $this->travelBack();
    }

    public function test_correction_controls_reject_teachers_invalid_scopes_and_class_wide_locks(): void
    {
        [$payload, $student, $teacher] = $this->context();
        $this->postJson(route('teacher.exams.save'), $payload)->assertOk();
        $exam = Exam::first();
        $window = ['session' => $payload['session'], 'class_id' => $payload['class_id'], 'exam_id' => $exam->id, 'reason' => 'Correction'];
        $this->postJson(route('principal.result-locks.corrections.open'), $window)->assertForbidden();
        $principal = $this->principal();
        $this->actingAs($principal)->postJson(route('principal.result-locks.corrections.open'), array_merge($window, ['session' => '2020-2021']))->assertUnprocessable();
        $this->postJson(route('principal.result-locks.corrections.open'), $window + ['correction_minutes' => -1])->assertUnprocessable();
        $this->postJson(route('principal.result-locks.corrections.open'), array_merge($window, ['reason' => '']))->assertUnprocessable();
        $this->post(route('principal.result-locks.lock'), ['session' => $payload['session'], 'class_id' => $payload['class_id'], 'lock_type' => 'final'])->assertSessionHasNoErrors();
        $this->postJson(route('principal.result-locks.corrections.open'), $window)->assertUnprocessable();
        $this->assertFalse($exam->fresh()->correctionsAreOpen());
    }
}
