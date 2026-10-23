<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\SeniorProgressReportDraft;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\SeniorProgressReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use ZipArchive;

class SeniorProgressReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('s', 32))]);
        // An isolated fixture schema avoids unrelated legacy migrations containing MySQL-only SQL.
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        foreach (['0001_01_01_000000_create_users_table.php', '2026_03_05_093953_create_permission_tables.php',
            '2026_03_05_220000_create_notifications_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('section')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('class_teacher_id')->nullable();
            $table->timestamps();
        });
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_id');
            $table->string('name');
            $table->unsignedBigInteger('class_id');
            $table->string('status');
            $table->date('date_of_birth')->nullable();
            $table->integer('age')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('student_class_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_id');
            $table->string('session');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('teacher_id');
            $table->timestamps();
        });
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('teacher_id');
            $table->string('exam_type');
            $table->string('session');
            $table->string('exam_label')->nullable();
            $table->string('exam_group')->nullable();
            $table->integer('sequence_number')->nullable();
            $table->date('exam_date')->nullable();
            $table->integer('total_marks');
            $table->timestamps();
        });
        Schema::create('marks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('teacher_id');
            $table->string('session');
            $table->integer('total_marks');
            $table->integer('obtained_marks')->nullable();
            $table->string('grade')->nullable();
            $table->timestamps();
        });
        Schema::create('teacher_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('teacher_id');
            $table->string('session');
            $table->boolean('is_class_teacher');
            $table->softDeletes();
            $table->timestamps();
        });
        (require database_path('migrations/2026_10_23_000002_create_senior_progress_report_drafts_table.php'))->up();
        config(['senior_progress.enabled' => true, 'senior_progress.campus' => 'mirpur']);
        $this->withoutVite();
    }

    public function test_only_principal_can_access_and_disabled_or_other_campus_returns_404(): void
    {
        $this->get(route('principal.senior-progress.index'))->assertRedirect(route('login'));
        foreach (['Admin', 'Teacher'] as $role) {
            $this->actingAs($this->user($role))->get(route('principal.senior-progress.index'))->assertForbidden();
        }
        $this->actingAs($this->user('Principal'));
        config(['senior_progress.enabled' => false]);
        $this->get(route('principal.senior-progress.index'))->assertNotFound();
        config(['senior_progress.enabled' => true, 'senior_progress.campus' => 'swabi']);
        $this->get(route('principal.senior-progress.index'))->assertNotFound();
    }

    public function test_preview_save_and_single_and_class_downloads_use_review_without_changing_marks(): void
    {
        [$class, $student, $exam] = $this->fixture();
        $this->actingAs($this->user('Principal'));
        $context = ['class_id' => $class->id, 'session' => '2026-2027', 'exam_id' => $exam->id];
        $this->get(route('principal.senior-progress.index', $context))->assertSee('Senior Progress Reports');
        $this->get(route('principal.senior-progress.index', $context))->assertOk()->assertSee('Ayesha');
        $this->get(route('principal.senior-progress.preview', ['student' => $student->id] + $context))
            ->assertOk()->assertSee('Mathematics')->assertSee('Skills development');
        $markBefore = Mark::first()->getAttributes();
        $this->post(route('principal.senior-progress.save', ['student' => $student->id]), $context + [
            'progress' => ['1'], 'effort' => ['2'], 'skills' => ['3', '', '1'],
            'teacher_remarks' => 'Steady progress & good focus.', 'head_remarks' => 'Continue the good work.',
        ])->assertRedirect();
        $this->assertSame($markBefore, Mark::first()->getAttributes());
        $this->assertDatabaseCount('senior_progress_report_drafts', 1);
        $this->assertSame('Continue the good work.', SeniorProgressReportDraft::first()->content['head_remarks']);

        $response = $this->get(route('principal.senior-progress.download', ['student' => $student->id] + $context));
        $response->assertOk()->assertDownload();
        $file = $response->baseResponse->getFile()->getPathname();
        $xml = $this->documentXml($file);
        $this->assertStringContainsString('Steady progress &amp; good focus.', $xml);
        $this->assertStringContainsString('Continue the good work.', $xml);
        $this->assertStringContainsString('Mathematics', $xml);
        $this->assertStringContainsString('✓', $xml);
        unlink($file);

        $second = Student::query()->create(['student_id' => 'K-002', 'name' => 'Bilal', 'class_id' => $class->id, 'status' => 'active']);
        $response = $this->post(route('principal.senior-progress.class-download'), $context);
        $response->assertOk()->assertDownload();
        $archive = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($archive));
        $this->assertSame(2, $zip->numFiles);
        $reviewedDocument = tempnam(sys_get_temp_dir(), 'reviewed-');
        file_put_contents($reviewedDocument, $zip->getFromName('k-001-ayesha-'.$student->id.'-progress.docx'));
        $this->assertStringContainsString('Continue the good work.', $this->documentXml($reviewedDocument));
        unlink($reviewedDocument);
        $this->assertNotFalse($zip->locateName('k-002-bilal-'.$second->id.'-progress.docx'));
        $zip->close();
        unlink($archive);
    }

    public function test_scope_and_roster_prevent_wrong_class_or_session_exports(): void
    {
        [$class, $student, $exam] = $this->fixture();
        $other = SchoolClass::query()->create(['name' => '8', 'section' => 'B', 'status' => 'active']);
        $junior = SchoolClass::query()->create(['name' => '6', 'status' => 'active']);
        $this->actingAs($this->user('Principal'));
        $context = ['student' => $student->id, 'class_id' => $other->id, 'session' => '2026-2027', 'exam_id' => $exam->id];
        $this->get(route('principal.senior-progress.download', $context))->assertNotFound();
        $context['class_id'] = $junior->id;
        $this->get(route('principal.senior-progress.download', $context))->assertNotFound();
        $context['class_id'] = $class->id;
        $context['session'] = '2025-2026';
        $this->get(route('principal.senior-progress.download', $context))->assertNotFound();
        $context['session'] = '2026-2027';
        StudentClassHistory::query()->create(['student_id' => $student->id, 'class_id' => $other->id, 'session' => '2026-2027', 'status' => 'active']);
        $this->get(route('principal.senior-progress.download', $context))->assertNotFound();
    }

    public function test_missing_marks_remain_blank_and_other_assessments_do_not_leak_into_report(): void
    {
        [$class, $student, $exam] = $this->fixture();
        $service = app(SeniorProgressReportService::class);
        $report = $service->report($student, $class, '2026-2027', $exam);
        $this->assertCount(1, $report['subjects']);
        $this->assertNotSame('', $report['subjects'][0]['grade']);
        $this->assertSame([], $report['skills']);
        $this->assertSame('', $report['head_remarks']);
        $other = $exam->replicate();
        $other->exam_type = 'final_term';
        $other->save();
        Mark::query()->create(['exam_id' => $other->id, 'student_id' => $student->id, 'teacher_id' => $exam->teacher_id,
            'session' => '2026-2027', 'total_marks' => 100, 'obtained_marks' => 10]);
        $this->assertSame($report['subjects'], $service->report($student, $class, '2026-2027', $exam)['subjects']);
        Mark::query()->delete();
        $this->assertSame([], $service->report($student, $class, '2026-2027', $exam)['subjects']);
    }

    public function test_invalid_ratings_are_rejected(): void
    {
        [$class, $student, $exam] = $this->fixture();
        $this->actingAs($this->user('Principal'))->post(route('principal.senior-progress.save', $student->id), [
            'class_id' => $class->id, 'session' => '2026-2027', 'exam_id' => $exam->id,
            'skills' => ['9'], 'effort' => ['4'],
        ])->assertSessionHasErrors(['skills.0', 'effort.0']);
        $this->assertDatabaseCount('senior_progress_report_drafts', 0);
    }

    public function test_duplicate_subjects_fail_instead_of_silently_exporting_an_ambiguous_grade(): void
    {
        [$class, $student, $exam] = $this->fixture();
        $duplicate = $exam->replicate();
        $duplicate->save();
        Mark::query()->create(['exam_id' => $duplicate->id, 'student_id' => $student->id,
            'teacher_id' => $exam->teacher_id, 'session' => '2026-2027', 'total_marks' => 100, 'obtained_marks' => 50]);
        $this->actingAs($this->user('Principal'))->get(route('principal.senior-progress.download', [
            'student' => $student->id, 'class_id' => $class->id, 'session' => '2026-2027', 'exam_id' => $exam->id,
        ]))->assertSessionHasErrors('exam_id');
    }

    public function test_grades_are_blank_when_both_numeric_and_letter_grades_are_unrecorded(): void
    {
        [$class, $student, $exam] = $this->fixture();
        Mark::query()->update(['obtained_marks' => null, 'grade' => null]);
        $report = app(SeniorProgressReportService::class)->report($student, $class, '2026-2027', $exam);
        $this->assertSame('', $report['subjects'][0]['grade']);
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));

        return $user;
    }

    private function fixture(): array
    {
        $class = SchoolClass::query()->create(['name' => '7', 'section' => 'A', 'status' => 'active']);
        $student = Student::query()->create(['student_id' => 'K-001', 'name' => 'Ayesha', 'class_id' => $class->id, 'status' => 'active']);
        $teacher = Teacher::query()->create(['user_id' => User::factory()->create()->id, 'teacher_id' => 'T-001']);
        $subject = Subject::query()->create(['name' => 'Mathematics', 'code' => 'MTH', 'status' => 'active']);
        $exam = Exam::query()->create(['class_id' => $class->id, 'subject_id' => $subject->id,
            'exam_type' => 'first_term', 'session' => '2026-2027', 'total_marks' => 100, 'teacher_id' => $teacher->id]);
        Mark::query()->create(['exam_id' => $exam->id, 'student_id' => $student->id, 'teacher_id' => $teacher->id,
            'session' => '2026-2027', 'total_marks' => 100, 'obtained_marks' => 85]);

        return [$class, $student, $exam];
    }

    private function documentXml(string $path): string
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        return $xml;
    }
}
