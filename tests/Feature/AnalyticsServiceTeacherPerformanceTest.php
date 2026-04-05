<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\TeacherCgpaRanking;
use App\Models\User;
use App\Modules\Analytics\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsServiceTeacherPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_performance_rows_use_ranking_snapshots_and_keep_unranked_assigned_teachers_visible(): void
    {
        $service = app(AnalyticsService::class);
        $classRoom = $this->createClass('8', 'A');
        [$teacherOne, $teacherTwo, $teacherThree] = $this->createTeachers('Adeel', 'Bilal', 'Faryal');
        [$math, $english, $science] = $this->createSubjects('Mathematics', 'English', 'Science');

        TeacherAssignment::query()->create([
            'teacher_id' => $teacherOne->id,
            'class_id' => $classRoom->id,
            'subject_id' => $math->id,
            'is_class_teacher' => false,
            'session' => '2025-2026',
        ]);
        TeacherAssignment::query()->create([
            'teacher_id' => $teacherTwo->id,
            'class_id' => $classRoom->id,
            'subject_id' => $english->id,
            'is_class_teacher' => false,
            'session' => '2025-2026',
        ]);
        TeacherAssignment::query()->create([
            'teacher_id' => $teacherThree->id,
            'class_id' => $classRoom->id,
            'subject_id' => $science->id,
            'is_class_teacher' => false,
            'session' => '2025-2026',
        ]);

        TeacherCgpaRanking::query()->create([
            'teacher_id' => $teacherOne->id,
            'session' => '2025-2026',
            'exam_type' => null,
            'class_id' => null,
            'average_percentage' => 92.50,
            'pass_percentage' => 100.00,
            'cgpa' => 5.55,
            'student_count' => 24,
            'rank_position' => 1,
            'ranking_scope' => TeacherCgpaRanking::SCOPE_OVERALL,
            'ranking_group' => TeacherCgpaRanking::GROUP_SENIOR_SCHOOL,
        ]);
        TeacherCgpaRanking::query()->create([
            'teacher_id' => $teacherTwo->id,
            'session' => '2025-2026',
            'exam_type' => null,
            'class_id' => null,
            'average_percentage' => 81.25,
            'pass_percentage' => 75.00,
            'cgpa' => 4.88,
            'student_count' => 21,
            'rank_position' => 2,
            'ranking_scope' => TeacherCgpaRanking::SCOPE_OVERALL,
            'ranking_group' => TeacherCgpaRanking::GROUP_SENIOR_SCHOOL,
        ]);

        $rows = $service->teacherPerformanceRows('2025-2026')->all();

        $this->assertCount(3, $rows);
        $this->assertSame([$teacherOne->id, $teacherTwo->id, $teacherThree->id], array_column($rows, 'teacher_id'));
        $this->assertSame([1, 2, null], array_column($rows, 'rank'));
        $this->assertSame([92.50, 81.25, null], array_column($rows, 'average_score'));
        $this->assertSame([100.00, 75.00, null], array_column($rows, 'pass_percentage'));
        $this->assertSame([0, 0, 0], array_column($rows, 'entries'));
    }

    public function test_teacher_performance_rows_use_classwise_rankings_for_class_scope(): void
    {
        $service = app(AnalyticsService::class);
        $classEight = $this->createClass('8', 'A');
        $classNine = $this->createClass('9', 'A');
        [$teacherOne, $teacherTwo, $teacherThree] = $this->createTeachers('Adeel', 'Bilal', 'Faryal');
        [$math, $english, $science] = $this->createSubjects('Mathematics', 'English', 'Science');

        TeacherAssignment::query()->create([
            'teacher_id' => $teacherOne->id,
            'class_id' => $classEight->id,
            'subject_id' => $math->id,
            'is_class_teacher' => false,
            'session' => '2025-2026',
        ]);
        TeacherAssignment::query()->create([
            'teacher_id' => $teacherOne->id,
            'class_id' => $classNine->id,
            'subject_id' => $math->id,
            'is_class_teacher' => false,
            'session' => '2025-2026',
        ]);
        TeacherAssignment::query()->create([
            'teacher_id' => $teacherTwo->id,
            'class_id' => $classEight->id,
            'subject_id' => $english->id,
            'is_class_teacher' => false,
            'session' => '2025-2026',
        ]);
        TeacherAssignment::query()->create([
            'teacher_id' => $teacherThree->id,
            'class_id' => $classEight->id,
            'subject_id' => $science->id,
            'is_class_teacher' => false,
            'session' => '2025-2026',
        ]);

        TeacherCgpaRanking::query()->create([
            'teacher_id' => $teacherOne->id,
            'session' => '2025-2026',
            'exam_type' => 'final_term',
            'class_id' => null,
            'average_percentage' => 95.00,
            'pass_percentage' => 100.00,
            'cgpa' => 5.70,
            'student_count' => 40,
            'rank_position' => 1,
            'ranking_scope' => TeacherCgpaRanking::SCOPE_OVERALL,
            'ranking_group' => TeacherCgpaRanking::GROUP_SENIOR_SCHOOL,
        ]);
        TeacherCgpaRanking::query()->create([
            'teacher_id' => $teacherTwo->id,
            'session' => '2025-2026',
            'exam_type' => 'final_term',
            'class_id' => null,
            'average_percentage' => 60.00,
            'pass_percentage' => 55.00,
            'cgpa' => 3.60,
            'student_count' => 18,
            'rank_position' => 2,
            'ranking_scope' => TeacherCgpaRanking::SCOPE_OVERALL,
            'ranking_group' => TeacherCgpaRanking::GROUP_SENIOR_SCHOOL,
        ]);
        TeacherCgpaRanking::query()->create([
            'teacher_id' => $teacherTwo->id,
            'session' => '2025-2026',
            'exam_type' => 'final_term',
            'class_id' => $classEight->id,
            'average_percentage' => 88.00,
            'pass_percentage' => 90.00,
            'cgpa' => 5.28,
            'student_count' => 22,
            'rank_position' => 1,
            'ranking_scope' => TeacherCgpaRanking::SCOPE_CLASSWISE,
            'ranking_group' => TeacherCgpaRanking::GROUP_SENIOR_SCHOOL,
        ]);
        TeacherCgpaRanking::query()->create([
            'teacher_id' => $teacherOne->id,
            'session' => '2025-2026',
            'exam_type' => 'final_term',
            'class_id' => $classEight->id,
            'average_percentage' => 70.00,
            'pass_percentage' => 68.00,
            'cgpa' => 4.20,
            'student_count' => 20,
            'rank_position' => 2,
            'ranking_scope' => TeacherCgpaRanking::SCOPE_CLASSWISE,
            'ranking_group' => TeacherCgpaRanking::GROUP_SENIOR_SCHOOL,
        ]);

        $rows = $service->teacherPerformanceRows('2025-2026', $classEight->id, 'final_term')->all();

        $this->assertCount(3, $rows);
        $this->assertSame([$teacherTwo->id, $teacherOne->id, $teacherThree->id], array_column($rows, 'teacher_id'));
        $this->assertSame([1, 2, null], array_column($rows, 'rank'));
        $this->assertSame([88.00, 70.00, null], array_column($rows, 'average_score'));
        $this->assertSame([90.00, 68.00, null], array_column($rows, 'pass_percentage'));
    }

    public function test_teacher_performance_rows_aggregate_multiple_group_rows_for_the_same_teacher(): void
    {
        $service = app(AnalyticsService::class);
        $classOne = $this->createClass('1', 'A');
        $classSeven = $this->createClass('7', 'A');
        [$teacher] = $this->createTeachers('Adeel');
        [$science, $english] = $this->createSubjects('Science', 'English');

        TeacherAssignment::query()->create([
            'teacher_id' => $teacher->id,
            'class_id' => $classOne->id,
            'subject_id' => $science->id,
            'is_class_teacher' => false,
            'session' => '2025-2026',
        ]);
        TeacherAssignment::query()->create([
            'teacher_id' => $teacher->id,
            'class_id' => $classSeven->id,
            'subject_id' => $english->id,
            'is_class_teacher' => false,
            'session' => '2025-2026',
        ]);

        TeacherCgpaRanking::query()->create([
            'teacher_id' => $teacher->id,
            'session' => '2025-2026',
            'exam_type' => null,
            'class_id' => null,
            'average_percentage' => 90.00,
            'pass_percentage' => 100.00,
            'cgpa' => 5.40,
            'student_count' => 10,
            'rank_position' => 1,
            'ranking_scope' => TeacherCgpaRanking::SCOPE_OVERALL,
            'ranking_group' => TeacherCgpaRanking::GROUP_EARLY_YEARS,
        ]);
        TeacherCgpaRanking::query()->create([
            'teacher_id' => $teacher->id,
            'session' => '2025-2026',
            'exam_type' => null,
            'class_id' => null,
            'average_percentage' => 70.00,
            'pass_percentage' => 60.00,
            'cgpa' => 4.20,
            'student_count' => 30,
            'rank_position' => 3,
            'ranking_scope' => TeacherCgpaRanking::SCOPE_OVERALL,
            'ranking_group' => TeacherCgpaRanking::GROUP_SENIOR_SCHOOL,
        ]);

        $rows = $service->teacherPerformanceRows('2025-2026')->all();

        $this->assertCount(1, $rows);
        $this->assertSame($teacher->id, $rows[0]['teacher_id']);
        $this->assertNull($rows[0]['rank']);
        $this->assertSame(75.00, $rows[0]['average_score']);
        $this->assertSame(70.00, $rows[0]['pass_percentage']);
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
     * @return array<int, Teacher>
     */
    private function createTeachers(string ...$names): array
    {
        return collect($names)->map(function (string $name, int $index): Teacher {
            $user = User::factory()->create([
                'name' => $name,
                'email' => 'analytics-teacher-'.$index.'-'.strtolower(str_replace(' ', '-', $name)).'@example.test',
            ]);

            return Teacher::query()->create([
                'teacher_id' => 'T-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'user_id' => $user->id,
                'designation' => 'Teacher',
                'employee_code' => 'EMP-'.($index + 1),
            ]);
        })->all();
    }

    /**
     * @return array<int, Subject>
     */
    private function createSubjects(string ...$names): array
    {
        return collect($names)->map(function (string $name, int $index): Subject {
            return Subject::query()->create([
                'name' => $name,
                'code' => 'SUB-'.($index + 1),
                'status' => 'active',
            ]);
        })->all();
    }
}
