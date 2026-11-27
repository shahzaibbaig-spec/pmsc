<?php

namespace Tests\Feature;

use App\Models\{Exam, Mark, SchoolClass, Student, StudentResult, Subject, Teacher, User};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResultCheckerAccessTest extends TestCase
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
                $table->integer($column);
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
        Schema::table('student_results', function (Blueprint $table) {
            $table->integer('class_id')->nullable();
            $table->integer('exam_id')->nullable();
            $table->string('session')->nullable();
            $table->string('grade')->nullable();
        });
        (require database_path('migrations/2026_11_27_000100_add_result_checker_role.php'))->up();
    }

    public function test_checker_sees_all_classes_and_can_filter_records_and_published_results(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Result Checker');
        $teacher = Teacher::create(['teacher_id' => 'RC-T', 'user_id' => User::factory()->create()->id]);
        $subject = Subject::create(['name' => 'Math', 'code' => 'RC-M']);
        foreach (['7', '10'] as $name) {
            $class = SchoolClass::create(['name' => $name, 'status' => 'active']);
            $student = Student::create(['name' => 'Pupil '.$name, 'student_id' => 'RC-'.$name, 'class_id' => $class->id, 'status' => 'active']);
            $exam = Exam::create(['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id,
                'exam_type' => 'class_test', 'session' => '2026-2027', 'total_marks' => 100, 'marking_mode' => 'numeric', 'exam_date' => '2026-11-01']);
            Mark::create(['student_id' => $student->id, 'exam_id' => $exam->id, 'teacher_id' => $teacher->id,
                'session' => '2026-2027', 'obtained_marks' => 80, 'total_marks' => 100]);
            StudentResult::create(['student_id' => $student->id, 'exam_id' => $exam->id, 'class_id' => $class->id,
                'subject_id' => $subject->id, 'session' => '2026-2027', 'exam_name' => 'Class Test', 'grade' => 'B+', 'obtained_marks' => 80, 'total_marks' => 100]);
        }
        $url = route('result-checker.results.index');
        $this->actingAs($user)->get($url)->assertOk()->assertSee('Pupil 7')->assertSee('Pupil 10')
            ->assertViewHas('records', fn ($records) => $records->total() === 2);
        $this->get($url.'?student=RC-10&exam_type=class_test&session=2026-2027')
            ->assertOk()->assertSee('Pupil 10')->assertDontSee('Pupil 7');
        $this->get($url.'?source=published')->assertOk()->assertSee('Pupil 7')->assertSee('Pupil 10');
        $this->get($url.'?class_id='.$class->id)->assertOk()
            ->assertViewHas('records', fn ($records) => $records->total() === 1);
        $this->get($url.'?student=Pupil&student_id='.$student->id)->assertOk()
            ->assertViewHas('records', fn ($records) => $records->total() === 1)
            ->assertSee('Pupil 10')->assertDontSee('Pupil 7');
        $this->get(route('result-checker.results.class-tests', ['class_id' => '', 'session' => '2026-2027']))
            ->assertOk()->assertViewHas('reports', fn ($reports) => $reports->count() === 2)
            ->assertViewHas('sheetRoute', 'result-checker.results.class-tests');
        $this->get(route('dashboard'))->assertRedirect($url);
    }

    public function test_role_is_read_only_and_migration_is_repeatable(): void
    {
        (require database_path('migrations/2026_11_27_000100_add_result_checker_role.php'))->up();
        $user = User::factory()->create();
        $user->assignRole('Result Checker');
        $this->assertSame(['view_all_student_results'], $user->getAllPermissions()->pluck('name')->all());
        $this->actingAs($user)->post(route('principal.results.publish'), [])->assertForbidden();
        $this->post(route('principal.exams.settings.update'), ['exam_pass_percentage' => 50])->assertForbidden();
        $this->get(route('admin.users.index'))->assertForbidden();
        $this->post(route('teacher.exams.save'), [])->assertForbidden();
    }

    public function test_other_roles_and_revoked_permission_cannot_access_checker_pages(): void
    {
        Role::findOrCreate('Teacher', 'web');
        $user = User::factory()->create();
        $user->assignRole('Teacher');
        foreach (['result-checker.results.index', 'result-checker.results.class-tests', 'result-checker.students.search'] as $route) {
            $this->actingAs($user)->get(route($route))->assertForbidden();
        }
        $user->syncRoles(['Result Checker']);
        Role::findByName('Result Checker', 'web')->revokePermissionTo('view_all_student_results');
        $user->unsetRelation('roles')->unsetRelation('permissions');
        $this->get(route('result-checker.results.index'))->assertForbidden();
        $this->getJson(route('result-checker.students.search', ['q' => 'Pupil']))->assertForbidden();
    }

    public function test_ajax_search_matches_names_and_ids_and_limits_returned_student_details(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Result Checker');
        $class = SchoolClass::create(['name' => '8', 'section' => 'A', 'status' => 'active']);
        foreach (range(1, 22) as $index) {
            Student::create(['name' => 'Same Name', 'student_id' => 'FIND-'.$index,
                'class_id' => $class->id, 'status' => 'active']);
        }
        $url = route('result-checker.students.search');
        $this->actingAs($user)->getJson($url.'?q=S')->assertOk()->assertExactJson(['students' => []]);
        $response = $this->getJson($url.'?q=Same')->assertOk()->assertJsonCount(20, 'students');
        $this->assertSame(['id', 'name', 'student_id', 'class'], array_keys($response->json('students.0')));
        $this->assertSame('8 A', $response->json('students.0.class'));
        $this->getJson($url.'?q=FIND-22')->assertOk()->assertJsonCount(1, 'students')
            ->assertJsonPath('students.0.student_id', 'FIND-22');
        $this->getJson($url.'?q=Nobody')->assertOk()->assertExactJson(['students' => []]);
        $this->getJson($url.'?q='.str_repeat('x', 151))->assertUnprocessable()->assertJsonValidationErrors('q');
    }
}
