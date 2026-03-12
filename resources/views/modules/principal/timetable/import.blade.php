<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Timetable Import
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-xl bg-white shadow-sm">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900">Import MASTER School Timetable Workbook</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Upload <code>MASTER_school_timetable_import.xlsx</code> with sheets:
                        classes, teachers, subjects, class_teachers, teacher_subject_assignments, timetable.
                    </p>

                    @if($importError)
                        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ $importError }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <ul class="list-disc ps-5 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('principal.timetable.import.store') }}" enctype="multipart/form-data" class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-3">
                        @csrf

                        <div>
                            <x-input-label for="session" value="Session" />
                            <select id="session" name="session" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($sessions as $session)
                                    <option value="{{ $session }}" @selected(old('session', $defaultSession) === $session)>{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label for="workbook" value="Excel Workbook (.xlsx)" />
                            <input
                                id="workbook"
                                name="workbook"
                                type="file"
                                accept=".xlsx"
                                required
                                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-600 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-indigo-700"
                            >
                        </div>

                        <div class="md:col-span-3 flex items-center justify-end">
                            <button type="submit" class="inline-flex min-h-11 items-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                                Import Workbook
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @if($summary)
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-6">
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Session</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900">{{ $summary['session'] }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Classes Imported</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $summary['classes_imported'] }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Teachers Imported</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $summary['teachers_imported'] }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Subjects Imported</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $summary['subjects_imported'] }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Assignments Imported</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $summary['assignments_imported'] }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Timetable Rows Imported</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $summary['timetable_rows_imported'] }}</p>
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl bg-white shadow-sm">
                    <div class="p-6">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Import Diagnostics</h3>
                                <p class="mt-1 text-sm text-gray-600">Unknown references and timetable conflicts skipped during import.</p>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                                Conflicts Found: {{ $summary['conflicts_found'] }}
                            </span>
                        </div>

                        <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">#</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Sheet</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Row</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Message</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @forelse($summary['errors'] as $i => $error)
                                        <tr>
                                            <td class="px-4 py-2 text-sm text-gray-700">{{ $i + 1 }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-700">{{ $error['sheet'] }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-700">{{ $error['row'] }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-700">{{ $error['message'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-8 text-center text-sm text-emerald-700">No errors found. Import completed cleanly.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

