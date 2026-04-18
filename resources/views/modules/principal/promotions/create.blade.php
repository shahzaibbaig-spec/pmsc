<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Create Promotion Campaign</h2>
                <p class="mt-1 text-sm text-slate-500">Start a new principal-led class promotion workflow for the next academic session.</p>
            </div>
            <a
                href="{{ route('principal.promotions.index') }}"
                class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
            >
                Back to Campaigns
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('error'))
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <ul class="list-disc ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Campaign Details</h3>
            <p class="mt-1 text-xs text-slate-500">Use explicit class promotion mapping. Terminal class without next mapping cannot be normally promoted.</p>

            <form method="POST" action="{{ route('principal.promotions.store') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-4">
                @csrf

                <div>
                    <label for="from_session" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">From Session</label>
                    <select
                        id="from_session"
                        name="from_session"
                        class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        @foreach ($sessionOptions as $session)
                            <option value="{{ $session }}" @selected(old('from_session', $defaultFromSession) === $session)>{{ $session }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="to_session" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">To Session</label>
                    <input
                        id="to_session"
                        name="to_session"
                        type="text"
                        value="{{ old('to_session', $defaultToSession) }}"
                        class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="2027-2028"
                        required
                    >
                </div>

                <div>
                    <label for="class_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Class</label>
                    <select
                        id="class_id"
                        name="class_id"
                        class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="">Select class</option>
                        @foreach ($classOptions as $class)
                            <option value="{{ $class['id'] }}" @selected((int) old('class_id', 0) === (int) $class['id'])>
                                {{ $class['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Create Campaign
                    </button>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>

