<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold">{{ $resident->exists ? 'Edit Hostel Resident' : 'Add Hostel Resident' }}</h2></x-slot>
    <div class="mx-auto max-w-3xl space-y-4 px-4 py-8">
        @if($errors->any())<div class="rounded-xl bg-rose-50 p-4 text-rose-700"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ $resident->exists ? route('warden.hostel.residents.update', $resident) : route('warden.hostel.residents.store') }}" class="space-y-4 rounded-xl bg-white p-6 shadow-sm">
            @csrf
            @if($resident->exists) @method('PUT') @endif
            <label class="block">Name<input name="name" value="{{ old('name', $resident->name) }}" maxlength="255" required class="mt-1 block w-full rounded-lg border-slate-300"></label>
            <label class="block">Hostel<select name="hostel_id" required class="mt-1 block w-full rounded-lg border-slate-300">@foreach($hostels as $hostel)<option value="{{ $hostel->id }}" @selected((int) old('hostel_id', $resident->hostel_id) === $hostel->id)>{{ $hostel->name }}</option>@endforeach</select></label>
            <label class="block">Date of birth<input type="date" name="date_of_birth" value="{{ old('date_of_birth', $resident->date_of_birth?->toDateString()) }}" class="mt-1 block w-full rounded-lg border-slate-300"></label>
            <label class="block">Gender<select name="gender" required class="mt-1 block w-full rounded-lg border-slate-300">@foreach(['male', 'female'] as $gender)<option value="{{ $gender }}" @selected(old('gender', $resident->gender) === $gender)>{{ ucfirst($gender) }}</option>@endforeach</select></label>
            <label class="block">Room group<input name="room_group" value="{{ old('room_group', $resident->room_group) }}" maxlength="100" placeholder="e.g. J 3-4" class="mt-1 block w-full rounded-lg border-slate-300"></label>
            <label class="block">Status<select name="is_active" class="mt-1 block w-full rounded-lg border-slate-300"><option value="1" @selected((int) old('is_active', $resident->is_active) === 1)>Active</option><option value="0" @selected((int) old('is_active', $resident->is_active) === 0)>Inactive</option></select></label>
            <label class="block">Notes<textarea name="notes" maxlength="2000" rows="3" class="mt-1 block w-full rounded-lg border-slate-300">{{ old('notes', $resident->notes) }}</textarea></label>
            <div class="flex gap-3"><button class="rounded-xl bg-slate-900 px-4 py-3 text-white">Save Resident</button><a href="{{ route('warden.hostel.residents.index') }}" class="rounded-xl border px-4 py-3">Cancel</a></div>
        </form>
    </div>
</x-app-layout>
