<?php

namespace Tests\Feature;

use App\Http\Controllers\Warden\HostelResidentController;
use App\Models\Hostel;
use App\Models\HostelResident;
use App\Models\User;
use App\Services\HostelResidentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class HostelResidentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'residents_test', 'database.connections.residents_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        Schema::create('hostels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });
        (require database_path('migrations/2026_11_04_000100_create_hostel_residents_table.php'))->up();
    }

    public function test_import_creates_only_residents_and_preserves_details_on_repeat(): void
    {
        $hostel = Hostel::create(['name' => 'Jinnah House']);
        $service = app(HostelResidentService::class);
        $rows = [['name' => 'Resident Boy', 'date_of_birth' => '2014-01-02', 'room_group' => 'J 3-4']];
        $this->assertSame(['created' => 1, 'unchanged' => 0], $service->import($rows, $hostel));
        $resident = HostelResident::first();
        $this->assertSame('male', $resident->gender);
        $this->assertSame('2014-01-02', $resident->date_of_birth->toDateString());
        $resident->update(['room_group' => 'J 5-6', 'is_active' => false]);
        $this->assertSame(['created' => 0, 'unchanged' => 1], $service->import($rows, $hostel));
        $this->assertSame('J 5-6', $resident->fresh()->room_group);
        $this->assertFalse($resident->fresh()->is_active);
        $this->assertFalse(Schema::hasTable('students'));
        $this->assertFalse(Schema::hasTable('hostel_room_allocations'));
    }

    public function test_warden_cannot_import_residents_to_another_hostel(): void
    {
        $hostel = Hostel::create(['name' => 'Jinnah House']);
        $this->expectException(RuntimeException::class);
        app(HostelResidentService::class)->import([], $hostel, $this->warden(99));
    }

    public function test_resident_listing_is_scoped_to_wardens_hostel(): void
    {
        $first = Hostel::create(['name' => 'One']);
        $second = Hostel::create(['name' => 'Two']);
        $service = app(HostelResidentService::class);
        $row = [['name' => 'Resident', 'date_of_birth' => null, 'room_group' => 'J 3-4']];
        $service->import($row, $first);
        $service->import($row, $second);
        $this->assertSame(2, HostelResident::count());
        $this->assertSame([$first->id], HostelResident::forWarden($this->warden($first->id))->pluck('hostel_id')->all());
        $this->assertSame(0, HostelResident::forWarden($this->warden(0))->count());
    }

    public function test_warden_cannot_edit_another_hostels_resident(): void
    {
        $hostel = Hostel::create(['name' => 'One']);
        app(HostelResidentService::class)->import([['name' => 'Resident', 'date_of_birth' => null, 'room_group' => null]], $hostel);
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $this->warden(99));
        try {
            app(HostelResidentController::class)->edit($request, HostelResident::first());
            $this->fail('Cross-hostel editing must be forbidden.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_csv_reads_bom_and_validates_before_import(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'residents');
        try {
            file_put_contents($path, "\xEF\xBB\xBFRoom group,Spreadsheet name,DOB,Reason\nJ 3-4,Resident Boy,2014-01-02,Unmatched\n");
            $rows = app(HostelResidentService::class)->readCsv($path);
            $this->assertCount(1, $rows);
            $this->assertSame('Resident Boy', $rows[0]['name']);
            file_put_contents($path, "Room group,Spreadsheet name,DOB\nJ 3-4,Resident Boy,not-a-date\n");
            $this->expectException(ValidationException::class);
            app(HostelResidentService::class)->readCsv($path);
        } finally {
            unlink($path);
        }
    }

    public function test_failed_import_rolls_back_all_residents(): void
    {
        $hostel = Hostel::create(['name' => 'One']);
        HostelResident::creating(function ($resident) {
            if ($resident->name === 'Reject') {
                throw new RuntimeException('Reject second row');
            }
        });
        try {
            app(HostelResidentService::class)->import([['name' => 'First', 'date_of_birth' => null], ['name' => 'Reject', 'date_of_birth' => null]], $hostel);
            $this->fail('Import should fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Reject second row', $exception->getMessage());
        } finally {
            HostelResident::flushEventListeners();
        }
        $this->assertSame(0, HostelResident::count());
    }

    private function warden(int $hostelId): User
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->forceFill(['hostel_id' => $hostelId]);
        $user->shouldReceive('isWarden')->andReturn(true);

        return $user;
    }
}
