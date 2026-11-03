<?php

namespace Tests\Feature;

use App\Models\Hostel;
use App\Models\HostelRoom;
use App\Models\HostelRoomAllocation;
use App\Models\Student;
use App\Models\User;
use App\Services\HostelRoomAllocationService;
use App\Services\HostelRoomListImportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Tests\TestCase;

class HostelRoomListImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'hostel_import_test', 'database.connections.hostel_import_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        Schema::create('hostels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('student_id')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->integer('age')->nullable();
            $table->string('gender')->nullable();
            $table->integer('class_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('hostel_rooms', function (Blueprint $table) {
            $table->id();
            $table->integer('hostel_id');
            $table->string('room_name');
            $table->integer('floor_number');
            $table->integer('capacity');
            $table->string('gender')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active');
            $table->integer('created_by');
            $table->timestamps();
        });
        Schema::create('hostel_room_allocations', function (Blueprint $table) {
            $table->id();
            $table->integer('hostel_id');
            $table->integer('hostel_room_id')->nullable();
            $table->integer('student_id');
            $table->date('allocated_from');
            $table->date('allocated_to')->nullable();
            $table->string('session');
            $table->string('status');
            $table->boolean('is_active');
            $table->text('remarks')->nullable();
            $table->integer('allocated_by');
            $table->timestamps();
        });
    }

    public function test_workbook_preserves_room_groups_and_ambiguous_floors(): void
    {
        $book = new Spreadsheet;
        $book->getActiveSheet()->fromArray([
            ['GROUND FLOOR'], ['J 3-4'], ['SR #', 'Name', 'DOB'], [1, 'Aahil', 44488],
        ]);
        $book->createSheet()->fromArray([['1st & 2nd FLOOR'], ['J 17-18'], [1, 'Zain']]);
        $path = tempnam(sys_get_temp_dir(), 'hostel');
        try {
            (new Xlsx($book))->save($path);
            $rooms = app(HostelRoomListImportService::class)->read($path);
            $this->assertCount(2, $rooms);
            $this->assertSame('J 3-4', $rooms[0]['name']);
            $this->assertSame(0, $rooms[0]['floor']);
            $this->assertNull($rooms[1]['floor']);
            $this->assertSame('2021-10-19', $rooms[0]['students'][0]['dob']);
        } finally {
            unlink($path);
            $book->disconnectWorksheets();
        }
    }

    public function test_matching_requires_unique_name_and_matching_birth_date(): void
    {
        $first = $this->student('Same Name', '2012-01-01');
        $this->student('Same Name', '2013-01-01');
        $rows = [['name' => 'Same Name', 'dob' => '2012-01-01'], ['name' => 'Same Name', 'dob' => null], ['name' => 'Same Name', 'dob' => '2014-01-01']];
        $matched = app(HostelRoomListImportService::class)->match([['students' => $rows]]);
        $this->assertSame($first->id, $matched[0]['students'][0]['student_id']);
        $this->assertNull($matched[0]['students'][1]['student_id']);
        $this->assertNull($matched[0]['students'][2]['student_id']);
    }

    public function test_import_is_repeatable_and_room_changes_keep_history(): void
    {
        $hostel = Hostel::create(['name' => 'Jinnah House']);
        $student = $this->student('Student One', '2012-01-01');
        $user = $this->actor($hostel->id);
        $service = app(HostelRoomListImportService::class);
        $rooms = $this->roomGroups();
        $choices = [['floor' => 0, 'capacity' => 2, 'students' => [$student->id]]];
        $this->assertSame(1, $service->import($rooms, $choices, $hostel->id, '2026-11-03', $user)['allocated']);
        $this->assertSame(1, $service->import($rooms, $choices, $hostel->id, '2026-11-03', $user)['unchanged']);
        $rooms[0]['name'] = 'J 5-6';
        $service->import($rooms, $choices, $hostel->id, '2026-11-04', $user);
        $this->assertSame(2, HostelRoomAllocation::count());
        $this->assertSame(1, HostelRoomAllocation::active()->count());
        $this->assertSame('shifted', HostelRoomAllocation::first()->status);
    }

    public function test_failed_duplicate_student_import_rolls_back_rooms_and_allocations(): void
    {
        $hostel = Hostel::create(['name' => 'Jinnah House']);
        $student = $this->student('Student One', '2012-01-01');
        $groups = $this->roomGroups();
        $groups[0]['students'][] = $groups[0]['students'][0];
        try {
            app(HostelRoomListImportService::class)->import($groups, [['floor' => 0, 'capacity' => 2, 'students' => [$student->id, $student->id]]], $hostel->id, '2026-11-03', $this->actor($hostel->id));
            $this->fail('Duplicate student should be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('more than once', $exception->getMessage());
        }
        $this->assertSame(0, HostelRoom::count());
        $this->assertSame(0, HostelRoomAllocation::count());
    }

    public function test_warden_cannot_import_into_another_hostel(): void
    {
        $this->expectException(RuntimeException::class);
        app(HostelRoomListImportService::class)->import([], [], 2, '2026-11-03', $this->actor(1));
    }

    public function test_editing_current_allocation_does_not_create_a_transfer(): void
    {
        $hostel = Hostel::create(['name' => 'Jinnah House']);
        $student = $this->student('Student One', '2012-01-01');
        $user = $this->actor($hostel->id);
        app(HostelRoomListImportService::class)->import($this->roomGroups(), [['floor' => 0, 'capacity' => 1, 'students' => [$student->id]]], $hostel->id, '2026-11-03', $user);
        app(HostelRoomAllocationService::class)->shiftStudentRoom($student->id, HostelRoom::first()->id, ['allocated_from' => '2026-11-03', 'remarks' => 'Updated details'], $user);
        $this->assertSame(1, HostelRoomAllocation::count());
        $this->assertSame('Updated details', HostelRoomAllocation::first()->remarks);
    }

    public function test_capacity_failure_rolls_back_entire_import(): void
    {
        $hostel = Hostel::create(['name' => 'Jinnah House']);
        $one = $this->student('One', '2012-01-01');
        $two = $this->student('Two', '2013-01-01');
        $groups = $this->roomGroups();
        $groups[0]['students'][] = ['name' => 'Two', 'source' => 'Sheet1:5'];
        try {
            app(HostelRoomListImportService::class)->import($groups, [['floor' => 0, 'capacity' => 1, 'students' => [$one->id, $two->id]]], $hostel->id, '2026-11-03', $this->actor($hostel->id));
            $this->fail('A full room must reject another student.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('no available beds', strtolower($exception->getMessage()));
        }
        $this->assertSame(0, HostelRoom::count());
        $this->assertSame(0, HostelRoomAllocation::count());
    }

    public function test_room_change_cannot_predate_current_assignment(): void
    {
        $hostel = Hostel::create(['name' => 'Jinnah House']);
        $student = $this->student('Student One', '2012-01-01');
        $user = $this->actor($hostel->id);
        $service = app(HostelRoomListImportService::class);
        $choices = [['floor' => 0, 'capacity' => 1, 'students' => [$student->id]]];
        $service->import($this->roomGroups(), $choices, $hostel->id, '2026-11-03', $user);
        $groups = $this->roomGroups();
        $groups[0]['name'] = 'J 5-6';
        try {
            $service->import($groups, $choices, $hostel->id, '2026-11-02', $user);
            $this->fail('A move before the assignment date must fail.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('before', $exception->getMessage());
        }
        $this->assertSame(1, HostelRoom::count());
        $this->assertSame(1, HostelRoomAllocation::active()->count());
    }

    private function actor(int $hostelId): User
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->forceFill(['id' => 1, 'hostel_id' => $hostelId]);
        $user->shouldReceive('isWarden')->andReturn(true);

        return $user;
    }

    private function student(string $name, string $dob): Student
    {
        return Student::create(['name' => $name, 'date_of_birth' => $dob, 'gender' => 'male']);
    }

    private function roomGroups(): array
    {
        return [['name' => 'J 3-4', 'floor_label' => 'GROUND FLOOR', 'students' => [['name' => 'Student One', 'source' => 'Sheet1:4']]]];
    }
}
