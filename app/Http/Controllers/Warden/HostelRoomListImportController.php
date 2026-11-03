<?php

namespace App\Http\Controllers\Warden;

use App\Http\Controllers\Controller;
use App\Models\Hostel;
use App\Models\Student;
use App\Services\HostelRoomListImportService;
use Illuminate\Http\Request;
use RuntimeException;

class HostelRoomListImportController extends Controller
{
    public function index(Request $request, HostelRoomListImportService $service)
    {
        $source = storage_path('app/private/hostel/room-wise-list.xlsx');
        if (! $request->session()->has('hostel_room_import') && is_file($source)) {
            $request->session()->put('hostel_room_import', $service->match($service->read($source)));
        }

        return view('warden.hostel.allocations.import', [
            'preview' => $request->session()->get('hostel_room_import'),
            'hostels' => Hostel::query()->when($request->user()->isWarden(), fn ($query) => $query->whereKey($request->user()->hostel_id))->get(),
            'students' => Student::query()->orderBy('name')->get(['id', 'name', 'student_id']),
        ]);
    }

    public function preview(Request $request, HostelRoomListImportService $service)
    {
        $request->validate(['workbook' => ['required', 'file', 'mimes:xlsx', 'max:5120']]);
        try {
            $rooms = $service->match($service->read($request->file('workbook')->getRealPath()));
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['workbook' => 'Unable to read the room list. Upload an XLSX with J room headings and student names.']);
        }
        $request->session()->put('hostel_room_import', $rooms);

        return redirect()->route('warden.hostel.import.index');
    }

    public function store(Request $request, HostelRoomListImportService $service)
    {
        $rooms = $request->session()->get('hostel_room_import');
        abort_unless(is_array($rooms) && count($rooms), 419, 'Upload the workbook again.');
        $rules = [
            'hostel_id' => ['required', 'integer', 'exists:hostels,id'],
            'allocated_from' => ['required', 'date'],
            'rooms' => ['required', 'array', 'size:'.count($rooms)],
        ];
        foreach ($rooms as $i => $room) {
            $rules["rooms.$i.floor"] = ['required', 'integer', 'min:0', 'max:99'];
            $rules["rooms.$i.capacity"] = ['required', 'integer', 'min:1', 'max:1000'];
            foreach ($room['students'] as $j => $student) {
                $rules["rooms.$i.students.$j"] = ['nullable', 'integer', 'exists:students,id,deleted_at,NULL'];
            }
        }
        $data = $request->validate($rules);
        try {
            $counts = $service->import($rooms, $data['rooms'], (int) $data['hostel_id'], $data['allocated_from'], $request->user());
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['import' => $exception->getMessage()]);
        }
        $request->session()->forget('hostel_room_import');

        return redirect()->route('warden.hostel.allocations.index')->with('success', "Import complete: {$counts['rooms']} rooms added, {$counts['allocated']} students allocated, {$counts['unchanged']} unchanged, {$counts['skipped']} skipped.");
    }
}
