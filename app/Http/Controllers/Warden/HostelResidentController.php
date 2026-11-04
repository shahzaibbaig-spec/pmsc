<?php

namespace App\Http\Controllers\Warden;

use App\Http\Controllers\Controller;
use App\Models\Hostel;
use App\Models\HostelResident;
use App\Services\HostelResidentService;
use Illuminate\Http\Request;
use RuntimeException;

class HostelResidentController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $search = trim($filters['search'] ?? '');
        $residents = HostelResident::query()->forWarden($request->user())->with('hostel')
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner->where('name', 'like', '%'.$search.'%')->orWhere('room_group', 'like', '%'.$search.'%')))
            ->orderBy('room_group')->orderBy('name')->paginate(50)->withQueryString();

        return view('warden.hostel.residents.index', ['residents' => $residents, 'search' => $search, 'hostels' => $this->hostels($request)]);
    }

    public function create(Request $request)
    {
        return view('warden.hostel.residents.form', ['resident' => new HostelResident(['gender' => 'male', 'is_active' => true]), 'hostels' => $this->hostels($request)]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        HostelResident::query()->create([...$data, 'created_by' => $request->user()->id]);

        return redirect()->route('warden.hostel.residents.index')->with('success', 'Hostel resident added.');
    }

    public function edit(Request $request, HostelResident $resident)
    {
        $this->assertAccessible($request, $resident);

        return view('warden.hostel.residents.form', ['resident' => $resident, 'hostels' => $this->hostels($request)]);
    }

    public function update(Request $request, HostelResident $resident)
    {
        $this->assertAccessible($request, $resident);
        $resident->update($this->validated($request));

        return redirect()->route('warden.hostel.residents.index')->with('success', 'Hostel resident updated.');
    }

    public function import(Request $request, HostelResidentService $service)
    {
        $data = $request->validate([
            'residents_csv' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
            'hostel_id' => ['required', 'integer', 'exists:hostels,id'],
        ]);
        $hostel = Hostel::query()->findOrFail($data['hostel_id']);
        abort_if($request->user()->isWarden() && (int) $request->user()->hostel_id !== (int) $hostel->id, 403);
        try {
            $rows = $service->readCsv($request->file('residents_csv')->getRealPath());
            $result = $service->import($rows, $hostel, $request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['residents_csv' => $exception->getMessage()]);
        }

        return redirect()->route('warden.hostel.residents.index')->with('success', "{$result['created']} hostel residents added; {$result['unchanged']} already imported.");
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'hostel_id' => ['required', 'integer', 'exists:hostels,id'],
            'name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'gender' => ['required', 'in:male,female'],
            'room_group' => ['nullable', 'string', 'max:100'],
            'is_active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        abort_if($request->user()->isWarden() && (int) $request->user()->hostel_id !== (int) $data['hostel_id'], 403);
        $data['name'] = trim($data['name']);
        $data['room_group'] = isset($data['room_group']) ? trim($data['room_group']) : null;

        return $data;
    }

    private function assertAccessible(Request $request, HostelResident $resident): void
    {
        abort_if($request->user()->isWarden() && (int) $request->user()->hostel_id !== (int) $resident->hostel_id, 403);
    }

    private function hostels(Request $request)
    {
        return Hostel::query()->when($request->user()->isWarden(), fn ($query) => $query->whereKey((int) $request->user()->hostel_id))->get();
    }
}
