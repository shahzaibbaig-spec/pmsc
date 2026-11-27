<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Result Locks</h2>
            <p class="mt-1 text-sm text-slate-500">Control review locks and final freezes for each class and exam scope.</p>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="{ unlockOpen: false }">
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
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

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Filters</h3>
            <form method="GET" action="{{ route('principal.result-locks.index') }}" class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-4">
                <div>
                    <label for="session" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Session</label>
                    <select id="session" name="session" onchange="this.form.exam_id.value = ''; this.form.subject_id.value = ''; this.form.requestSubmit()" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($sessions as $session)
                            <option value="{{ $session }}" @selected($filters['session'] === $session)>{{ $session }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="class_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Class</label>
                    <select id="class_id" name="class_id" onchange="this.form.exam_id.value = ''; this.form.subject_id.value = ''; this.form.requestSubmit()" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select class</option>
                        @foreach($classes as $classRoom)
                            <option value="{{ $classRoom->id }}" @selected((string) $filters['class_id'] === (string) $classRoom->id)>
                                {{ trim($classRoom->name.' '.($classRoom->section ?? '')) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="subject_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Subject</label>
                    <select id="subject_id" name="subject_id" onchange="this.form.exam_id.value = ''; this.form.requestSubmit()" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                        <option value="">All subjects</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" @selected((string) $filters['subject_id'] === (string) $subject->id)>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="exam_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Exam</label>
                    <select id="exam_id" name="exam_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">{{ $filters['subject_id'] ? 'Select an exam or test' : 'Whole class result scope' }}</option>
                        @foreach($exams as $exam)
                            <option value="{{ $exam->id }}" @selected((string) $filters['exam_id'] === (string) $exam->id)>
                                {{ $examLabelResolver($exam) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Apply
                    </button>
                    <a href="{{ route('principal.result-locks.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Reset
                    </a>
                </div>
            </form>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-semibold text-slate-900">Recently unlocked exams and tests</h3>
            <p class="mt-1 text-xs text-slate-500">Latest unlock for each exam, up to 25, matching the session, class and subject filters. Current status includes correction timers and class-wide locks.</p>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50"><tr><th class="p-2">Class / Exam</th><th class="p-2">Unlocked by</th><th class="p-2">Unlocked at</th><th class="p-2">Current status / Deadline</th><th class="p-2">Action</th></tr></thead>
                    <tbody>
                        @forelse($recentUnlocks as $item)
                            @php($log = $item['log'])
                            @php($exam = $log->exam)
                            <tr class="border-b border-slate-100">
                                <td class="p-2 align-top">{{ trim(($exam->classRoom?->name ?? '').' '.($exam->classRoom?->section ?? '')) }}<br>{{ $examLabelResolver($exam) }}</td>
                                <td class="p-2 align-top">{{ $log->performer?->name ?? 'Unknown user' }}</td>
                                <td class="p-2 align-top">{{ $log->created_at->timezone('Asia/Karachi')->format('d M Y, h:i A') }} PKT</td>
                                <td class="p-2 align-top">
                                    @if($item['status']['is_locked'])
                                        Locked{{ $exam->correctionWindowExpired() ? ' — timer expired' : '' }}
                                    @elseif($exam->correctionsAreOpen())
                                        Corrections open<br>{{ $exam->corrections_close_at ? 'Locks '.$exam->corrections_close_at->timezone('Asia/Karachi')->format('d M Y, h:i A').' PKT' : 'Until manually locked' }}
                                    @else
                                        Open
                                    @endif
                                </td>
                                <td class="p-2 align-top">
                                    <a class="text-indigo-600 underline" href="{{ route('principal.result-locks.index', ['session' => $log->session, 'class_id' => $log->class_id, 'subject_id' => $exam->subject_id, 'exam_id' => $exam->id]) }}">Manage test</a>
                                    @if(! $item['status']['is_locked'])
                                        <form method="POST" action="{{ route('principal.result-locks.corrections.close') }}" class="mt-2 space-y-2">
                                            @csrf
                                            <input type="hidden" name="session" value="{{ $log->session }}">
                                            <input type="hidden" name="class_id" value="{{ $log->class_id }}">
                                            <input type="hidden" name="subject_id" value="{{ $exam->subject_id }}">
                                            <input type="hidden" name="exam_id" value="{{ $exam->id }}">
                                            <input name="reason" required maxlength="2000" aria-label="Reason for locking {{ $examLabelResolver($exam) }}" placeholder="Reason for locking" class="w-full rounded-lg border-slate-300 text-sm">
                                            <x-ui.button type="submit">Lock again</x-ui.button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-4 text-slate-500">No recently unlocked exams match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if ($filters['class_id'] && (! $filters['subject_id'] || $filters['exam_id']))
            <section class="rounded-2xl border border-indigo-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-900">Exam correction window</h3>
                <p class="mt-1 text-sm text-slate-600">Select a specific exam above and press Apply. Reopen it to let its assigned teachers correct grades or marks, including entries older than seven days. Class-wide locks must be unlocked separately.</p>
                @if($correctionExam)
                    <p class="mt-3 text-sm font-medium">
                        {{ $examLabelResolver($correctionExam) }}:
                        @if($correctionExam->correctionsAreOpen())
                            Corrections open
                            @if($correctionExam->corrections_close_at)
                                until {{ $correctionExam->corrections_close_at->timezone('Asia/Karachi')->format('d M Y, h:i A') }} (Pakistan time).
                            @else
                                until manually locked.
                            @endif
                        @elseif($correctionExam->correctionWindowExpired())
                            Timer expired — entries are locked again.
                        @else
                            {{ $correctionExam->entriesAreLocked() ? 'Entries locked.' : 'Within the normal editing period.' }}
                        @endif
                    </p>
                    <form method="POST" action="{{ route('principal.result-locks.corrections.open') }}" class="mt-4 grid gap-4 md:grid-cols-3">
                        @csrf
                        <input type="hidden" name="session" value="{{ $filters['session'] }}">
                        <input type="hidden" name="class_id" value="{{ $filters['class_id'] }}">
                        <input type="hidden" name="exam_id" value="{{ $filters['exam_id'] }}">
                                <input type="hidden" name="subject_id" value="{{ $filters['subject_id'] }}">
                        <x-ui.input name="reason" id="correction_reason" label="Reason for corrections" required maxlength="2000" />
                        <div>
                            <x-ui.input name="correction_minutes" label="Automatically lock again after (minutes)" type="number" min="1" max="43200" :value="old('correction_minutes')" />
                            <p class="mt-1 text-xs text-slate-500">Leave empty to keep open until manually locked. Maximum 30 days.</p>
                        </div>
                        <div class="flex items-end"><x-ui.button type="submit">Reopen for corrections</x-ui.button></div>
                    </form>
                    <form method="POST" action="{{ route('principal.result-locks.corrections.close') }}" class="mt-4 flex flex-wrap items-end gap-3">
                        @csrf
                        <input type="hidden" name="session" value="{{ $filters['session'] }}">
                        <input type="hidden" name="class_id" value="{{ $filters['class_id'] }}">
                        <input type="hidden" name="exam_id" value="{{ $filters['exam_id'] }}">
                                <input type="hidden" name="subject_id" value="{{ $filters['subject_id'] }}">
                        <x-ui.input name="reason" id="relock_reason" label="Reason for locking again" required maxlength="2000" />
                        <x-ui.button type="submit">Lock exam now</x-ui.button>
                    </form>
                @endif
            </section>
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
                <section class="space-y-6 xl:col-span-4">
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">Current Status</h3>
                                <p class="mt-1 text-xs text-slate-500">Selected session/class/exam scope.</p>
                            </div>
                            @if (($status['lock_type'] ?? null) === 'final')
                                <span class="inline-flex rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700">Final Locked</span>
                            @elseif (($status['lock_type'] ?? null) === 'soft')
                                <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Soft Locked</span>
                            @elseif ($status['is_locked'] ?? false)
                                <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Entry Locked</span>
                            @else
                                <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Not Locked</span>
                            @endif
                        </div>

                        <div class="mt-4 rounded-xl border {{ ($status['lock_type'] ?? null) === 'final' ? 'border-rose-200 bg-rose-50' : (($status['lock_type'] ?? null) === 'soft' ? 'border-amber-200 bg-amber-50' : 'border-emerald-200 bg-emerald-50') }} px-4 py-3 text-sm {{ ($status['lock_type'] ?? null) === 'final' ? 'text-rose-700' : (($status['lock_type'] ?? null) === 'soft' ? 'text-amber-800' : 'text-emerald-700') }}">
                            {{ $status['message'] ?? 'This result scope is open for edits.' }}
                        </div>

                        <div class="mt-4 space-y-3">
                            <form method="POST" action="{{ route('principal.result-locks.lock') }}">
                                @csrf
                                <input type="hidden" name="session" value="{{ $filters['session'] }}">
                                <input type="hidden" name="class_id" value="{{ $filters['class_id'] }}">
                                <input type="hidden" name="exam_id" value="{{ $filters['exam_id'] }}">
                                <input type="hidden" name="subject_id" value="{{ $filters['subject_id'] }}">
                                <input type="hidden" name="lock_type" value="soft">
                                <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">
                                    Soft Lock Results
                                </button>
                            </form>

                            <form method="POST" action="{{ route('principal.result-locks.lock') }}" x-data="{ confirmFinal: false }" class="space-y-3">
                                @csrf
                                <input type="hidden" name="session" value="{{ $filters['session'] }}">
                                <input type="hidden" name="class_id" value="{{ $filters['class_id'] }}">
                                <input type="hidden" name="exam_id" value="{{ $filters['exam_id'] }}">
                                <input type="hidden" name="subject_id" value="{{ $filters['subject_id'] }}">
                                <input type="hidden" name="lock_type" value="final">
                                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                                    This action will permanently freeze results. No edits will be allowed.
                                </div>
                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500" x-model="confirmFinal">
                                    <span>I understand this final lock freezes the selected result scope.</span>
                                </label>
                                <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50" :disabled="!confirmFinal">
                                    Final Lock Results
                                </button>
                            </form>

                            <button type="button" @click="unlockOpen = !unlockOpen" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Unlock Results
                            </button>

                            <form method="POST" action="{{ route('principal.result-locks.unlock') }}" x-show="unlockOpen" x-cloak class="space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                @csrf
                                <input type="hidden" name="session" value="{{ $filters['session'] }}">
                                <input type="hidden" name="class_id" value="{{ $filters['class_id'] }}">
                                <input type="hidden" name="exam_id" value="{{ $filters['exam_id'] }}">
                                <input type="hidden" name="subject_id" value="{{ $filters['subject_id'] }}">
                                <div>
                                    <label for="reason" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Unlock Reason</label>
                                    <textarea id="reason" name="reason" rows="3" required class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Why is this result scope being reopened?">{{ old('reason') }}</textarea>
                                </div>
                                <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">
                                    Confirm Unlock
                                </button>
                            </form>
                        </div>
                    </article>
                </section>

                <section class="space-y-6 xl:col-span-8">
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-900">Active Locks</h3>
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Scope</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Locked By</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Locked At</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white">
                                    @forelse($activeLocks as $lock)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-slate-700">
                                                <div class="font-medium">{{ trim(($lock->classRoom?->name ?? 'Class').' '.($lock->classRoom?->section ?? '')) }}</div>
                                                <div class="text-xs text-slate-500">{{ $lock->session }} | {{ $examLabelResolver($lock->exam) }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $lock->lock_type === 'final' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700' }}">
                                                    {{ $lock->lock_type === 'final' ? 'Final' : 'Soft' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-slate-700">{{ $lock->locker?->name ?? 'System' }}</td>
                                            <td class="px-4 py-3 text-sm text-slate-700">{{ optional($lock->locked_at)->format('Y-m-d H:i') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">No active locks found for the selected filters.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-900">Audit Log</h3>
                        <div class="mt-4 space-y-3">
                            @forelse($recentLogs as $log)
                                <div class="rounded-xl border border-slate-200 px-4 py-3">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900">
                                                {{ strtoupper($log->action) }} | {{ strtoupper($log->lock_type) }}
                                            </p>
                                            <p class="mt-1 text-xs text-slate-500">
                                                {{ trim(($log->classRoom?->name ?? 'Class').' '.($log->classRoom?->section ?? '')) }} | {{ $log->session }} | {{ $examLabelResolver($log->exam) }}
                                            </p>
                                        </div>
                                        <p class="text-xs text-slate-500">{{ optional($log->created_at)->format('Y-m-d H:i') }}</p>
                                    </div>
                                    <p class="mt-2 text-sm text-slate-700">By {{ $log->performer?->name ?? 'System' }}</p>
                                    @if ($log->reason)
                                        <p class="mt-2 text-sm text-slate-600">{{ $log->reason }}</p>
                                    @endif
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">
                                    No lock audit activity found yet.
                                </div>
                            @endforelse
                        </div>
                    </article>
                </section>
            </div>
        @else
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Select a class and, when filtering by subject, a specific exam or test to manage its lock.
            </div>
        @endif
    </div>
</x-app-layout>
