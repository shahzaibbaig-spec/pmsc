<?php

namespace Tests\Feature;

use App\Models\CognitiveAssessmentAttempt;
use App\Models\CognitiveAssessmentAttemptReset;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\CognitiveAssessmentReportService;
use App\Services\CognitiveAssessmentService;
use Database\Seeders\CognitiveAssessmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CognitiveAssessmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_scores_a_completed_level_4_attempt_automatically(): void
    {
        $this->seed(CognitiveAssessmentSeeder::class);

        $service = app(CognitiveAssessmentService::class);
        $student = $this->eligibleStudent('10', 'A');
        $this->enableStudent($service, $student);
        $attempt = $service->startAttempt($student);
        $attemptView = $service->buildAttemptViewData($attempt);

        $responses = collect($attemptView['sections'])
            ->flatMap(fn (array $section) => collect($section['questions']))
            ->map(fn (array $question): array => [
                'question_id' => $question['question_id'],
                'bank_question_id' => $question['bank_question_id'],
                'selected_answer' => $question['correct_answer'],
            ])
            ->values()
            ->all();

        $service->saveResponses($attempt, $responses);
        $gradedAttempt = $service->submitAttempt($attempt);

        $this->assertSame(CognitiveAssessmentAttempt::STATUS_GRADED, $gradedAttempt->status);
        $this->assertSame(10, $gradedAttempt->verbal_score);
        $this->assertSame(10, $gradedAttempt->non_verbal_score);
        $this->assertSame(10, $gradedAttempt->quantitative_score);
        $this->assertSame(10, $gradedAttempt->spatial_score);
        $this->assertSame(40, $gradedAttempt->overall_score);
        $this->assertSame('100.00', (string) $gradedAttempt->overall_percentage);
        $this->assertSame('Very Strong', $gradedAttempt->performance_band);

        $result = $service->buildStudentResult($gradedAttempt);

        $this->assertSame(4, count($result['sections']));
        $this->assertSame(40, $result['summary']['overall_score']);
        $this->assertSame('Very Strong', $result['summary']['performance_band']);
    }

    public function test_attempt_view_data_uses_bank_question_response_keys(): void
    {
        $this->seed(CognitiveAssessmentSeeder::class);

        $service = app(CognitiveAssessmentService::class);
        $student = $this->eligibleStudent('9', 'B');
        $this->enableStudent($service, $student);
        $attempt = $service->startAttempt($student);
        $attemptView = $service->buildAttemptViewData($attempt);

        $questions = collect($attemptView['sections'])
            ->flatMap(fn (array $section) => collect($section['questions']))
            ->values();

        $this->assertNotEmpty($questions);
        $this->assertTrue($questions->every(fn (array $question): bool => str_contains((string) $question['response_key'], ':')));
        $this->assertTrue($questions->contains(fn (array $question): bool => $question['source_type'] === 'bank'));
    }

    public function test_it_rejects_students_outside_grades_8_to_12(): void
    {
        $this->seed(CognitiveAssessmentSeeder::class);

        $service = app(CognitiveAssessmentService::class);
        $student = $this->eligibleStudent('7', 'A');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Only students in Grades 8, 9, 10, 11, and 12 can take');

        $service->startAttempt($student);
    }

    public function test_it_auto_submits_expired_attempts(): void
    {
        $this->seed(CognitiveAssessmentSeeder::class);

        $service = app(CognitiveAssessmentService::class);
        $student = $this->eligibleStudent('11', 'B');
        $this->enableStudent($service, $student);
        $attempt = $service->startAttempt($student);

        $attempt->forceFill([
            'expires_at' => now()->subMinute(),
        ])->save();

        $summary = $service->autoSubmitExpiredAttempts();
        $attempt->refresh();

        $this->assertSame(1, $summary['expired_attempts']);
        $this->assertSame(1, $summary['auto_submitted']);
        $this->assertSame(CognitiveAssessmentAttempt::STATUS_GRADED, $attempt->status);
        $this->assertSame('Needs Support', $attempt->performance_band);
        $this->assertNotNull($attempt->submitted_at);
    }

    public function test_submit_route_finishes_the_assessment_and_redirects_to_result(): void
    {
        $this->seed(CognitiveAssessmentSeeder::class);

        $service = app(CognitiveAssessmentService::class);
        $student = $this->eligibleStudent('8', 'A');
        $this->enableStudent($service, $student);
        $user = User::factory()->create([
            'name' => 'Student 8A',
            'email' => 'student8a@pmsc.edu.pk',
        ]);

        $student->forceFill([
            'name' => 'Student 8A',
            'student_id' => 'student8a',
        ])->save();

        $role = Role::query()->firstOrCreate(['name' => 'Student', 'guard_name' => 'web']);
        Permission::query()->firstOrCreate(['name' => 'take_cognitive_assessment', 'guard_name' => 'web']);
        Permission::query()->firstOrCreate(['name' => 'view_own_cognitive_results', 'guard_name' => 'web']);
        $user->assignRole($role);
        $user->givePermissionTo(['take_cognitive_assessment', 'view_own_cognitive_results']);

        $attempt = $service->startAttempt($student);
        $attemptView = $service->buildAttemptViewData($attempt);
        $answers = collect($attemptView['sections'])
            ->flatMap(fn (array $section) => collect($section['questions']))
            ->mapWithKeys(fn (array $question): array => [
                (string) $question['response_key'] => $question['correct_answer'],
            ])
            ->all();

        $response = $this->actingAs($user)->post(
            route('student.assessments.cognitive-skills-level-4.submit', $attempt),
            ['answers' => $answers]
        );

        $attempt->refresh();

        $response->assertRedirect(route('student.assessments.cognitive-skills-level-4.result', $attempt));
        $this->assertSame(CognitiveAssessmentAttempt::STATUS_GRADED, $attempt->status);
        $this->assertSame('Very Strong', $attempt->performance_band);
    }

    public function test_it_requires_principal_enablement_before_student_can_start(): void
    {
        $this->seed(CognitiveAssessmentSeeder::class);

        $service = app(CognitiveAssessmentService::class);
        $student = $this->eligibleStudent('8', 'B');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('only after the Principal enables it');

        $service->startAttempt($student);
    }

    public function test_student_assessment_pages_remain_hidden_until_principal_enables_access(): void
    {
        $this->seed(CognitiveAssessmentSeeder::class);

        $service = app(CognitiveAssessmentService::class);
        $student = $this->eligibleStudent('8', 'C');
        $user = $this->studentPortalUser($student, 'student8c@pmsc.edu.pk');

        $assessmentIndexResponse = $this->actingAs($user)->get(route('student.assessments.index'));
        $assessmentIndexResponse
            ->assertOk()
            ->assertSee('No assessment is available in your panel right now')
            ->assertSee('after the Principal enables it')
            ->assertDontSee('Open Assessment')
            ->assertDontSee('Start Assessment');

        $this->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertDontSee('Cognitive Skills Assessment Test Level 4');

        $this->actingAs($user)
            ->get(route('student.assessments.cognitive-skills-level-4.index'))
            ->assertForbidden();

        $this->enableStudent($service, $student);

        $this->actingAs($user)
            ->get(route('student.assessments.cognitive-skills-level-4.index'))
            ->assertOk()
            ->assertSee('Cognitive Skills Assessment Test Level 4')
            ->assertSee('Start Assessment');

        $this->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Cognitive Skills Assessment Test Level 4');
    }

    public function test_reset_marks_previous_attempt_as_reset_and_allows_fresh_attempt(): void
    {
        $this->seed(CognitiveAssessmentSeeder::class);

        $service = app(CognitiveAssessmentService::class);
        $student = $this->eligibleStudent('12', 'A');
        $principal = User::factory()->create([
            'name' => 'Principal User',
            'email' => 'principal-test@pmsc.edu.pk',
        ]);

        $this->enableStudent($service, $student, $principal);
        $firstAttempt = $service->startAttempt($student);
        $service->resetStudentAssessment((int) $firstAttempt->assessment_id, (int) $student->id, (int) $principal->id, 'Reset for retake');

        $firstAttempt->refresh();

        $this->assertSame(CognitiveAssessmentAttempt::STATUS_RESET, $firstAttempt->status);
        $this->assertDatabaseHas('cognitive_assessment_attempt_resets', [
            'attempt_id' => $firstAttempt->id,
            'student_id' => $student->id,
        ]);
        $this->assertSame(1, CognitiveAssessmentAttemptReset::query()->count());

        $secondAttempt = $service->startAttempt($student);

        $this->assertNotSame($firstAttempt->id, $secondAttempt->id);
        $this->assertSame(CognitiveAssessmentAttempt::STATUS_IN_PROGRESS, $secondAttempt->status);
    }

    public function test_profile_report_service_generates_interpretation_and_pathway(): void
    {
        $this->seed(CognitiveAssessmentSeeder::class);

        $assessmentService = app(CognitiveAssessmentService::class);
        $reportService = app(CognitiveAssessmentReportService::class);
        $student = $this->eligibleStudent('9', 'A');
        $this->enableStudent($assessmentService, $student);
        $attempt = $assessmentService->startAttempt($student);
        $attemptView = $assessmentService->buildAttemptViewData($attempt);

        $responses = collect($attemptView['sections'])
            ->flatMap(fn (array $section) => collect($section['questions']))
            ->map(fn (array $question): array => [
                'question_id' => $question['question_id'],
                'bank_question_id' => $question['bank_question_id'],
                'selected_answer' => $question['correct_answer'],
            ])
            ->values()
            ->all();

        $assessmentService->saveResponses($attempt, $responses);
        $attempt = $assessmentService->submitAttempt($attempt);
        $report = $reportService->buildStudentProfileReport((int) $attempt->id);

        $this->assertSame('Balanced academic pathway', $report['pathway']['pathway']);
        $this->assertNotEmpty($report['interpretation']['summary_paragraph']);
        $this->assertNotEmpty($report['interpretation']['strengths']);
        $this->assertSame(40, $report['summary']['overall_score']);
    }

    private function eligibleStudent(string $className, string $section): Student
    {
        $classRoom = SchoolClass::query()->create([
            'name' => $className,
            'section' => $section,
            'status' => 'active',
        ]);

        return Student::query()->create([
            'student_id' => 'STD-'.$className.$section,
            'name' => 'Student '.$className.$section,
            'class_id' => $classRoom->id,
            'status' => 'active',
        ]);
    }

    private function enableStudent(CognitiveAssessmentService $service, Student $student, ?User $principal = null): void
    {
        $principal ??= User::factory()->create([
            'name' => 'Principal Seeder',
            'email' => 'principal-seeder-'.uniqid().'@pmsc.edu.pk',
        ]);

        $assessment = $service->resolveAssessment();
        $service->enableAssessmentForStudent((int) $assessment->id, (int) $student->id, (int) $principal->id, 'Enabled for testing');
    }

    private function studentPortalUser(Student $student, string $email): User
    {
        $user = User::factory()->create([
            'name' => $student->name,
            'email' => $email,
        ]);

        $student->forceFill([
            'name' => $user->name,
            'student_id' => strtolower((string) strtok($email, '@')),
        ])->save();

        $role = Role::query()->firstOrCreate(['name' => 'Student', 'guard_name' => 'web']);
        Permission::query()->firstOrCreate(['name' => 'take_cognitive_assessment', 'guard_name' => 'web']);
        Permission::query()->firstOrCreate(['name' => 'view_own_cognitive_results', 'guard_name' => 'web']);

        $user->assignRole($role);
        $user->givePermissionTo(['take_cognitive_assessment', 'view_own_cognitive_results']);

        return $user;
    }
}
