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
                    <select id="session" name="session" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($sessions as $session)
                            <option value="{{ $session }}" @selected($filters['session'] === $session)>{{ $session }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="class_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Class</label>
                    <select id="class_id" name="class_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select class</option>
                        @foreach($classes as $classRoom)
                            <option value="{{ $classRoom->id }}" @selected((string) $filters['class_id'] === (string) $classRoom->id)>
                                {{ trim($classRoom->name.' '.($classRoom->section ?? '')) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="exam_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Exam</label>
                    <select id="exam_id" name="exam_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Whole class result scope</option>
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

        @if ($filters['class_id'])
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
                Select a class first to manage result locking for a session or exam scope.
            </div>
        @endif
    </div>
</x-app-layout>
