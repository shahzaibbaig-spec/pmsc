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

    public function test_result_cards_show_scoped_analysis_actual_comments_and_published_snapshots(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Result Checker');
        Role::findOrCreate('Teacher', 'web');
        $author = User::factory()->create(['name' => 'Recorded Teacher']);
        $author->assignRole('Teacher');
        $teacher = Teacher::create(['teacher_id' => 'CARD-T', 'user_id' => $author->id]);
        $class = SchoolClass::create(['name' => '8', 'status' => 'active']);
        $subject = Subject::create(['name' => 'Card Math', 'code' => 'CARD-M']);
        $student = Student::create(['name' => 'Card Student', 'student_id' => 'CARD-S', 'class_id' => $class->id, 'status' => 'active']);
        $other = Student::create(['name' => 'Other Student', 'student_id' => 'CARD-O', 'class_id' => $class->id, 'status' => 'active']);
        $context = ['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id,
            'exam_type' => 'class_test', 'session' => '2026-2027', 'total_marks' => 100, 'marking_mode' => 'numeric', 'exam_date' => '2026-11-01'];
        $exam = Exam::create($context + ['exam_label' => 'Fractions']);
        $unrelated = Exam::create($context + ['exam_label' => 'Algebra']);
        $markData = ['teacher_id' => $teacher->id, 'session' => '2026-2027', 'total_marks' => 100];
        $mark = Mark::create($markData + ['exam_id' => $exam->id, 'student_id' => $student->id, 'obtained_marks' => 30]);
        Mark::create($markData + ['exam_id' => $exam->id, 'student_id' => $other->id, 'obtained_marks' => 90]);
        Mark::create($markData + ['exam_id' => $unrelated->id, 'student_id' => $student->id, 'obtained_marks' => 100]);
        Schema::create('report_comments', function (Blueprint $table) {
            $table->id();
            $table->integer('student_id');
            $table->integer('generated_by')->nullable();
            $table->string('session');
            $table->string('exam_type');
            $table->text('final_comment')->nullable();
            $table->boolean('is_edited')->default(false);
            $table->timestamps();
        });
        $comment = \App\Models\ReportComment::create(['student_id' => $student->id, 'session' => '2026-2027',
            'exam_type' => 'class_test', 'generated_by' => $author->id, 'is_edited' => true,
            'final_comment' => 'Practise equivalent fractions daily.']);
        $url = route('result-checker.results.card', ['source' => 'marks', 'record' => $mark->id]);
        $this->actingAs($user)->get($url)->assertOk()->assertSee('Student Result Card')->assertSee('Teacher comment')
            ->assertSee('Practise equivalent fractions daily.')->assertSee('Recorded Teacher')->assertSee('Print / Save PDF')
            ->assertViewHas('rows', fn ($rows) => $rows->count() === 1)
            ->assertViewHas('overall', 30.0)
            ->assertViewHas('teaching', fn ($teaching) => $teaching[0]['count'] === 2 && $teaching[0]['average'] === 60.0 && $teaching[0]['pass_rate'] === 50.0);
        $comment->update(['is_edited' => false]);
        $this->get($url)->assertOk()->assertSee('Saved automatic comment')->assertDontSee('<h2>Teacher comment</h2>', false);
        $comment->update(['final_comment' => '   ']);
        $this->get($url)->assertOk()->assertSee('Automatic comments for teacher review')
            ->assertSee('The student achieved 30.00%')->assertSee('below the school pass threshold')
            ->assertSee('average 60.00% and pass rate 50.00% across 2 numeric records')
            ->assertSee('Suggested teacher action:')->assertViewHas('commentGenerated', true);
        $this->assertSame('   ', $comment->fresh()->final_comment);
        $published = StudentResult::create(['student_id' => $student->id, 'class_id' => $class->id, 'exam_id' => $exam->id,
            'subject_id' => $subject->id, 'session' => '2026-2027', 'exam_name' => 'Class Test', 'grade' => 'A',
            'obtained_marks' => 88, 'total_marks' => 100]);
        $this->get(route('result-checker.results.card', ['source' => 'published', 'record' => $published->id]))
            ->assertOk()->assertSee('Published result snapshot')->assertViewHas('overall', 88.0)
            ->assertViewHas('teaching', fn ($teaching) => $teaching[0]['average'] === 88.0);
        $this->get(route('result-checker.results.card', ['source' => 'marks', 'record' => 99999]))->assertNotFound();
        $this->actingAs($author)->get($url)->assertForbidden();
    }

    public function test_grade_only_card_generates_cautious_comments_without_inventing_numeric_scores(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Result Checker');
        $class = SchoolClass::create(['name' => 'KG', 'status' => 'active']);
        $subject = Subject::create(['name' => 'Reading', 'code' => 'GRADE-R']);
        $teacher = Teacher::create(['teacher_id' => 'GRADE-T', 'user_id' => User::factory()->create()->id]);
        $student = Student::create(['name' => 'Grade Student', 'student_id' => 'GRADE-S', 'class_id' => $class->id, 'status' => 'active']);
        $exam = Exam::create(['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id,
            'exam_type' => 'first_term', 'session' => '2026-2027', 'marking_mode' => 'grade', 'total_marks' => 100]);
        $mark = Mark::create(['exam_id' => $exam->id, 'student_id' => $student->id, 'teacher_id' => $teacher->id,
            'session' => '2026-2027', 'grade' => 'A', 'obtained_marks' => null, 'total_marks' => null]);
        $this->actingAs($user)->get(route('result-checker.results.card', ['source' => 'marks', 'record' => $mark->id]))
            ->assertOk()->assertViewHas('overall', null)->assertViewHas('numericCount', 0)
            ->assertSee('Automatic comments for teacher review')->assertSee('Recorded grade A.')
            ->assertSee('A numeric performance judgement cannot be made')->assertViewHas('commentGenerated', true)
            ->assertSee('Numeric class outcomes are unavailable');
    }

    public function test_consolidated_midterm_sheet_includes_every_subject_and_student_beyond_one_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Result Checker');
        $class = SchoolClass::create(['name' => '9', 'status' => 'active']);
        $teacher = Teacher::create(['teacher_id' => 'SHEET-T', 'user_id' => User::factory()->create()->id]);
        $context = ['class_id' => $class->id, 'teacher_id' => $teacher->id, 'exam_type' => 'first_term',
            'session' => '2026-2027', 'marking_mode' => 'numeric', 'exam_label' => 'Midterm'];
        $math = Subject::create(['name' => 'Sheet Math', 'code' => 'SHEET-M']);
        $english = Subject::create(['name' => 'Sheet English', 'code' => 'SHEET-E']);
        $mathExam = Exam::create($context + ['subject_id' => $math->id, 'total_marks' => 100]);
        $englishExam = Exam::create($context + ['subject_id' => $english->id, 'total_marks' => 50]);
        foreach (range(1, 26) as $index) {
            $student = Student::create(['name' => 'Sheet Student '.$index, 'student_id' => 'SHEET-'.$index,
                'class_id' => $class->id, 'status' => 'active']);
            foreach ([[$mathExam, 60, 100], [$englishExam, 40, 50]] as [$exam, $score, $total]) {
                Mark::create(['exam_id' => $exam->id, 'student_id' => $student->id, 'teacher_id' => $teacher->id,
                    'session' => '2026-2027', 'obtained_marks' => $score, 'total_marks' => $total]);
            }
        }
        $otherExam = Exam::create(array_merge($context, ['session' => '2025-2026', 'subject_id' => $math->id, 'total_marks' => 100]));
        $resit = Exam::create(array_merge($context, ['exam_label' => 'Midterm Resit', 'subject_id' => $math->id, 'total_marks' => 100]));
        $final = Exam::create(array_merge($context, ['exam_type' => 'final_term', 'exam_label' => 'Final only', 'subject_id' => $math->id, 'total_marks' => 100]));
        foreach ([$otherExam, $resit, $final] as $exam) {
            Mark::create(['exam_id' => $exam->id, 'student_id' => $student->id, 'teacher_id' => $teacher->id,
                'session' => $exam->session, 'obtained_marks' => 99, 'total_marks' => 100]);
        }
        $url = route('result-checker.results.index', ['exam_type' => 'first_term']);
        $this->actingAs($user)->get($url)->assertOk()->assertSee('Consolidated Midterm Results')
            ->assertSee('Sheet Student 1')->assertSee('Sheet Student 26')->assertDontSee('Final only')
            ->assertSee('Automatic comments for teacher review')->assertSee('Print / Save PDF')
            ->assertViewHas('sheet', function ($sheet) {
                $this->assertCount(3, $sheet);
                $group = $sheet->first(fn ($item) => $item['cards']->count() === 26);
                $this->assertCount(2, $group['subjects']);
                $this->assertSame(100.0, $group['cards'][0]['obtained']);
                $this->assertSame(150.0, $group['cards'][0]['total']);
                return true;
            });
        $this->get($url.'&session=2026-2027&student_id='.$student->id)->assertOk()
            ->assertViewHas('sheet', function ($sheet) {
                $this->assertCount(2, $sheet);
                $card = $sheet[0]['cards'][0];
                $this->assertSame(26, $card['teaching'][0]['count']);
                return true;
            });
        foreach ([[$mathExam, $math, 60, 100], [$englishExam, $english, 40, 50]] as [$exam, $subject, $score, $total]) {
            StudentResult::create(['student_id' => $student->id, 'class_id' => $class->id, 'subject_id' => $subject->id,
                'exam_id' => $exam->id, 'session' => '2026-2027', 'exam_name' => 'Published Midterm',
                'obtained_marks' => $score, 'total_marks' => $total, 'grade' => 'B']);
        }
        $this->get($url.'&source=published')->assertOk()->assertSee('Published Midterm')
            ->assertViewHas('sheet', fn ($sheet) => $sheet->count() === 1 && $sheet[0]['cards'][0]['total'] === 150.0);
    }
}
