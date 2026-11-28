<?php

namespace Tests\Feature;

use App\Models\{Exam, Mark, SchoolClass, Student, Subject, Teacher, TeacherAssignment, User};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\{Role, Permission};
use Tests\TestCase;

class ResultCompletionTest extends TestCase
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

    public function test_completion_counts_expected_students_missing_teachers_valid_grades_and_zero_marks(): void
    {
        $principal = User::factory()->create();
        Role::findOrCreate('Principal', 'web')->givePermissionTo(Permission::findOrCreate('generate_results', 'web'));
        $principal->assignRole('Principal');
        $teacherA = Teacher::create(['teacher_id' => 'COMP-A', 'user_id' => User::factory()->create(['name' => 'Teacher A'])->id]);
        $teacherB = Teacher::create(['teacher_id' => 'COMP-B', 'user_id' => User::factory()->create(['name' => 'Missing Teacher'])->id]);
        $prep = SchoolClass::create(['name' => 'Prep', 'status' => 'active']);
        $eight = SchoolClass::create(['name' => '8', 'status' => 'active']);
        $unconfigured = SchoolClass::create(['name' => '7', 'status' => 'active']);
        $math = Subject::create(['name' => 'Math', 'code' => 'COMP-M']);
        $english = Subject::create(['name' => 'English', 'code' => 'COMP-E']);
        foreach ([[$prep, $math, $teacherA], [$prep, $english, $teacherB], [$eight, $math, $teacherA]] as [$class, $subject, $teacher]) {
            TeacherAssignment::create(['teacher_id' => $teacher->id, 'class_id' => $class->id, 'subject_id' => $subject->id, 'session' => '2026-2027']);
        }
        $students = collect([$prep, $prep, $eight])->map(fn ($class, $index) => Student::create([
            'student_id' => 'COMP-'.$index, 'name' => 'Pupil '.$index, 'class_id' => $class->id, 'status' => 'active',
        ]));
        $inactive = Student::create(['student_id' => 'COMP-INACTIVE', 'name' => 'Inactive', 'class_id' => $prep->id, 'status' => 'inactive']);
        $gradeExam = Exam::create(['class_id' => $prep->id, 'subject_id' => $math->id, 'teacher_id' => $teacherA->id,
            'session' => '2026-2027', 'exam_type' => 'first_term', 'marking_mode' => 'grade', 'exam_label' => 'Midterm']);
        foreach ([[$students[0], 'A'], [$students[1], null], [$inactive, 'A']] as [$student, $grade]) {
            Mark::create(['student_id' => $student->id, 'exam_id' => $gradeExam->id, 'teacher_id' => $teacherA->id, 'session' => '2026-2027', 'grade' => $grade]);
        }
        $duplicate = Exam::create(['class_id' => $prep->id, 'subject_id' => $math->id, 'teacher_id' => $teacherA->id,
            'session' => '2026-2027', 'exam_type' => 'first_term', 'marking_mode' => 'grade', 'exam_label' => 'Midterm duplicate']);
        Mark::create(['student_id' => $students[0]->id, 'exam_id' => $duplicate->id, 'teacher_id' => $teacherA->id, 'session' => '2026-2027', 'grade' => 'A']);
        $numeric = Exam::create(['class_id' => $eight->id, 'subject_id' => $math->id, 'teacher_id' => $teacherA->id,
            'session' => '2026-2027', 'exam_type' => 'first_term', 'marking_mode' => 'numeric', 'total_marks' => 100]);
        Mark::create(['student_id' => $students[2]->id, 'exam_id' => $numeric->id, 'teacher_id' => $teacherA->id,
            'session' => '2026-2027', 'obtained_marks' => 0, 'total_marks' => 100]);
        $url = route('principal.results.completion', ['session' => '2026-2027', 'exam_type' => 'first_term']);
        $this->actingAs($principal)->get($url)->assertOk()->assertSee('Missing Teacher')->assertSee('Not started')
            ->assertSee('40.00%')->assertViewHas('report', function ($report) use ($prep, $eight, $unconfigured) {
                $this->assertSame(5, $report['overall']['expected']);
                $this->assertSame(2, $report['overall']['entered']);
                $this->assertSame(3, $report['overall']['missing']);
                $this->assertSame(40.0, $report['overall']['percentage']);
                $this->assertSame(25.0, $report['classes']->firstWhere('id', $prep->id)['percentage']);
                $this->assertSame(100.0, $report['classes']->firstWhere('id', $eight->id)['percentage']);
                $this->assertNull($report['classes']->firstWhere('id', $unconfigured->id)['percentage']);
                return true;
            });
        $this->get($url.'&class_id='.$eight->id)->assertOk()->assertDontSee('Missing Teacher')
            ->assertViewHas('report', fn ($report) => $report['overall']['percentage'] === 40.0);
        $this->get(route('principal.results.completion', ['session' => '2026-2027', 'exam_type' => 'class_test']))
            ->assertOk()->assertViewHas('needsTestContext', true)->assertViewHas('report', null);
        $this->get(route('principal.results.completion', ['session' => '2026-2027', 'exam_type' => 'class_test', 'topic' => 'Fractions', 'exam_date' => '2026-11-28']))
            ->assertOk()->assertViewHas('report', fn ($report) => $report['overall']['expected'] === 5 && $report['overall']['entered'] === 0);
        $this->get(route('principal.results.completion', ['session' => '2020-2021']))->assertOk()
            ->assertViewHas('report', fn ($report) => $report['overall']['percentage'] === null);
        Role::findOrCreate('Teacher', 'web');
        $teacherUser = $teacherA->user;
        $teacherUser->assignRole('Teacher');
        $this->actingAs($teacherUser)->get($url)->assertForbidden();
    }

    public function test_senior_subject_enrolment_limits_expected_entries_and_bimonthlies_are_scoped(): void
    {
        Schema::create('student_subject_assignments', function (Blueprint $table) {
            $table->id(); $table->integer('student_id'); $table->integer('class_id'); $table->integer('subject_id'); $table->string('session');
        });
        $class = SchoolClass::create(['name' => '9', 'status' => 'active']);
        $subject = Subject::create(['name' => 'Biology', 'code' => 'COMP-BIO']);
        $teacher = Teacher::create(['teacher_id' => 'COMP-SENIOR', 'user_id' => User::factory()->create()->id]);
        TeacherAssignment::create(['teacher_id' => $teacher->id, 'class_id' => $class->id, 'subject_id' => $subject->id, 'session' => '2026-2027']);
        $students = collect([1, 2])->map(fn ($number) => Student::create(['name' => 'Senior '.$number, 'student_id' => 'COMP-S'.$number, 'class_id' => $class->id, 'status' => 'active']));
        \Illuminate\Support\Facades\DB::table('student_subject_assignments')->insert(['student_id' => $students[0]->id, 'class_id' => $class->id, 'subject_id' => $subject->id, 'session' => '2026-2027']);
        $exam = Exam::create(['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id,
            'session' => '2026-2027', 'exam_type' => 'bimonthly_test', 'sequence_number' => 1, 'marking_mode' => 'numeric', 'total_marks' => 20]);
        foreach ($students as $student) {
            Mark::create(['student_id' => $student->id, 'exam_id' => $exam->id, 'teacher_id' => $teacher->id, 'session' => '2026-2027', 'obtained_marks' => 10, 'total_marks' => 20]);
        }
        $service = app(\App\Services\ResultCompletionService::class);
        $report = $service->report(['session' => '2026-2027', 'exam_type' => 'bimonthly_test', 'sequence_number' => 1]);
        $this->assertSame(1, $report['overall']['expected']);
        $this->assertSame(1, $report['overall']['entered']);
        $this->assertSame(100.0, $report['overall']['percentage']);
        $report = $service->report(['session' => '2026-2027', 'exam_type' => 'bimonthly_test', 'sequence_number' => 2]);
        $this->assertSame(0, $report['overall']['entered']);
        $this->assertSame(0.0, $report['overall']['percentage']);
    }
}
