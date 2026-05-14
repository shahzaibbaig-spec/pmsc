<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">New Notebook Observation</h2>
                <p class="mt-1 text-sm text-slate-500">Evaluate notebook quality using Yes/No/N/A checklist.</p>
            </div>
            <a href="{{ route('principal.notebook-observations.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Back to List
            </a>
        </div>
    </x-slot>

    @php
        $rows = old('items', $templateItems);
    @endphp

    <div class="mx-auto max-w-7xl space-y-6 py-8">
        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <ul class="list-disc ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('principal.notebook-observations.store') }}" class="space-y-6">
            @csrf

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-base font-semibold text-slate-900">Observation Details</h3>
                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label for="observed_teacher_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Observed Teacher</label>
                        <select id="observed_teacher_id" name="observed_teacher_id" required class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                            <option value="">Select Teacher</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected((int) old('observed_teacher_id') === (int) $teacher->id)>{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="session" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Session</label>
                        <select id="session" name="session" required class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                            @foreach ($sessions as $session)
                                <option value="{{ $session }}" @selected(old('session', $selected_session) === $session)>{{ $session }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="observation_date" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Observation Date</label>
                        <input id="observation_date" name="observation_date" type="date" value="{{ old('observation_date', now()->toDateString()) }}" required class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                    </div>
                    <div>
                        <label for="class_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Class</label>
                        <select id="class_id" name="class_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                            <option value="">Select Class (optional)</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class['id'] }}" @selected((int) old('class_id') === (int) $class['id'])>{{ $class['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="class_section" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Class Section</label>
                        <input id="class_section" name="class_section" type="text" value="{{ old('class_section') }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                    </div>
                    <div>
                        <label for="subject_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Subject</label>
                        <select id="subject_id" name="subject_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                            <option value="">Select Subject (optional)</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject['id'] }}" @selected((int) old('subject_id') === (int) $subject['id'])>{{ $subject['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="total_students" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Total Students</label>
                        <input id="total_students" name="total_students" type="number" min="0" value="{{ old('total_students') }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                    </div>
                    <div>
                        <label for="notebooks_provided" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Notebooks Provided</label>
                        <input id="notebooks_provided" name="notebooks_provided" type="number" min="0" value="{{ old('notebooks_provided') }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                    </div>
                    <div>
                        <label for="covered_notebooks" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Covered Notebooks</label>
                        <input id="covered_notebooks" name="covered_notebooks" type="number" min="0" value="{{ old('covered_notebooks') }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                    </div>
                    <div>
                        <label for="uncovered_notebooks" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Uncovered Notebooks</label>
                        <input id="uncovered_notebooks" name="uncovered_notebooks" type="number" min="0" value="{{ old('uncovered_notebooks') }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                    </div>
                    <div>
                        <label for="well_maintained" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Well Maintained</label>
                        <input id="well_maintained" name="well_maintained" type="number" min="0" value="{{ old('well_maintained') }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                    </div>
                    <div class="md:col-span-3">
                        <label for="general_comments" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">General Comments</label>
                        <textarea id="general_comments" name="general_comments" rows="2" class="mt-1 block w-full rounded-xl border-slate-300 text-sm">{{ old('general_comments') }}</textarea>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-base font-semibold text-slate-900">Notebook Review Checklist</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-blue-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Checklist Item</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Response</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Comments</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($rows as $index => $item)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-slate-900">
                                        {{ $item['checklist_text'] ?? '-' }}
                                        <input type="hidden" name="items[{{ $index }}][checklist_text]" value="{{ $item['checklist_text'] ?? '' }}">
                                        <input type="hidden" name="items[{{ $index }}][sort_order]" value="{{ $item['sort_order'] ?? $index }}">
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        <select name="items[{{ $index }}][response]" class="min-h-10 rounded-lg border-slate-300 text-sm">
                                            @foreach ($responses as $response)
                                                <option value="{{ $response }}" @selected(($item['response'] ?? 'na') === $response)>{{ strtoupper($response) }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        <input type="text" name="items[{{ $index }}][comments]" value="{{ $item['comments'] ?? '' }}" class="min-h-10 w-full rounded-lg border-slate-300 text-sm" placeholder="Optional comment">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="flex flex-wrap gap-2">
                <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Submit Observation
                </button>
                <a href="{{ route('principal.notebook-observations.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
