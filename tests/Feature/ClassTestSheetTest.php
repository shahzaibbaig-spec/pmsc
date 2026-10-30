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
        // Keep this fixture isolated from legacy migrations that require MySQL.
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        foreach (['0001_01_01_000000_create_users_table.php',
            '2026_03_05_130000_create_student_management_tables.php',
            '2026_03_05_140000_create_teacher_module_tables.php'] as $migration) {
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
        $this->assertSame(20, $graded['rows'][0]['total']);
    }
}
