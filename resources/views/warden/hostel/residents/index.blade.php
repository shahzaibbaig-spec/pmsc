<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="text-xl font-semibold">Hostel Residents</h2><p class="mt-1 text-sm text-slate-500">Residents registered for hostel accommodation only.</p></div>
            @can('assign_students_to_rooms')<a href="{{ route('warden.hostel.residents.create') }}" class="rounded-xl bg-slate-900 px-4 py-3 text-white">Add Resident</a>@endcan
        </div>
    </x-slot>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8">
        @if(session('success'))<div class="rounded-xl bg-emerald-50 p-4 text-emerald-700">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="rounded-xl bg-rose-50 p-4 text-rose-700"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @can('assign_students_to_rooms')
        <details class="rounded-xl bg-white p-6 shadow-sm">
            <summary class="cursor-pointer font-semibold">Import boys from the remaining-residents CSV</summary>
            <form method="POST" enctype="multipart/form-data" action="{{ route('warden.hostel.residents.import') }}" class="mt-4 space-y-4">
                @csrf
                <p class="text-sm text-slate-600">Creates hostel residents with their birth dates and room groups. Re-uploading the same list keeps existing resident details.</p>
                <label class="block">Hostel<select name="hostel_id" required class="mt-1 block rounded-lg border-slate-300">@foreach($hostels as $hostel)<option value="{{ $hostel->id }}">{{ $hostel->name }}</option>@endforeach</select></label>
                <label class="block">CSV file<input type="file" name="residents_csv" required accept=".csv" class="mt-1 block"></label>
                <button class="rounded-xl bg-slate-900 px-4 py-3 text-white">Import Hostel Residents</button>
            </form>
        </details>
        @endcan
        <section class="overflow-hidden rounded-xl bg-white shadow-sm">
            <form method="GET" class="flex flex-wrap gap-3 p-4"><input aria-label="Search resident or room group" name="search" value="{{ $search }}" placeholder="Search name or room group" class="rounded-lg border-slate-300"><button class="rounded-lg border px-4 py-2">Search</button><span class="self-center text-sm text-slate-500">{{ $residents->total() }} residents</span></form>
            <div class="overflow-x-auto"><table class="w-full text-left text-sm">
                <thead class="bg-slate-50"><tr>@foreach(['Name', 'Hostel', 'Room group', 'DOB', 'Gender', 'Status', 'Actions'] as $heading)<th class="p-3">{{ $heading }}</th>@endforeach</tr></thead>
                <tbody>@forelse($residents as $resident)<tr class="border-t border-slate-100">
                    <td class="p-3 font-semibold">{{ $resident->name }}</td><td class="p-3">{{ $resident->hostel?->name }}</td><td class="p-3">{{ $resident->room_group ?: 'Pending' }}</td><td class="p-3">{{ $resident->date_of_birth?->format('d M Y') ?? '—' }}</td><td class="p-3">{{ ucfirst($resident->gender) }}</td><td class="p-3">{{ $resident->is_active ? 'Active' : 'Inactive' }}</td>
                    <td class="p-3">@can('assign_students_to_rooms')<a class="font-semibold text-indigo-700" href="{{ route('warden.hostel.residents.edit', $resident) }}">Edit / Change Room Group</a>@endcan</td>
                </tr>@empty<tr><td colspan="7" class="p-8 text-center text-slate-500">No hostel residents found.</td></tr>@endforelse</tbody>
            </table></div>
            <div class="p-4">{{ $residents->links() }}</div>
        </section>
    </div>
</x-app-layout>
