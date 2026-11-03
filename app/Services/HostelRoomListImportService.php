<?php

namespace App\Services;

use App\Models\HostelRoom;
use App\Models\HostelRoomAllocation;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use RuntimeException;

class HostelRoomListImportService
{
    public function read(string $path): array
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $book = $reader->load($path);
        $rooms = [];
        foreach ($book->getWorksheetIterator() as $sheet) {
            $room = null;
            $floor = null;
            $floorLabel = '';
            foreach ($sheet->toArray(null, false, false, false) as $index => $cells) {
                $label = trim((string) ($cells[0] ?? ''));
                if (str_contains(strtoupper($label), 'FLOOR')) {
                    $floorLabel = $label;
                    $floor = str_contains(strtoupper($label), 'GROUND') ? 0 : null;
                } elseif (preg_match('/^J\s*(\d+)\s*-\s*(\d+)$/i', $label, $match)) {
                    $room = 'J '.$match[1].'-'.$match[2];
                    if (isset($rooms[$room])) {
                        throw new RuntimeException('Duplicate room heading: '.$room);
                    }
                    $rooms[$room] = ['name' => $room, 'floor' => $floor, 'floor_label' => $floorLabel, 'students' => []];
                } elseif ($room !== null && is_numeric($label) && trim((string) ($cells[1] ?? '')) !== '') {
                    $dob = $cells[2] ?? null;
                    $rooms[$room]['students'][] = [
                        'name' => trim((string) $cells[1]),
                        'dob' => is_numeric($dob) ? Date::excelToDateTimeObject($dob)->format('Y-m-d') : null,
                        'source' => $sheet->getTitle().':'.($index + 1),
                    ];
                }
            }
        }
        $book->disconnectWorksheets();
        if ($rooms === []) {
            throw new RuntimeException('No J room headings were found in the workbook.');
        }

        return array_values($rooms);
    }

    public function match(array $rooms): array
    {
        $students = Student::query()->get(['id', 'name', 'date_of_birth'])->groupBy(fn ($student) => $this->key($student->name));
        foreach ($rooms as &$room) {
            foreach ($room['students'] as &$row) {
                $candidates = $students->get($this->key($row['name']), collect());
                if ($row['dob']) {
                    $candidates = $candidates->filter(fn ($student) => $student->date_of_birth?->format('Y-m-d') === $row['dob']);
                }
                $row['student_id'] = $candidates->count() === 1 ? (int) $candidates->first()->id : null;
            }
        }

        return $rooms;
    }

    public function import(array $rooms, array $choices, int $hostelId, string $date, User $user): array
    {
        if ($user->isWarden() && (int) $user->hostel_id !== $hostelId) {
            throw new RuntimeException('You can only import into your assigned hostel.');
        }

        return DB::transaction(function () use ($rooms, $choices, $hostelId, $date, $user) {
            $counts = ['rooms' => 0, 'allocated' => 0, 'unchanged' => 0, 'skipped' => 0];
            $seen = [];
            foreach ($rooms as $i => $group) {
                $choice = $choices[$i];
                $room = HostelRoom::query()->where('hostel_id', $hostelId)->where('room_name', $group['name'])->lockForUpdate()->first();
                if ($room && (int) $room->floor_number !== (int) $choice['floor']) {
                    throw new RuntimeException($group['name'].' already exists on another floor. Edit the room first.');
                }
                if (! $room) {
                    $room = app(HostelRoomService::class)->createRoom([
                        'hostel_id' => $hostelId, 'room_name' => $group['name'], 'floor_number' => $choice['floor'],
                        'capacity' => $choice['capacity'], 'notes' => 'Imported room group; source floor heading: '.$group['floor_label'],
                    ], $user);
                    $counts['rooms']++;
                }
                foreach ($group['students'] as $j => $row) {
                    $studentId = (int) ($choice['students'][$j] ?? 0);
                    if (! $studentId) {
                        $counts['skipped']++;

                        continue;
                    }
                    if (isset($seen[$studentId])) {
                        throw new RuntimeException('The same student was selected more than once: '.$row['name']);
                    }
                    $seen[$studentId] = true;
                    Student::query()->whereKey($studentId)->lockForUpdate()->firstOrFail();
                    $active = HostelRoomAllocation::query()->where('student_id', $studentId)->active()->lockForUpdate()->first();
                    if ($active && (int) $active->hostel_room_id === (int) $room->id) {
                        $counts['unchanged']++;

                        continue;
                    }
                    $data = ['allocated_from' => $date, 'remarks' => 'Room-wise list: '.$row['source']];
                    $service = app(HostelRoomAllocationService::class);
                    if ($active) {
                        if ((int) $active->hostel_id !== $hostelId) {
                            throw new RuntimeException($row['name'].' is already assigned to another hostel.');
                        }
                        $service->shiftStudentRoom($studentId, (int) $room->id, $data, $user);
                    } else {
                        $service->allocateStudentToRoom($studentId, (int) $room->id, $data, $user);
                    }
                    $counts['allocated']++;
                }
            }

            return $counts;
        });
    }

    private function key(string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($name)));
    }
}
