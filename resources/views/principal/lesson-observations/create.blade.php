<x-app-layout>
    @php
        $routeBase = $routeBase ?? 'principal.lesson-observations';
        $panelLabel = $panelLabel ?? 'Principal/Admin';
    @endphp
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">New Lesson Observation</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $panelLabel }} lesson observation form with KORT checklist scoring.</p>
            </div>
            <a href="{{ route($routeBase.'.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
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

        <form method="POST" action="{{ route($routeBase.'.store') }}" class="space-y-6">
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
                        <label for="no_of_students" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">No. of Students</label>
                        <input id="no_of_students" name="no_of_students" type="number" min="0" value="{{ old('no_of_students') }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                    </div>
                    <div>
                        <label for="school" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">School</label>
                        <input id="school" name="school" type="text" value="{{ old('school') }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label for="subject_topic" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Subject / Topic</label>
                        <input id="subject_topic" name="subject_topic" type="text" value="{{ old('subject_topic') }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                    </div>
                    <div class="md:col-span-3">
                        <label for="learning_objectives" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Learning Objectives</label>
                        <textarea id="learning_objectives" name="learning_objectives" rows="2" class="mt-1 block w-full rounded-xl border-slate-300 text-sm">{{ old('learning_objectives') }}</textarea>
                    </div>
                    <div class="md:col-span-3">
                        <label for="previous_targets" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Previous Targets</label>
                        <textarea id="previous_targets" name="previous_targets" rows="2" class="mt-1 block w-full rounded-xl border-slate-300 text-sm">{{ old('previous_targets') }}</textarea>
                    </div>
                    <div class="md:col-span-3">
                        <label for="what_went_well" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">What Went Well</label>
                        <textarea id="what_went_well" name="what_went_well" rows="2" class="mt-1 block w-full rounded-xl border-slate-300 text-sm">{{ old('what_went_well') }}</textarea>
                    </div>
                    <div class="md:col-span-3">
                        <label for="even_better_if" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Even Better If</label>
                        <textarea id="even_better_if" name="even_better_if" rows="2" class="mt-1 block w-full rounded-xl border-slate-300 text-sm">{{ old('even_better_if') }}</textarea>
                    </div>
                    <div>
                        <label for="progress_percentage" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Progress Percentage</label>
                        <input id="progress_percentage" name="progress_percentage" type="number" min="0" max="100" step="0.01" value="{{ old('progress_percentage') }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                    </div>
                    <div class="flex items-center gap-2 pt-7">
                        <input id="observer_signature_acknowledged" name="observer_signature_acknowledged" type="checkbox" value="1" @checked(old('observer_signature_acknowledged')) class="rounded border-slate-300 text-blue-600">
                        <label for="observer_signature_acknowledged" class="text-sm text-slate-700">Observer signature acknowledged</label>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-base font-semibold text-slate-900">KORT Lesson Observation Standards</h3>
                <p class="mt-1 text-sm text-slate-500">Mark each standard as 1 (met) or 0 (not met).</p>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-blue-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Area</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Standard</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Mark</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Comments</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($rows as $index => $item)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-slate-900">
                                        {{ $item['area'] ?? '-' }}
                                        <input type="hidden" name="items[{{ $index }}][area]" value="{{ $item['area'] ?? '' }}">
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        {{ $item['standard_text'] ?? '' }}
                                        <input type="hidden" name="items[{{ $index }}][standard_text]" value="{{ $item['standard_text'] ?? '' }}">
                                        <input type="hidden" name="items[{{ $index }}][max_mark]" value="1">
                                        <input type="hidden" name="items[{{ $index }}][sort_order]" value="{{ $item['sort_order'] ?? $index }}">
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        <select name="items[{{ $index }}][mark]" class="min-h-10 rounded-lg border-slate-300 text-sm">
                                            <option value="1" @selected((string) ($item['mark'] ?? '0') === '1')>1</option>
                                            <option value="0" @selected((string) ($item['mark'] ?? '0') === '0')>0</option>
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
                <a href="{{ route($routeBase.'.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
