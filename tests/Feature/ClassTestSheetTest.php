<?php

namespace Tests\Feature;

use App\Models\{Exam, Mark, SchoolClass, Student, Subject, Teacher, User};
use App\Modules\Results\Services\ClassTestSheetService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ClassTestSheetTest extends TestCase
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
    }

    public function test_section_heads_can_view_only_their_assigned_section_and_session(): void
    {
        $classes = collect(['Prep', '4', '8'])->map(fn ($name) => SchoolClass::create([
            'name' => $name, 'section' => 'A', 'status' => 'active',
        ]));
        foreach (\App\Models\SectionHeadAssignment::TYPE_SCOPE_MAP as $index => $scope) {
            \Spatie\Permission\Models\Role::findOrCreate($index, 'web');
            $user = User::factory()->create();
            $user->assignRole($index);
            $teacher = Teacher::create(['teacher_id' => 'T-'.$user->id, 'user_id' => $user->id]);
            $assignment = \App\Models\SectionHeadAssignment::create([
                'teacher_id' => $teacher->id, 'user_id' => $user->id, 'section_head_type' => $index,
                'scope' => $scope, 'session' => '2026-2027', 'status' => 'active',
            ]);
            $position = array_search($scope, array_values(\App\Models\SectionHeadAssignment::TYPE_SCOPE_MAP), true);
            $class = $classes[$position];
            $url = route('section-head.results.class-tests', ['session' => '2026-2027', 'class_id' => $class->id]);
            $this->actingAs($user)->get($url)->assertOk()
                ->assertViewHas('classes', fn ($visible) => $visible->pluck('id')->all() === [$class->id])
                ->assertViewHas('sheetRoute', 'section-head.results.class-tests')
                ->assertSee('href="'.route('section-head.results.class-tests').'"', false)
                ->assertSee('action="'.route('section-head.results.class-tests').'"', false);
            $this->get(route('section-head.results.class-tests', ['session' => '2026-2027', 'class_id' => $classes[($position + 1) % 3]->id]))->assertForbidden();
            $this->get(route('section-head.results.class-tests', ['session' => '2025-2026']))->assertOk()
                ->assertViewHas('report', null);
            $this->get(route('principal.results.class-tests'))->assertForbidden();
            $this->post(route('principal.results.publish'), [])->assertForbidden();
            $assignment->update(['status' => 'inactive']);
            $this->get($url)->assertForbidden();
        }
    }

    public function test_regular_teachers_cannot_access_section_head_sheets_and_admin_access_is_preserved(): void
    {
        \Spatie\Permission\Models\Role::findOrCreate('Teacher', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');
        $this->actingAs($teacher)->get(route('section-head.results.class-tests'))->assertForbidden();
        \Spatie\Permission\Models\Permission::findOrCreate('generate_results', 'web');
        $class = SchoolClass::create(['name' => '8', 'status' => 'active']);
        foreach (['Admin', 'Principal'] as $role) {
            \Spatie\Permission\Models\Role::findOrCreate($role, 'web')->givePermissionTo('generate_results');
            $user = User::factory()->create();
            $user->assignRole($role);
            $this->actingAs($user)->get(route('principal.results.class-tests', ['class_id' => $class->id]))->assertOk()
                ->assertViewHas('sheetRoute', 'principal.results.class-tests');
        }
    }

    public function test_month_filters_and_progress_compare_matching_subjects_and_handle_missing_results(): void
    {
        $class = SchoolClass::create(['name' => '8', 'section' => 'A', 'status' => 'active']);
        $teacher = Teacher::create(['teacher_id' => 'T-1', 'user_id' => User::factory()->create()->id]);
        $students = collect(['Improved', 'Declined', 'Steady', 'Missing'])->map(fn ($name) => Student::create([
            'student_id' => $name, 'name' => $name, 'class_id' => $class->id, 'status' => 'active',
        ]));
        $math = Subject::create(['name' => 'Math', 'code' => 'M']);
        $english = Subject::create(['name' => 'English', 'code' => 'E']);
        $add = function ($subject, $date, $total, $values) use ($class, $teacher, $students) {
            $exam = Exam::create(['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id,
                'exam_type' => 'class_test', 'session' => '2026-2027', 'exam_date' => $date,
                'total_marks' => $total, 'marking_mode' => 'numeric']);
            foreach ($values as $index => $value) {
                Mark::create(['exam_id' => $exam->id, 'student_id' => $students[$index]->id, 'teacher_id' => $teacher->id,
                    'session' => '2026-2027', 'obtained_marks' => $value, 'total_marks' => $total]);
            }
        };
        $add($math, '2026-12-10', 20, [10, 18, 10]);
        $add($math, '2027-01-05', 100, [80, 60, 50, 70]);
        $add($math, '2027-01-20', 10, [6, 6, 5]);
        // A subject only present in January must not distort progress.
        $add($english, '2027-01-10', 100, [0, 100, 100]);
        // Undated tests stay in All tests but must not affect the trend.
        $add($math, null, 100, [0, 0, 0]);
        $service = app(ClassTestSheetService::class);
        $sheet = $service->sheet($class->id, '2026-2027', null, '2027-01');
        $this->assertCount(3, $sheet['columns']);
        $this->assertSame(['2027-01', '2026-12'], $sheet['months']->pluck('value')->all());
        $rows = $sheet['rows']->keyBy(fn ($row) => $row['student']->name);
        $this->assertSame(['0.00%', '80.00%', '60.00%'], $rows['Improved']['percentage_cells']);
        $this->assertSame('Improved', $rows['Improved']['progress']['status']);
        $this->assertSame(20.0, $rows['Improved']['progress']['change']);
        $this->assertSame(-30.0, $rows['Declined']['progress']['change']);
        $this->assertSame('Declined', $rows['Declined']['progress']['status']);
        $this->assertSame('Steady', $rows['Steady']['progress']['status']);
        $this->assertSame('Not enough data', $rows['Missing']['progress']['status']);
        $this->assertSame('Dec 2026', $rows['Improved']['progress']['previous']);
        $this->assertCount(1, $rows['Improved']['progress']['subjects']);
        $all = $service->sheet($class->id, '2026-2027', $students[0]->id);
        $this->assertCount(5, $all['columns']);
        $this->assertSame(20.0, $all['rows'][0]['progress']['change']);
        $this->assertNull($sheet['analysis']);
        $this->assertNotNull($all['analysis']);
        $monthly = $service->sheet($class->id, '2026-2027', $students[0]->id, '2027-01');
        $subjects = collect($monthly['analysis']['subjects'])->keyBy('name');
        $this->assertSame('Priority support', $subjects['English']['priority']);
        $this->assertSame(0.0, $subjects['English']['average']);
        $this->assertSame(70.0, $subjects['Math']['average']);
        $this->assertSame('Review lower-scoring tests', $subjects['Math']['priority']);
        $this->assertStringContainsString('50.0% to 70.0%', $subjects['Math']['comment']);
        $this->assertCount(1, $subjects['Math']['topics']);
        $empty = $service->sheet($class->id, '2026-2027', $students[0]->id, '2027-02');
        $this->assertSame([], $empty['analysis']['subjects']);
        $this->assertStringContainsString('no usable numeric results', $empty['analysis']['summary']);
        $this->assertCount(0, $service->sheet($class->id, '2026-2027', null, '2027-02')['columns']);

        // With a gap, All tests compares available months; a selected month uses its calendar predecessor.
        $add($math, '2027-03-10', 100, [90]);
        $this->assertSame(20.0, $service->sheet($class->id, '2026-2027', $students[0]->id)['rows'][0]['progress']['change']);
        $this->assertNull($service->sheet($class->id, '2026-2027', $students[0]->id, '2027-03')['rows'][0]['progress']['change']);
    }

    public function test_sheet_keeps_every_test_and_subject_and_can_select_one_student(): void
    {
        $class = SchoolClass::create(['name' => '8', 'section' => 'A', 'status' => 'active']);
        $teacher = Teacher::create(['teacher_id' => 'T-1', 'user_id' => User::factory()->create()->id,
            'designation' => 'Teacher', 'employee_code' => 'EMP-1']);
        $alice = Student::create(['student_id' => 'S-1', 'name' => 'Alice', 'class_id' => $class->id, 'status' => 'active']);
        Student::create(['student_id' => 'S-2', 'name' => 'Bob', 'class_id' => $class->id, 'status' => 'active']);
        foreach (['English', 'Mathematics'] as $index => $name) {
            $subject = Subject::create(['name' => $name, 'code' => 'SUB-'.$index, 'status' => 'active']);
            foreach (['First test', 'Second test'] as $test => $label) {
                $exam = Exam::create(['class_id' => $class->id, 'subject_id' => $subject->id,
                    'teacher_id' => $teacher->id, 'exam_type' => 'class_test', 'exam_label' => $label,
                    'session' => '2026-2027', 'total_marks' => 20, 'marking_mode' => 'numeric']);
                if ($test === 0) {
                    Mark::create(['exam_id' => $exam->id, 'student_id' => $alice->id,
                        'teacher_id' => $teacher->id, 'session' => '2026-2027', 'obtained_marks' => 0, 'total_marks' => 20]);
                }
            }
            Exam::create(['class_id' => $class->id, 'subject_id' => $subject->id,
                'teacher_id' => $teacher->id, 'exam_type' => 'final_term',
                'session' => '2026-2027', 'total_marks' => 100]);
        }
        $service = app(ClassTestSheetService::class);
        $sheet = $service->sheet($class->id, '2026-2027');
        $this->assertCount(4, $sheet['columns']);
        $this->assertCount(2, $sheet['rows']);
        $this->assertSame(['0 / 20', '—', '0 / 20', '—'], $sheet['rows'][0]['cells']);
        $this->assertSame(['0.00%', '—', '0.00%', '—'], $sheet['rows'][0]['percentage_cells']);
        $this->assertSame(40, $sheet['rows'][0]['total']);
        $this->assertSame(2, $sheet['rows'][0]['recorded']);
        $this->assertSame(0.0, $sheet['rows'][0]['percentage']);
        $this->assertNull($sheet['rows'][1]['percentage']);
        $this->assertCount(1, $service->sheet($class->id, '2026-2027', $alice->id)['rows']);
        $this->assertCount(0, $service->sheet($class->id, '2025-2026')['columns']);

        // Historical enrollment must keep a promoted student's old tests available.
        \Illuminate\Support\Facades\DB::table('student_class_histories')->insert([
            'student_id' => $alice->id, 'class_id' => $class->id, 'session' => '2026-2027',
        ]);
        $nextClass = SchoolClass::create(['name' => '9', 'section' => 'A', 'status' => 'active']);
        $alice->update(['class_id' => $nextClass->id]);
        $historical = $service->sheet($class->id, '2026-2027', $alice->id);
        $this->assertSame($alice->id, $historical['rows'][0]['student']->id);
        $this->assertSame(2, $historical['rows'][0]['recorded']);

        // Grade results remain visible and do not enter the numeric total.
        $class->update(['name' => 'Prep']);
        $firstExam = Exam::where('exam_type', 'class_test')->orderBy('id')->first();
        $firstExam->update(['marking_mode' => 'grade']);
        Mark::where('exam_id', $firstExam->id)->update(['obtained_marks' => null, 'grade' => 'A']);
        $graded = $service->sheet($class->id, '2026-2027', $alice->id);
        $this->assertSame('A', $graded['rows'][0]['cells'][0]);
        $this->assertSame('A', $graded['rows'][0]['percentage_cells'][0]);
        $this->assertSame(20, $graded['rows'][0]['total']);
        $this->assertStringContainsString('1 grade result(s)', $graded['analysis']['summary']);
        $this->assertStringContainsString('2 test result(s) are not entered', $graded['analysis']['summary']);
        $this->assertCount(1, $graded['analysis']['subjects']);
    }
}
