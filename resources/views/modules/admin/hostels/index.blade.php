<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Hostel Management</h2>
                <p class="mt-1 text-sm text-slate-500">Create and maintain hostels used by wardens and student allocations.</p>
            </div>
            <a href="{{ route('warden.hostel.allocations.index') }}" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                Open Hostel Allocations
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->has('hostel'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ $errors->first('hostel') }}
                </div>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Create Hostel</h3>
                <form method="POST" action="{{ route('admin.hostels.store') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-4">
                    @csrf
                    <div class="md:col-span-3">
                        <label for="name" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Hostel Name</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500" placeholder="e.g. Fatimah House">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Add Hostel
                        </button>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" action="{{ route('admin.hostels.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div class="md:col-span-2">
                        <label for="search" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
                        <input id="search" type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500" placeholder="Search hostel name">
                    </div>
                    <div>
                        <label for="per_page" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Rows</label>
                        <select id="per_page" name="per_page" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            @foreach ([10, 15, 25, 50] as $size)
                                <option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 15) === $size)>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Filter</button>
                        <a href="{{ route('admin.hostels.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Hostel</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Rooms</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Wardens</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($hostels as $hostel)
                                <tr x-data="{ editOpen: false }">
                                    <td class="px-4 py-4 text-sm font-semibold text-slate-900">{{ $hostel->name }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ (int) ($hostel->rooms_count ?? 0) }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ (int) ($hostel->wardens_count ?? 0) }}</td>
                                    <td class="px-4 py-4 text-sm">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="button" @click="editOpen = !editOpen" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                Edit
                                            </button>
                                            <form method="POST" action="{{ route('admin.hostels.destroy', $hostel) }}" onsubmit="return confirm('Delete this hostel? This works only when no linked records exist.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex min-h-10 items-center rounded-lg border border-rose-300 bg-white px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>

                                        <form method="POST" action="{{ route('admin.hostels.update', $hostel) }}" x-show="editOpen" x-cloak class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                                            @csrf
                                            @method('PUT')
                                            <div class="flex flex-col gap-2 md:flex-row">
                                                <input type="text" name="name" value="{{ old('name', $hostel->name) }}" required class="block min-h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                                                <button type="submit" class="inline-flex min-h-10 items-center rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">
                                                    Save
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-500">No hostels found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($hostels->hasPages())
                    <div class="border-t border-slate-200 px-4 py-3">
                        {{ $hostels->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
