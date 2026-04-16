@php
    $dailyDiary = $dailyDiary ?? null;
    $selectedSessionValue = old('session', $selectedSession ?? ($options['selected_session'] ?? ''));
    $selectedClassValue = old('class_id', $dailyDiary?->class_id);
    $selectedSubjectValue = old('subject_id', $dailyDiary?->subject_id);
@endphp

@if ($errors->any())
    <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
        <p class="font-semibold">Please fix the following errors:</p>
        <ul class="mt-2 list-disc space-y-1 ps-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if ($errors->has('daily_diary'))
    <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
        {{ $errors->first('daily_diary') }}
    </div>
@endif

<form
    method="POST"
    action="{{ $action }}"
    class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
    x-data="dailyDiaryForm({
        matrix: @js($options['assignment_matrix'] ?? []),
        session: @js((string) $selectedSessionValue),
        classId: @js($selectedClassValue !== null ? (int) $selectedClassValue : null),
        subjectId: @js($selectedSubjectValue !== null ? (int) $selectedSubjectValue : null),
    })"
    x-init="init()"
>
    @csrf
    @if (strtoupper($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        <div>
            <label for="session" class="mb-1 block text-sm font-medium text-slate-700">Session</label>
            <select
                id="session"
                name="session"
                x-model="session"
                @change="onSessionChange()"
                required
                class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
            >
                @foreach (($options['sessions'] ?? []) as $sessionOption)
                    <option value="{{ $sessionOption }}">{{ $sessionOption }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="class_id" class="mb-1 block text-sm font-medium text-slate-700">Class</label>
            <select
                id="class_id"
                name="class_id"
                x-model="classId"
                @change="onClassChange()"
                required
                class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
            >
                <template x-for="classItem in classOptions" :key="classItem.class_id">
                    <option :value="classItem.class_id" x-text="classItem.class_name"></option>
                </template>
            </select>
            <p class="mt-1 text-xs text-slate-500">Only classes assigned to you are shown.</p>
        </div>

        <div>
            <label for="subject_id" class="mb-1 block text-sm font-medium text-slate-700">Subject</label>
            <select
                id="subject_id"
                name="subject_id"
                x-model="subjectId"
                required
                class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
            >
                <template x-for="subjectItem in subjectOptions" :key="subjectItem.id">
                    <option :value="subjectItem.id" x-text="subjectItem.name"></option>
                </template>
            </select>
            <p class="mt-1 text-xs text-slate-500">Only subjects assigned to this class are shown.</p>
        </div>

        <div>
            <label for="diary_date" class="mb-1 block text-sm font-medium text-slate-700">Date</label>
            <input
                id="diary_date"
                type="date"
                name="diary_date"
                value="{{ old('diary_date', optional($dailyDiary?->diary_date)->toDateString() ?? now()->toDateString()) }}"
                required
                class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
            >
        </div>
    </div>

    <div>
        <label for="title" class="mb-1 block text-sm font-medium text-slate-700">Title (Optional)</label>
        <input
            id="title"
            type="text"
            name="title"
            value="{{ old('title', $dailyDiary?->title) }}"
            maxlength="255"
            placeholder="e.g. Homework for Chapter 3"
            class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
        >
    </div>

    <div>
        <label for="homework_text" class="mb-1 block text-sm font-medium text-slate-700">Homework Text</label>
        <textarea
            id="homework_text"
            name="homework_text"
            rows="6"
            required
            class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
            placeholder="Write complete homework details..."
        >{{ old('homework_text', $dailyDiary?->homework_text) }}</textarea>
    </div>

    <div>
        <label for="instructions" class="mb-1 block text-sm font-medium text-slate-700">Instructions (Optional)</label>
        <textarea
            id="instructions"
            name="instructions"
            rows="4"
            class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
            placeholder="Special notes for students..."
        >{{ old('instructions', $dailyDiary?->instructions) }}</textarea>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
            <input
                type="checkbox"
                name="is_published"
                value="1"
                @checked((int) old('is_published', $dailyDiary?->is_published ?? 1) === 1)
                class="rounded border-slate-300 text-slate-900 shadow-sm focus:ring-slate-500"
            >
            Publish immediately
        </label>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <button
            type="submit"
            class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800"
        >
            {{ $submitLabel ?? 'Save Diary Entry' }}
        </button>
        <a
            href="{{ route('teacher.daily-diary.index') }}"
            class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
        >
            Cancel
        </a>
    </div>
</form>

<script>
    function dailyDiaryForm(config) {
        return {
            matrix: config.matrix || {},
            session: config.session || '',
            classId: config.classId !== null ? Number(config.classId) : null,
            subjectId: config.subjectId !== null ? Number(config.subjectId) : null,
            classOptions: [],
            subjectOptions: [],
            init() {
                this.refreshClasses();
            },
            onSessionChange() {
                this.refreshClasses();
            },
            onClassChange() {
                this.refreshSubjects();
            },
            refreshClasses() {
                const rows = Array.isArray(this.matrix[this.session]) ? this.matrix[this.session] : [];
                this.classOptions = rows;

                if (!this.classOptions.length) {
                    this.classId = null;
                    this.subjectOptions = [];
                    this.subjectId = null;
                    return;
                }

                const classExists = this.classOptions.some((item) => Number(item.class_id) === Number(this.classId));
                if (!classExists) {
                    this.classId = Number(this.classOptions[0].class_id);
                }

                this.refreshSubjects();
            },
            refreshSubjects() {
                const classRow = this.classOptions.find((item) => Number(item.class_id) === Number(this.classId));
                this.subjectOptions = classRow && Array.isArray(classRow.subjects) ? classRow.subjects : [];

                if (!this.subjectOptions.length) {
                    this.subjectId = null;
                    return;
                }

                const subjectExists = this.subjectOptions.some((item) => Number(item.id) === Number(this.subjectId));
                if (!subjectExists) {
                    this.subjectId = Number(this.subjectOptions[0].id);
                }
            },
        };
    }
</script>

