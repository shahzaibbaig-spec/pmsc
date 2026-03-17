<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Student Custom Fee</h2>
            <p class="mt-1 text-sm text-slate-500">Override class fee heads (tuition, computer, exam) for individual students.</p>
        </div>
    </x-slot>

    <div
        class="space-y-6"
        x-data="studentCustomFeePage()"
        x-init="init(@js(old('student_id') ? [
            'student_id' => old('student_id'),
            'student_name' => old('student_name_label', 'Student'),
            'class_name' => old('class_name_label', 'Class'),
            'session' => old('session', $selectedSession),
            'tuition_fee' => old('tuition_fee'),
            'computer_fee' => old('computer_fee'),
            'exam_fee' => old('exam_fee'),
        ] : null))"
    >
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <ul class="list-disc ps-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('principal.fees.student-custom-fees.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <label for="session" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Session</label>
                    <select id="session" name="session" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($sessions as $session)
                            <option value="{{ $session }}" @selected($filters['session'] === $session)>{{ $session }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="class_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Class</label>
                    <select id="class_id" name="class_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Classes</option>
                        @foreach($classes as $classRoom)
                            <option value="{{ $classRoom->id }}" @selected((string) $filters['class_id'] === (string) $classRoom->id)>
                                {{ trim($classRoom->name.' '.($classRoom->section ?? '')) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="search" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Search Student</label>
                    <input
                        id="search"
                        name="search"
                        type="text"
                        value="{{ $filters['search'] }}"
                        placeholder="Name or student ID"
                        class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900">
                        Apply
                    </button>
                    <a href="{{ route('principal.fees.student-custom-fees.index', ['session' => $selectedSession]) }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Reset
                    </a>
                </div>
            </form>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-[1120px] divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Default Fee</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Custom Fee</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($students as $row)
                            @php
                                $student = $row['student'];
                                $defaultBreakdown = $row['default_breakdown'];
                                $custom = $row['custom'];
                                $customBreakdown = $row['custom_breakdown'];
                                $isCustomActive = (bool) ($custom?->is_active ?? false);
                                $modalData = [
                                    'student_id' => (int) $student->id,
                                    'student_name' => $student->name.' ('.$student->student_id.')',
                                    'class_name' => $row['class_name'],
                                    'session' => $filters['session'],
                                    'tuition_fee' => (string) ($custom?->tuition_fee ?? $defaultBreakdown['tuition_fee']),
                                    'computer_fee' => (string) ($custom?->computer_fee ?? $defaultBreakdown['computer_fee']),
                                    'exam_fee' => (string) ($custom?->exam_fee ?? $defaultBreakdown['exam_fee']),
                                ];
                            @endphp
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <div class="font-semibold text-slate-900">{{ $student->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $student->student_id }}</div>
                                    <div class="text-xs text-slate-500">{{ $row['class_name'] }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    @foreach($feeFieldLabels as $field => $label)
                                        <div class="flex items-center justify-between gap-4">
                                            <span class="text-slate-500">{{ $label }}</span>
                                            <span>{{ number_format((float) $defaultBreakdown[$field], 2) }}</span>
                                        </div>
                                    @endforeach
                                    <div class="mt-2 border-t border-slate-200 pt-2 text-sm font-semibold text-slate-900">
                                        Total: {{ number_format((float) $row['default_total'], 2) }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    @if($customBreakdown)
                                        @foreach($feeFieldLabels as $field => $label)
                                            <div class="flex items-center justify-between gap-4">
                                                <span class="text-slate-500">{{ $label }}</span>
                                                <span>{{ number_format((float) $customBreakdown[$field], 2) }}</span>
                                            </div>
                                        @endforeach
                                        <div class="mt-2 border-t border-slate-200 pt-2 text-sm font-semibold text-slate-900">
                                            Total: {{ number_format((float) $row['custom_total'], 2) }}
                                        </div>
                                    @else
                                        <span class="text-slate-500">No custom fee set.</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($isCustomActive)
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Custom Active</span>
                                    @elseif($custom !== null)
                                        <span class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">Custom Inactive</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">Default Fee</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button
                                            type="button"
                                            class="inline-flex min-h-10 items-center rounded-lg border border-indigo-300 px-3 text-xs font-semibold text-indigo-700 hover:bg-indigo-50"
                                            @click="openModal(@js($modalData))"
                                        >
                                            {{ $custom !== null ? 'Edit Custom Fee' : 'Set Custom Fee' }}
                                        </button>

                                        @if($custom !== null && $isCustomActive)
                                            <form method="POST" action="{{ route('principal.fees.student-custom-fees.reset', $custom) }}" onsubmit="return confirm('Reset custom fee for this student?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex min-h-10 items-center rounded-lg border border-rose-300 px-3 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                                    Reset
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">No students found for selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4">
                {{ $students->links() }}
            </div>
        </section>

        <div x-show="modal.open" x-cloak class="fixed inset-0 z-50" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-slate-900/50" @click="closeModal()"></div>
            <div class="absolute inset-x-0 top-6 mx-auto w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Set Student Custom Fee</h3>
                        <p class="mt-1 text-xs text-slate-500" x-text="modal.student_name"></p>
                        <p class="text-xs text-slate-500" x-text="modal.class_name"></p>
                    </div>
                    <button type="button" @click="closeModal()" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-300 text-slate-600 hover:bg-slate-100">
                        <span class="sr-only">Close</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('principal.fees.student-custom-fees.store') }}" class="mt-5 space-y-4">
                    @csrf

                    <input type="hidden" name="student_id" x-model="modal.student_id">
                    <input type="hidden" name="session" x-model="modal.session">
                    <input type="hidden" name="student_name_label" x-model="modal.student_name">
                    <input type="hidden" name="class_name_label" x-model="modal.class_name">

                    <div>
                        <label for="tuition_fee" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Tuition Fee</label>
                        <input id="tuition_fee" name="tuition_fee" type="number" min="0" step="0.01" x-model="modal.tuition_fee" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    </div>

                    <div>
                        <label for="computer_fee" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Computer Fee</label>
                        <input id="computer_fee" name="computer_fee" type="number" min="0" step="0.01" x-model="modal.computer_fee" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    </div>

                    <div>
                        <label for="exam_fee" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Exam Fee</label>
                        <input id="exam_fee" name="exam_fee" type="number" min="0" step="0.01" x-model="modal.exam_fee" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        Session: <span class="font-semibold" x-text="modal.session"></span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            Save Custom Fee
                        </button>
                        <button type="button" @click="closeModal()" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function studentCustomFeePage() {
            return {
                modal: {
                    open: false,
                    student_id: '',
                    student_name: '',
                    class_name: '',
                    session: '',
                    tuition_fee: '',
                    computer_fee: '',
                    exam_fee: '',
                },

                init(oldData) {
                    if (oldData && oldData.student_id) {
                        this.openModal(oldData);
                    }
                },

                openModal(payload) {
                    this.modal.open = true;
                    this.modal.student_id = payload.student_id || '';
                    this.modal.student_name = payload.student_name || 'Student';
                    this.modal.class_name = payload.class_name || 'Class';
                    this.modal.session = payload.session || '';
                    this.modal.tuition_fee = payload.tuition_fee ?? '';
                    this.modal.computer_fee = payload.computer_fee ?? '';
                    this.modal.exam_fee = payload.exam_fee ?? '';
                },

                closeModal() {
                    this.modal.open = false;
                },
            };
        }
    </script>
</x-app-layout>
