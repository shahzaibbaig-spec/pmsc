<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">School Settings</h2>
    </x-slot>

    <div class="mx-auto max-w-4xl">
        <x-ui.card title="School Name & Logo" subtitle="Admin can update the branding used in dashboard and PDF reports.">
            @if (session('status'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <ul class="list-disc ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <div>
                    <x-ui.input
                        label="School Name"
                        id="school_name"
                        name="school_name"
                        :value="old('school_name', $setting->school_name)"
                        required
                    />
                </div>

                <div>
                    <label for="logo" class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
                        School Logo
                    </label>
                    <input
                        id="logo"
                        name="logo"
                        type="file"
                        accept=".jpg,.jpeg,.png,.webp"
                        class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                    >
                    <p class="mt-1 text-xs text-slate-500">Allowed: JPG, PNG, WEBP. Max size: 4MB.</p>
                </div>

                @if($logoUrl)
                    <div>
                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">Current Logo</p>
                        <img src="{{ $logoUrl }}" alt="Current School Logo" class="h-24 w-24 rounded-lg border border-slate-200 object-contain">
                    </div>
                @endif

                @if($supportsDefaulterBlocks)
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-900">Fee Defaulter Blocks</p>
                        <p class="mt-1 text-xs text-slate-500">Control whether official cards are blocked for active fee defaulters.</p>

                        <div class="mt-4 space-y-3">
                            <label class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2">
                                <span class="text-sm text-slate-700">Block result cards for fee defaulters</span>
                                <input
                                    type="checkbox"
                                    name="block_results_for_defaulters"
                                    value="1"
                                    @checked(old('block_results_for_defaulters', $setting->block_results_for_defaulters))
                                    class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                >
                            </label>

                            <label class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2">
                                <span class="text-sm text-slate-700">Block admit cards for fee defaulters</span>
                                <input
                                    type="checkbox"
                                    name="block_admit_card_for_defaulters"
                                    value="1"
                                    @checked(old('block_admit_card_for_defaulters', $setting->block_admit_card_for_defaulters))
                                    class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                >
                            </label>

                            <label class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2">
                                <span class="text-sm text-slate-700">Block ID cards for fee defaulters</span>
                                <input
                                    type="checkbox"
                                    name="block_id_card_for_defaulters"
                                    value="1"
                                    @checked(old('block_id_card_for_defaulters', $setting->block_id_card_for_defaulters))
                                    class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                >
                            </label>
                        </div>
                    </div>
                @endif

                <div class="pt-2">
                    <x-ui.button type="submit">Save Settings</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-app-layout>
