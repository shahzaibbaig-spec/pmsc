<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Fee Structure List
        </h2>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc ps-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-5 sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-lg font-medium text-gray-900">Filters</h3>
                        @if ($canCreate)
                            <a href="{{ route('principal.fees.structures.create') }}" class="inline-flex min-h-11 items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                Create Fee Structure
                            </a>
                        @endif
                    </div>

                    <form method="GET" action="{{ route('principal.fees.structures.index') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-5">
                        <div>
                            <x-input-label for="session" value="Session" />
                            <select id="session" name="session" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Sessions</option>
                                @foreach($sessions as $session)
                                    <option value="{{ $session }}" @selected($filters['session'] === $session)>{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="class_id" value="Class" />
                            <select id="class_id" name="class_id" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Classes</option>
                                @foreach($classes as $classRoom)
                                    <option value="{{ $classRoom->id }}" @selected((string) $filters['class_id'] === (string) $classRoom->id)>
                                        {{ trim($classRoom->name.' '.($classRoom->section ?? '')) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="fee_type" value="Fee Type" />
                            <select id="fee_type" name="fee_type" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Types</option>
                                @foreach($feeTypes as $feeType)
                                    <option value="{{ $feeType }}" @selected($filters['fee_type'] === $feeType)>{{ ucwords(str_replace('_', ' ', $feeType)) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="is_active" value="Status" />
                            <select id="is_active" name="is_active" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All</option>
                                <option value="1" @selected((string) $filters['is_active'] === '1')>Active</option>
                                <option value="0" @selected((string) $filters['is_active'] === '0')>Inactive</option>
                            </select>
                        </div>

                        <div>
                            <x-input-label for="title" value="Title" />
                            <x-text-input id="title" name="title" type="text" class="mt-1 block min-h-11 w-full" value="{{ $filters['title'] }}" placeholder="Search title" />
                        </div>

                        <div class="md:col-span-5 flex flex-wrap gap-2">
                            <button type="submit" class="inline-flex min-h-11 items-center rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">
                                Apply
                            </button>
                            <a href="{{ route('principal.fees.structures.index') }}" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-[1150px] divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Session</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Class</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Title</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Fee Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Monthly</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Active</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Created By</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($structures as $structure)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $structure->session }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ trim(($structure->classRoom?->name ?? 'Class').' '.($structure->classRoom?->section ?? '')) }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $structure->title }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ ucwords(str_replace('_', ' ', $structure->fee_type)) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ number_format((float) $structure->amount, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $structure->is_monthly ? 'Yes' : 'No' }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        @if ($structure->is_active)
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700">Active</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $structure->creator?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex flex-wrap items-center gap-2">
                                            @if ($canEdit)
                                                <a href="{{ route('principal.fees.structures.edit', $structure) }}" class="inline-flex min-h-10 items-center rounded-md border border-indigo-300 px-3 text-xs font-medium text-indigo-700 hover:bg-indigo-50">
                                                    Edit
                                                </a>
                                            @endif
                                            @if ($canDelete)
                                                <form method="POST" action="{{ route('principal.fees.structures.destroy', $structure) }}" onsubmit="return confirm('Delete this fee structure?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex min-h-10 items-center rounded-md border border-red-300 px-3 text-xs font-medium text-red-700 hover:bg-red-50">
                                                        Delete
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-8 text-center text-sm text-gray-500">No fee structures found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-4">
                    {{ $structures->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
