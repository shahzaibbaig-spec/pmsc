<x-app-layout>
    @php
        $statusValue = strtolower((string) ($student->status ?? 'inactive'));
        $statusClasses = match ($statusValue) {
            'active' => 'bg-emerald-100 text-emerald-700',
            'inactive' => 'bg-rose-100 text-rose-700',
            default => 'bg-slate-100 text-slate-700',
        };

        $initials = collect(preg_split('/\s+/', trim((string) $student->name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(substr((string) $part, 0, 1)))
            ->implode('');

        $profileUser = auth()->user();
        $isAdmin = $profileUser?->hasRole('Admin') ?? false;
        $canGenerateResults = $profileUser?->can('generate_results') ?? false;
        $hasFinanceRole = $profileUser?->hasAnyRole(['Admin', 'Accountant']) ?? false;
        $canGenerateChallans = $hasFinanceRole && ($profileUser?->can('generate_fee_challans') ?? false);
    @endphp

    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Student Profile Dashboard</h2>
            <p class="mt-1 text-sm text-slate-500">Academic, attendance, fee, medical, and discipline profile in one view.</p>
        </div>
    </x-slot>

    <div
        class="py-8"
        x-data="studentProfileDashboard({
            tabs: @js($tabs),
            defaultTab: 'overview',
            endpointTemplate: @js($tabEndpointTemplate),
        })"
        x-init="init()"
    >
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xl font-semibold text-white">
                            {{ $initials !== '' ? $initials : 'ST' }}
                        </div>
                        <div>
                            <h3 class="text-2xl font-semibold text-slate-900">{{ $student->name }}</h3>
                            <p class="mt-1 text-sm text-slate-500">Student ID: {{ $student->student_id }}</p>
                            <div class="mt-3 grid grid-cols-1 gap-x-8 gap-y-2 text-sm text-slate-700 sm:grid-cols-2">
                                <p><span class="font-medium text-slate-900">Class:</span> {{ trim(($student->classRoom?->name ?? '').' '.($student->classRoom?->section ?? '')) ?: '-' }}</p>
                                <p><span class="font-medium text-slate-900">Father Name:</span> {{ $student->father_name ?: '-' }}</p>
                                <p><span class="font-medium text-slate-900">Contact:</span> {{ $student->contact ?: '-' }}</p>
                                <p>
                                    <span class="font-medium text-slate-900">Status:</span>
                                    <span class="ms-2 inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClasses }}">
                                        {{ ucfirst($statusValue) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @if($isAdmin)
                            <a
                                href="{{ route('admin.students.edit', $student) }}"
                                class="inline-flex min-h-10 items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                            >
                                Edit Profile
                            </a>
                        @else
                            <button
                                type="button"
                                disabled
                                class="inline-flex min-h-10 items-center rounded-md border border-slate-200 bg-slate-100 px-4 py-2 text-sm font-medium text-slate-400"
                            >
                                Edit Profile
                            </button>
                        @endif

                        @if($canGenerateResults)
                            <a
                                href="{{ route('principal.results.generator', ['class_id' => $student->class_id, 'student_id' => $student->id]) }}"
                                class="inline-flex min-h-10 items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                            >
                                Generate Result
                            </a>
                        @else
                            <button
                                type="button"
                                disabled
                                class="inline-flex min-h-10 items-center rounded-md bg-indigo-300 px-4 py-2 text-sm font-medium text-white/80"
                            >
                                Generate Result
                            </button>
                        @endif

                        @if($canGenerateChallans)
                            <a
                                href="{{ route('principal.fees.challans.generate', ['class_id' => $student->class_id, 'student_id' => $student->id]) }}"
                                class="inline-flex min-h-10 items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"
                            >
                                Generate Fee Challan
                            </a>
                        @else
                            <button
                                type="button"
                                disabled
                                class="inline-flex min-h-10 items-center rounded-md bg-emerald-300 px-4 py-2 text-sm font-medium text-white/80"
                            >
                                Generate Fee Challan
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Attendance %</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900">{{ number_format((float) ($summaryStats['attendance_percentage'] ?? 0), 2) }}%</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Current Grade</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $summaryStats['current_grade'] ?? 'N/A' }}</p>
                    <p class="mt-1 text-xs text-slate-500">Average: {{ number_format((float) ($summaryStats['current_grade_percentage'] ?? 0), 2) }}%</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Pending Fee</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900">Rs. {{ number_format((float) ($summaryStats['pending_fee'] ?? 0), 2) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Medical Visits</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900">{{ (int) ($summaryStats['medical_visits'] ?? 0) }}</p>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <div class="flex flex-wrap gap-2">
                        @foreach($tabs as $tabKey => $tabLabel)
                            <button
                                type="button"
                                @click="loadTab('{{ $tabKey }}')"
                                class="inline-flex min-h-10 items-center rounded-md px-4 py-2 text-sm font-medium transition"
                                :class="tabButtonClass('{{ $tabKey }}')"
                            >
                                {{ $tabLabel }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="p-5">
                    <div x-show="loading" class="space-y-3">
                        <div class="h-5 w-40 animate-pulse rounded bg-slate-200"></div>
                        <div class="h-24 animate-pulse rounded-lg bg-slate-100"></div>
                        <div class="h-24 animate-pulse rounded-lg bg-slate-100"></div>
                    </div>

                    <div
                        x-show="errorMessage !== ''"
                        x-cloak
                        class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
                        x-text="errorMessage"
                    ></div>

                    <div x-show="!loading && errorMessage === ''" x-html="tabHtml"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function studentProfileDashboard(config) {
            return {
                tabs: config.tabs || {},
                endpointTemplate: config.endpointTemplate || '',
                activeTab: config.defaultTab || 'overview',
                tabHtml: '',
                loading: false,
                errorMessage: '',
                cache: {},

                init() {
                    this.loadTab(this.activeTab);
                },

                tabButtonClass(tab) {
                    if (this.activeTab === tab) {
                        return 'bg-indigo-600 text-white shadow';
                    }

                    return 'bg-slate-100 text-slate-700 hover:bg-slate-200';
                },

                async loadTab(tab, force = false) {
                    if (!this.tabs[tab]) {
                        return;
                    }

                    this.activeTab = tab;
                    this.errorMessage = '';

                    if (!force && this.cache[tab]) {
                        this.tabHtml = this.cache[tab];
                        return;
                    }

                    this.loading = true;
                    try {
                        const endpoint = this.endpointTemplate.replace('__TAB__', tab);
                        const response = await fetch(endpoint, {
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        const result = await response.json();
                        if (!response.ok) {
                            this.errorMessage = result.message || 'Failed to load tab content.';
                            return;
                        }

                        this.cache[tab] = result.html || '';
                        this.tabHtml = this.cache[tab];
                    } catch (error) {
                        this.errorMessage = 'Unexpected error while loading tab content.';
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>
</x-app-layout>
