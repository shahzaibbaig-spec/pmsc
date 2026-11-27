<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-slate-900">Student Results</h2></x-slot>
    <div class="space-y-5">
        <p class="text-sm text-slate-600">Review recorded marks and published results for all students. Select Class Test Records in the sidebar for class test sheets.</p>
        <form method="GET" action="{{ route('result-checker.results.index') }}" class="grid gap-4 rounded-xl bg-white p-5 md:grid-cols-3">
            <label class="text-sm">Records
                <select name="source" class="mt-1 block w-full rounded-lg border-slate-300">
                    <option value="marks" @selected(!$published)>Recorded marks</option>
                    <option value="published" @selected($published)>Published results</option>
                </select>
            </label>
            <label class="text-sm">Class
                <select name="class_id" class="mt-1 block w-full rounded-lg border-slate-300">
                    <option value="">All classes</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" @selected(($filters['class_id'] ?? '') == $class->id)>{{ trim($class->name.' '.$class->section) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm">Session
                <select name="session" class="mt-1 block w-full rounded-lg border-slate-300">
                    <option value="">All sessions</option>
                    @foreach($sessions as $session)
                        <option @selected(($filters['session'] ?? '') === $session)>{{ $session }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm">Exam type
                <select name="exam_type" class="mt-1 block w-full rounded-lg border-slate-300">
                    <option value="">All exam types</option>
                    @foreach($examTypes as $type)
                        <option value="{{ $type['value'] }}" @selected(($filters['exam_type'] ?? '') === $type['value'])>{{ $type['label'] }}</option>
                    @endforeach
                </select>
            </label>
            <div class="relative" id="checker-student-search" data-url="{{ route('result-checker.students.search') }}">
                <label for="checker-student" class="block text-sm">Student name or ID</label>
                <input id="checker-student" name="student" value="{{ $filters['student'] ?? '' }}" maxlength="150"
                    autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="checker-student-options"
                    class="mt-1 block w-full rounded-lg border-slate-300" placeholder="Type at least 2 characters">
                <input type="hidden" name="student_id" value="{{ $filters['student_id'] ?? '' }}">
                <ul id="checker-student-options" role="listbox" hidden class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg"></ul>
                <p role="status" class="mt-1 text-xs text-slate-500" data-search-status></p>
            </div>
            <div class="flex items-end gap-3"><x-ui.button type="submit">Apply Filters</x-ui.button><a href="{{ route('result-checker.results.index') }}" class="text-sm text-indigo-600">Reset</a></div>
        </form>
        <div class="overflow-x-auto rounded-xl bg-white p-4">
            <table class="w-full text-left text-sm">
                <thead><tr class="border-b"><th class="p-2">Student</th><th class="p-2">Class</th><th class="p-2">Session</th><th class="p-2">Exam</th><th class="p-2">Subject</th><th class="p-2">Marks</th><th class="p-2">Grade</th></tr></thead>
                <tbody>
                    @forelse($records as $record)
                        @php($classRoom = $published ? $record->classRoom : $record->exam?->classRoom)
                        <tr class="border-b border-slate-100">
                            <td class="p-2">{{ $record->student?->name ?? 'Unknown student' }}<span class="block text-xs text-slate-500">{{ $record->student?->student_id }}</span></td>
                            <td class="p-2">{{ trim(($classRoom?->name ?? '').' '.($classRoom?->section ?? '')) }}</td>
                            <td class="p-2">{{ $record->session }}</td>
                            <td class="p-2">{{ $record->exam?->exam_label ?: ($published ? $record->exam_name : $record->exam?->exam_type?->label()) }}</td>
                            <td class="p-2">{{ $published ? $record->subject?->name : $record->exam?->subject?->name }}</td>
                            <td class="p-2">{{ $record->obtained_marks !== null ? $record->obtained_marks.' / '.($record->total_marks ?? $record->exam?->total_marks) : '—' }}</td>
                            <td class="p-2">{{ $record->grade ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-5 text-center text-slate-500">No results match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">{{ $records->links() }}</div>
        </div>
    </div>
    <script>
        (() => {
            const container = document.getElementById('checker-student-search');
            const input = container.querySelector('[name="student"]');
            const selected = container.querySelector('[name="student_id"]');
            const options = container.querySelector('ul');
            const status = container.querySelector('[data-search-status]');
            let timer, requestNumber = 0, active = -1, matches = [];
            const close = () => {
                options.hidden = true;
                input.setAttribute('aria-expanded', 'false');
                input.removeAttribute('aria-activedescendant');
                active = -1;
            };
            const choose = student => {
                selected.value = student.id;
                input.value = student.name;
                ++requestNumber;
                clearTimeout(timer);
                status.textContent = `${student.student_id} selected`;
                close();
                input.focus();
            };
            input.addEventListener('input', () => {
                selected.value = '';
                clearTimeout(timer);
                const current = ++requestNumber;
                close();
                status.textContent = '';
                const term = input.value.trim();
                if (term.length < 2) return;
                timer = setTimeout(async () => {
                    status.textContent = 'Searching…';
                    try {
                        const url = new URL(container.dataset.url, window.location.origin);
                        url.searchParams.set('q', term);
                        const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                        if (!response.ok) throw new Error('Search failed');
                        const data = await response.json();
                        if (current !== requestNumber) return;
                        matches = data.students;
                        options.replaceChildren();
                        matches.forEach((student, index) => {
                            const item = document.createElement('li');
                            item.id = `checker-student-option-${index}`;
                            item.role = 'option';
                            item.className = 'cursor-pointer px-3 py-2 text-sm hover:bg-indigo-50';
                            item.textContent = `${student.name} — ${student.student_id}${student.class ? ' · ' + student.class : ''}`;
                            item.addEventListener('mousedown', event => event.preventDefault());
                            item.addEventListener('click', () => choose(student));
                            options.append(item);
                        });
                        options.hidden = matches.length === 0;
                        input.setAttribute('aria-expanded', String(matches.length > 0));
                        status.textContent = matches.length ? `${matches.length} matches. Select a student, then apply filters.` : 'No students found.';
                    } catch (error) {
                        if (current !== requestNumber) return;
                        close();
                        status.textContent = 'Search unavailable. You can still type a name and apply filters.';
                    }
                }, 300);
            });
            input.addEventListener('keydown', event => {
                if (event.key === 'Escape') {
                    ++requestNumber;
                    clearTimeout(timer);
                    close();
                }
                if (options.hidden) return;
                if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                    event.preventDefault();
                    active = (active + (event.key === 'ArrowDown' ? 1 : -1) + matches.length) % matches.length;
                    [...options.children].forEach((item, index) => {
                        item.classList.toggle('bg-indigo-50', index === active);
                        item.setAttribute('aria-selected', String(index === active));
                    });
                    input.setAttribute('aria-activedescendant', options.children[active].id);
                    options.children[active].scrollIntoView({ block: 'nearest' });
                } else if (event.key === 'Enter' && active >= 0) {
                    event.preventDefault();
                    choose(matches[active]);
                }
            });
            document.addEventListener('click', event => {
                if (!container.contains(event.target)) {
                    ++requestNumber;
                    clearTimeout(timer);
                    close();
                }
            });
        })();
    </script>
</x-app-layout>
