<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-slate-900">Bulk Teacher Assignment</h2>
            <a
                href="{{ route('principal.teacher-assignments.index') }}"
                class="inline-flex min-h-10 items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
                Back to Assignments
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-6 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <p class="font-semibold">Please fix the following errors:</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <form
                    method="POST"
                    action="{{ route('principal.teacher-assignments.bulk-store') }}"
                    x-data="teacherBulkAssignmentForm(
                        @js($classes->map(fn ($class) => ['id' => (string) $class->id, 'label' => trim($class->name . ' ' . ($class->section ?? ''))])->values()),
                        @js(collect(old('class_ids', []))->map(fn ($id) => (string) $id)->values()->all()),
                        @js(old('class_teacher_class_id'))
                    )"
                    x-init="init()"
                    class="space-y-6"
                >
                    @csrf

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label for="teacher_id" class="mb-1 block text-sm font-medium text-slate-700">Teacher</label>
                            <select
                                id="teacher_id"
                                name="teacher_id"
                                required
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                                <option value="">Select a teacher</option>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" @selected((string) old('teacher_id') === (string) $teacher->id)>
                                        {{ $teacher->user?->name ?? 'Unknown' }} ({{ $teacher->teacher_id }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="session" class="mb-1 block text-sm font-medium text-slate-700">Academic Session</label>
                            <select
                                id="session"
                                name="session"
                                required
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                                @foreach ($sessions as $session)
                                    <option value="{{ $session }}" @selected(old('session', $defaultSession) === $session)>{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label for="class_ids" class="mb-1 block text-sm font-medium text-slate-700">Classes</label>
                            <select
                                id="class_ids"
                                name="class_ids[]"
                                multiple
                                required
                                x-model="selectedClassIds"
                                class="block h-56 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}">
                                        {{ trim($class->name . ' ' . ($class->section ?? '')) }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-500">Hold Ctrl (Windows) or Command (Mac) to select multiple classes.</p>
                        </div>

                        <div>
                            <label for="subject_ids" class="mb-1 block text-sm font-medium text-slate-700">Subjects</label>
                            <select
                                id="subject_ids"
                                name="subject_ids[]"
                                multiple
                                required
                                class="block h-56 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                                @foreach ($subjects as $subject)
                                    <option value="{{ $subject->id }}" @selected(collect(old('subject_ids', []))->contains((string) $subject->id) || collect(old('subject_ids', []))->contains($subject->id))>
                                        {{ $subject->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-500">Selected subjects will be assigned to all selected classes.</p>
                        </div>
                    </div>

                    <div>
                        <label for="class_teacher_class_id" class="mb-1 block text-sm font-medium text-slate-700">Optional Class Teacher Class</label>
                        <select
                            id="class_teacher_class_id"
                            name="class_teacher_class_id"
                            x-model="classTeacherClassId"
                            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        >
                            <option value="">No class teacher assignment</option>
                            <template x-for="classOption in selectedClassOptions()" :key="classOption.id">
                                <option :value="classOption.id" x-text="classOption.label"></option>
                            </template>
                        </select>
                        <p class="mt-1 text-xs text-slate-500">Optional: choose one of the selected classes to make this teacher the class teacher.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            type="submit"
                            class="inline-flex min-h-10 items-center rounded-md bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Save Bulk Assignments
                        </button>
                        <a
                            href="{{ route('principal.teacher-assignments.index') }}"
                            class="inline-flex min-h-10 items-center rounded-md border border-slate-300 px-5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        >
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function teacherBulkAssignmentForm(classes, initialClassIds, initialClassTeacherClassId) {
            return {
                classes: classes || [],
                selectedClassIds: (initialClassIds || []).map(String),
                classTeacherClassId: initialClassTeacherClassId ? String(initialClassTeacherClassId) : '',
                init() {
                    this.$watch('selectedClassIds', () => {
                        if (this.classTeacherClassId && !this.selectedClassIds.includes(this.classTeacherClassId)) {
                            this.classTeacherClassId = '';
                        }
                    });
                },
                selectedClassOptions() {
                    return this.classes.filter((classOption) => this.selectedClassIds.includes(String(classOption.id)));
                },
            };
        }
    </script>
</x-app-layout>
