<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Bulk Print Teacher ACRs</h2>
                <p class="mt-1 text-sm text-slate-500">Generate one PDF containing all selected ACRs for official records.</p>
            </div>
            <a href="{{ route('principal.acr.index', ['session' => $selectedSession]) }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Back to ACR Register
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
            @if (session('error'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <ul class="list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('principal.acr.bulk-print') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="session" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Session</label>
                        <select id="session" name="session" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
                            @foreach ($sessions as $session)
                                <option value="{{ $session }}" @selected(old('session', $selectedSession) === $session)>{{ $session }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="status" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                        <select id="status" name="status" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="all" @selected(old('status', $selectedStatus) === 'all')>All</option>
                            <option value="draft" @selected(old('status', $selectedStatus) === 'draft')>Draft</option>
                            <option value="reviewed" @selected(old('status', $selectedStatus) === 'reviewed')>Reviewed</option>
                            <option value="finalized" @selected(old('status', $selectedStatus) === 'finalized')>Finalized</option>
                        </select>
                    </div>

                    <div class="border-t border-slate-100 pt-4">
                        <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Print All ACRs
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>

