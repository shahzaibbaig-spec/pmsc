<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\TeacherAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TeacherAssignmentSectionCopyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_copy_missing_only_preserves_existing_target_subject_allocations(): void
    {
        $service = app(TeacherAssignmentService::class);
        $sourceClass = $this->createClass('7', 'A');
        $targetClass = $this->createClass('7', 'B');
        [$teacherOne, $teacherTwo] = $this->createTeachers('Adeel', 'Bilal');
        [$math, $english] = $this->createSubjects('Mathematics', 'English');
        $user = User::factory()->create();

        $classTeacherAssignment = TeacherAssignment::query()->create([
            'teacher_id' => $teacherOne->id,
            'class_id' => $sourceClass->id,
            'subject_id' => null,
            'is_class_teacher' => true,
            'session' => '2026-2027',
        ]);
        TeacherAssignment::query()->create([
            'teacher_id' => $teacherOne->id,
            'class_id' => $sourceClass->id,
            'subject_id' => $math->id,
            'is_class_teacher' => false,
            'session' => '2026-2027',
        ]);
        TeacherAssignment::query()->create([
            'teacher_id' => $teacherTwo->id,
            'class_id' => $sourceClass->id,
            'subject_id' => $english->id,
            'is_class_teacher' => false,
            'session' => '2026-2027',
        ]);
        TeacherAssignment::query()->create([
            'teacher_id' => $teacherTwo->id,
            'class_id' => $targetClass->id,
            'subject_id' => $math->id,
            'is_class_teacher' => false,
            'session' => '2026-2027',
        ]);

        $summary = $service->copySectionAllocations(
            $sourceClass,
            $targetClass,
            '2026-2027',
            'copy_missing_only',
            $user
        );

        $this->assertSame(3, $summary['total_source_allocations']);
        $this->assertSame(2, $summary['copied_count']);
        $this->assertSame(1, $summary['skipped_count']);
        $this->assertSame(0, $summary['replaced_count']);
        $this->assertSame(1, TeacherAssignment::query()
            ->where('class_id', $targetClass->id)
            ->where('session', '2026-2027')
            ->where('subject_id', $math->id)
            ->count());

        $copiedClassTeacher = TeacherAssignment::query()
            ->where('class_id', $targetClass->id)
            ->where('is_class_teacher', true)
            ->sole();

        $this->assertSame($classTeacherAssignment->id, $copiedClassTeacher->copied_from_assignment_id);
        $this->assertSame($user->id, $copiedClassTeacher->copied_by);
        $this->assertNotNull($copiedClassTeacher->copied_at);
        $this->assertSame($teacherOne->id, $targetClass->refresh()->class_teacher_id);
    }

    public function test_replace_target_allocations_soft_deletes_target_rows_before_copying(): void
    {
        $service = app(TeacherAssignmentService::class);
        $sourceClass = $this->createClass('7', 'A');
        $targetClass = $this->createClass('7', 'B');
        [$teacherOne, $teacherTwo] = $this->createTeachers('Adeel', 'Bilal');
        [$math, $english] = $this->createSubjects('Mathematics', 'English');
        $user = User::factory()->create();

        TeacherAssignment::query()->create([
            'teacher_id' => $teacherOne->id,
            'class_id' => $sourceClass->id,
            'subject_id' => null,
            'is_class_teacher' => true,
            'session' => '2026-2027',
        ]);
        TeacherAssignment::query()->create([
            'teacher_id' => $teacherOne->id,
            'class_id' => $sourceClass->id,
            'subject_id' => $math->id,
            'is_class_teacher' => false,
            'session' => '2026-2027',
        ]);
        TeacherAssignment::query()->create([
            'teacher_id' => $teacherTwo->id,
            'class_id' => $targetClass->id,
            'subject_id' => null,
            'is_class_teacher' => true,
            'session' => '2026-2027',
        ]);
        TeacherAssignment::query()->create([
            'teacher_id' => $teacherTwo->id,
            'class_id' => $targetClass->id,
            'subject_id' => $english->id,
            'is_class_teacher' => false,
            'session' => '2026-2027',
        ]);
        $targetClass->update(['class_teacher_id' => $teacherTwo->id]);

        $summary = $service->copySectionAllocations(
            $sourceClass,
            $targetClass,
            '2026-2027',
            'replace_target_allocations',
            $user
        );

        $this->assertSame(2, $summary['total_source_allocations']);
        $this->assertSame(2, $summary['copied_count']);
        $this->assertSame(0, $summary['skipped_count']);
        $this->assertSame(2, $summary['replaced_count']);
        $this->assertSame(2, TeacherAssignment::query()->where('class_id', $targetClass->id)->count());
        $this->assertSame(2, TeacherAssignment::onlyTrashed()->where('class_id', $targetClass->id)->count());
        $this->assertSame(4, TeacherAssignment::withTrashed()->where('class_id', $targetClass->id)->count());
        $this->assertSame($teacherOne->id, $targetClass->refresh()->class_teacher_id);
        $this->assertFalse(TeacherAssignment::query()
            ->where('class_id', $targetClass->id)
            ->where('subject_id', $english->id)
            ->exists());
    }

    public function test_copy_requires_same_class_name_with_different_sections(): void
    {
        $service = app(TeacherAssignmentService::class);
        $sourceClass = $this->createClass('7', 'A');
        $targetClass = $this->createClass('8', 'A');
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        $service->copySectionAllocations(
            $sourceClass,
            $targetClass,
            '2026-2027',
            'copy_missing_only',
            $user
        );
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
                'email' => 'section-copy-teacher-'.$index.'@example.test',
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
