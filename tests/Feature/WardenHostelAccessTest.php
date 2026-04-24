<?php

namespace Tests\Feature;

use App\Models\Hostel;
use App\Models\HostelRoom;
use App\Models\HostelRoomAllocation;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\WardenStudentRecordService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WardenHostelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_fatimah_warden_sees_only_fatimah_assigned_students(): void
    {
        [$fatimahWarden, $fatimahHostel, $jinnahHostel] = $this->seedWardenAndHostels();
        [$class] = $this->seedClass();

        $fatimahGirl = $this->createStudent($class, 'female', '2014-01-10', 12);
        $fatimahUnderSixBoy = $this->createStudent($class, 'male', '2021-05-02', 5);
        $jinnahBoy = $this->createStudent($class, 'male', '2017-04-01', 9);

        $fatimahRoom = $this->createRoom($fatimahHostel, $fatimahWarden, 'F-101', 'female');
        $fatimahInfantRoom = $this->createRoom($fatimahHostel, $fatimahWarden, 'F-102', 'male');
        $jinnahRoom = $this->createRoom($jinnahHostel, $fatimahWarden, 'J-201', 'male');

        $this->allocate($fatimahGirl, $fatimahRoom, $fatimahWarden);
        $this->allocate($fatimahUnderSixBoy, $fatimahInfantRoom, $fatimahWarden);
        $this->allocate($jinnahBoy, $jinnahRoom, $fatimahWarden);

        $visibleIds = Student::query()
            ->forWarden($fatimahWarden)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        $this->assertEqualsCanonicalizing(
            [(int) $fatimahGirl->id, (int) $fatimahUnderSixBoy->id],
            $visibleIds
        );
    }

    public function test_jinnah_warden_sees_only_jinnah_assigned_students(): void
    {
        [$fatimahWarden, $fatimahHostel, $jinnahHostel] = $this->seedWardenAndHostels();
        $jinnahWarden = $this->createWarden($jinnahHostel);
        [$class] = $this->seedClass();

        $fatimahGirl = $this->createStudent($class, 'female', '2013-07-10', 13);
        $jinnahBoyOne = $this->createStudent($class, 'male', '2015-02-18', 11);
        $jinnahBoyTwo = $this->createStudent($class, 'male', '2016-03-20', 10);

        $fatimahRoom = $this->createRoom($fatimahHostel, $fatimahWarden, 'F-111', 'female');
        $jinnahRoom = $this->createRoom($jinnahHostel, $jinnahWarden, 'J-211', 'male');

        $this->allocate($fatimahGirl, $fatimahRoom, $fatimahWarden);
        $this->allocate($jinnahBoyOne, $jinnahRoom, $jinnahWarden);
        $this->allocate($jinnahBoyTwo, $jinnahRoom, $jinnahWarden);

        $visibleIds = Student::query()
            ->forWarden($jinnahWarden)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        $this->assertEqualsCanonicalizing(
            [(int) $jinnahBoyOne->id, (int) $jinnahBoyTwo->id],
            $visibleIds
        );
    }

    public function test_warden_cannot_open_other_hostel_student_record(): void
    {
        [$fatimahWarden, $fatimahHostel, $jinnahHostel] = $this->seedWardenAndHostels();
        $jinnahWarden = $this->createWarden($jinnahHostel);
        [$class] = $this->seedClass();

        $fatimahGirl = $this->createStudent($class, 'female', '2013-01-10', 13);
        $jinnahBoy = $this->createStudent($class, 'male', '2016-01-10', 10);

        $fatimahRoom = $this->createRoom($fatimahHostel, $fatimahWarden, 'F-121', 'female');
        $jinnahRoom = $this->createRoom($jinnahHostel, $jinnahWarden, 'J-221', 'male');

        $this->allocate($fatimahGirl, $fatimahRoom, $fatimahWarden);
        $this->allocate($jinnahBoy, $jinnahRoom, $jinnahWarden);

        /** @var WardenStudentRecordService $service */
        $service = app(WardenStudentRecordService::class);

        $this->expectException(RuntimeException::class);
        $service->getStudentRecord($jinnahBoy, null, $fatimahWarden);
    }

    private function seedWardenAndHostels(): array
    {
        Role::findOrCreate('Warden', 'web');

        $fatimahHostel = Hostel::query()->create(['name' => 'Fatimah House']);
        $jinnahHostel = Hostel::query()->create(['name' => 'Jinnah House']);

        $fatimahWarden = $this->createWarden($fatimahHostel);

        return [$fatimahWarden, $fatimahHostel, $jinnahHostel];
    }

    private function createWarden(Hostel $hostel): User
    {
        $user = User::factory()->create([
            'hostel_id' => (int) $hostel->id,
        ]);
        $user->assignRole('Warden');

        return $user;
    }

    private function seedClass(): array
    {
        $class = SchoolClass::query()->create([
            'name' => 'Class 1',
            'section' => 'A',
            'status' => 'active',
        ]);

        return [$class];
    }

    private function createStudent(SchoolClass $class, string $gender, string $dob, int $age): Student
    {
        return Student::query()->create([
            'student_id' => 'STU-'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'name' => fake()->name(),
            'father_name' => fake()->name('male'),
            'class_id' => (int) $class->id,
            'date_of_birth' => $dob,
            'age' => $age,
            'gender' => $gender,
            'status' => 'active',
        ]);
    }

    private function createRoom(Hostel $hostel, User $createdBy, string $name, string $gender): HostelRoom
    {
        return HostelRoom::query()->create([
            'hostel_id' => (int) $hostel->id,
            'room_name' => $name,
            'floor_number' => 1,
            'capacity' => 10,
            'gender' => $gender,
            'is_active' => true,
            'created_by' => (int) $createdBy->id,
        ]);
    }

    private function allocate(Student $student, HostelRoom $room, User $allocatedBy): HostelRoomAllocation
    {
        return HostelRoomAllocation::query()->create([
            'hostel_room_id' => (int) $room->id,
            'hostel_id' => (int) $room->hostel_id,
            'student_id' => (int) $student->id,
            'allocated_from' => now()->toDateString(),
            'allocated_to' => null,
            'session' => '2025-2026',
            'status' => HostelRoomAllocation::STATUS_ACTIVE,
            'is_active' => true,
            'remarks' => null,
            'allocated_by' => (int) $allocatedBy->id,
        ]);
    }
}

