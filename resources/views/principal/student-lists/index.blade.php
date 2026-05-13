<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Class-wise Student Lists</h2>
                <p class="mt-1 text-sm text-slate-500">Filter by session, class, section, and status. Print or download for records.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('principal.student-lists.print', array_merge(request()->query(), ['format' => 'html'])) }}"
                    target="_blank"
                    class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Print List
                </a>
                <a
                    href="{{ route('principal.student-lists.print', array_merge(request()->query(), ['format' => 'pdf'])) }}"
                    class="inline-flex min-h-11 items-center rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100"
                >
                    Download PDF
                </a>
                <a
                    href="{{ route('principal.student-lists.print', array_merge(request()->query(), ['format' => 'xlsx'])) }}"
                    class="inline-flex min-h-11 items-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100"
                >
                    Export Excel
                </a>
                <a
                    href="{{ route('principal.student-lists.print', array_merge(request()->query(), ['format' => 'csv'])) }}"
                    class="inline-flex min-h-11 items-center rounded-xl border border-cyan-200 bg-cyan-50 px-4 py-2 text-sm font-semibold text-cyan-700 hover:bg-cyan-100"
                >
                    Export CSV
                </a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 py-8">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('principal.student-lists.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-5">
                <div>
                    <label for="session" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Session</label>
                    <select id="session" name="session" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        @foreach ($sessions as $session)
                            <option value="{{ $session }}" @selected(($filters['session'] ?? null) === $session)>{{ $session }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="class_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Class</label>
                    <select id="class_id" name="class_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        <option value="">All Classes</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class['id'] }}" @selected((int) ($filters['class_id'] ?? 0) === (int) $class['id'])>{{ $class['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="section" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Section</label>
                    <select id="section" name="section" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        <option value="">All Sections</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section }}" @selected(($filters['section'] ?? null) === $section)>{{ $section }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="status" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                    <select id="status" name="status" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        <option value="active" @selected(($filters['status'] ?? 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(($filters['status'] ?? 'active') === 'inactive')>Inactive</option>
                        <option value="all" @selected(($filters['status'] ?? 'active') === 'all')>All</option>
                    </select>
                </div>

                <div>
                    <label for="per_page" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Rows</label>
                    <select id="per_page" name="per_page" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        @foreach ([10, 25, 50, 100, 200] as $size)
                            <option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 25) === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-5 flex flex-wrap items-center gap-2">
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                        Apply Filters
                    </button>
                    <a href="{{ route('principal.student-lists.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Reset
                    </a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h3 class="text-base font-semibold text-slate-900">Students ({{ number_format($students->total()) }})</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-blue-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Sr #</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Admission No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Student Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Father Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Class/Section</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Contact</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Age / DOB</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($rows as $row)
                            <tr>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $row['sr_no'] }}</td>
                                <td class="px-4 py-4 text-sm font-semibold text-slate-900">{{ $row['student_id'] }}</td>
                                <td class="px-4 py-4 text-sm text-slate-800">{{ $row['name'] }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $row['father_name'] ?: '-' }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $row['class_section'] ?: '-' }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $row['contact'] ?: '-' }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">
                                    {{ $row['age'] !== null ? $row['age'] : '-' }}
                                    @if (! empty($row['date_of_birth']))
                                        <span class="text-xs text-slate-500">({{ $row['date_of_birth'] }})</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-sm">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $row['status'] === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                        {{ ucfirst($row['status']) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">No students found for the selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($students->hasPages())
                <div class="border-t border-slate-200 px-4 py-3">
                    {{ $students->links() }}
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
