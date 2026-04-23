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

        $photoPath = trim((string) ($student->photo_path ?? ''));
        $photoUrl = $photoPath !== ''
            ? route('students.photo', ['student' => (int) $student->id, 'v' => optional($student->updated_at)->timestamp])
            : null;

        $profileUser = auth()->user();
        $isAdmin = $profileUser?->hasRole('Admin') ?? false;
        $isPrincipal = $profileUser?->hasRole('Principal') ?? false;
        $canUpdatePhoto = $isAdmin || $isPrincipal;
        $photoUpdateRoute = $canUpdatePhoto
            ? ($isAdmin
                ? route('admin.students.photo.update', $student)
                : route('principal.students.photo.update', $student))
            : null;
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
            resultSessions: @js($resultSessions),
            selectedResultSession: @js($selectedResultSession),
        })"
        x-init="init()"
    >
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex items-start gap-4">
                        @if ($photoUrl)
                            <img
                                src="{{ $photoUrl }}"
                                alt="{{ $student->name }} photo"
                                class="h-16 w-16 shrink-0 rounded-full border border-slate-200 object-cover"
                            >
                        @else
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xl font-semibold text-white">
                                {{ $initials !== '' ? $initials : 'ST' }}
                            </div>
                        @endif
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

                            @if($canUpdatePhoto && $photoUpdateRoute)
                                <div
                                    class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4"
                                    x-data="studentPhotoManager({ initialPhotoUrl: @js($photoUrl) })"
                                >
                                    <form method="POST" action="{{ $photoUpdateRoute }}" enctype="multipart/form-data" class="space-y-3">
                                        @csrf

                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-[120px,1fr]">
                                            <div class="h-28 w-28 overflow-hidden rounded-lg border border-slate-300 bg-white">
                                                <img
                                                    x-show="previewUrl !== ''"
                                                    x-cloak
                                                    :src="previewUrl"
                                                    alt="Photo preview"
                                                    class="h-full w-full object-cover"
                                                >
                                                <div
                                                    x-show="previewUrl === ''"
                                                    class="flex h-full w-full items-center justify-center text-xs text-slate-500"
                                                >
                                                    No Photo
                                                </div>
                                            </div>
                                            <div class="space-y-3">
                                                <div>
                                                    <label for="student_photo_upload" class="text-sm font-medium text-slate-700">Upload Photo</label>
                                                    <input
                                                        x-ref="photoFile"
                                                        id="student_photo_upload"
                                                        name="photo"
                                                        type="file"
                                                        accept="image/png,image/jpeg,image/webp"
                                                        @change="handleFileChange($event)"
                                                        :disabled="removePhoto"
                                                        class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                                                    >
                                                    <p class="mt-1 text-xs text-slate-500">PNG, JPG, JPEG, WEBP up to 3MB.</p>
                                                </div>

                                                <div class="flex flex-wrap items-center gap-2">
                                                    <button
                                                        type="button"
                                                        @click="openCamera()"
                                                        :disabled="removePhoto"
                                                        class="inline-flex min-h-9 items-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
                                                    >
                                                        Open Camera
                                                    </button>
                                                    <button
                                                        type="button"
                                                        x-show="cameraOpen"
                                                        x-cloak
                                                        @click="capturePhoto()"
                                                        class="inline-flex min-h-9 items-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700"
                                                    >
                                                        Capture
                                                    </button>
                                                    <button
                                                        type="button"
                                                        x-show="cameraOpen"
                                                        x-cloak
                                                        @click="stopCamera()"
                                                        class="inline-flex min-h-9 items-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100"
                                                    >
                                                        Stop Camera
                                                    </button>
                                                </div>

                                                <div x-show="cameraOpen" x-cloak class="overflow-hidden rounded-lg border border-slate-300 bg-black">
                                                    <video x-ref="video" class="h-52 w-full object-cover" autoplay playsinline muted></video>
                                                </div>

                                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                                    <input
                                                        type="checkbox"
                                                        name="remove_photo"
                                                        value="1"
                                                        x-model="removePhoto"
                                                        @change="onRemovePhotoToggle($event.target.checked)"
                                                        class="rounded border-slate-300 text-rose-600 focus:ring-rose-500"
                                                    >
                                                    Remove current photo
                                                </label>

                                                <input type="hidden" name="photo_capture" x-model="capturedData">
                                            </div>
                                        </div>

                                        <div
                                            x-show="cameraError !== ''"
                                            x-cloak
                                            class="rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700"
                                            x-text="cameraError"
                                        ></div>

                                        @error('photo')
                                            <div class="rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">
                                                {{ $message }}
                                            </div>
                                        @enderror

                                        @error('photo_capture')
                                            <div class="rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">
                                                {{ $message }}
                                            </div>
                                        @enderror

                                        <div class="flex items-center gap-2">
                                            <button
                                                type="submit"
                                                class="inline-flex min-h-10 items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-black"
                                            >
                                                Save Photo
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            @endif
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
                    <div class="flex flex-wrap items-center justify-between gap-3">
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

                        <div class="flex items-center gap-2">
                            <label for="student_profile_result_session" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Result Session</label>
                            <select
                                id="student_profile_result_session"
                                x-model="selectedResultSession"
                                @change="onResultSessionChanged()"
                                class="min-h-10 rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <template x-for="sessionOption in resultSessions" :key="`result-session-${sessionOption}`">
                                    <option :value="sessionOption" x-text="sessionOption"></option>
                                </template>
                            </select>
                        </div>
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
                resultSessions: config.resultSessions || [],
                selectedResultSession: config.selectedResultSession || '',
                tabHtml: '',
                loading: false,
                errorMessage: '',
                cache: {},

                init() {
                    if (!this.selectedResultSession && this.resultSessions.length > 0) {
                        this.selectedResultSession = this.resultSessions[0];
                    }
                    this.loadTab(this.activeTab);
                },

                tabButtonClass(tab) {
                    if (this.activeTab === tab) {
                        return 'bg-indigo-600 text-white shadow';
                    }

                    return 'bg-slate-100 text-slate-700 hover:bg-slate-200';
                },

                buildEndpoint(tab) {
                    const endpoint = this.endpointTemplate.replace('__TAB__', tab);
                    const url = new URL(endpoint, window.location.origin);

                    if (this.selectedResultSession) {
                        url.searchParams.set('session', this.selectedResultSession);
                    }

                    return url.toString();
                },

                onResultSessionChanged() {
                    this.cache = {};

                    const currentUrl = new URL(window.location.href);
                    if (this.selectedResultSession) {
                        currentUrl.searchParams.set('session', this.selectedResultSession);
                    } else {
                        currentUrl.searchParams.delete('session');
                    }

                    window.history.replaceState({}, '', currentUrl.toString());
                    this.loadTab(this.activeTab, true);
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
                        const endpoint = this.buildEndpoint(tab);
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
                        this.$nextTick(() => {
                            const selector = document.querySelector('[data-result-session-select]');
                            if (!selector) {
                                return;
                            }

                            selector.value = this.selectedResultSession;
                            selector.onchange = (event) => {
                                this.selectedResultSession = event.target.value || '';
                                this.onResultSessionChanged();
                            };
                        });
                    } catch (error) {
                        this.errorMessage = 'Unexpected error while loading tab content.';
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }

        function studentPhotoManager(config) {
            return {
                previewUrl: config.initialPhotoUrl || '',
                capturedData: '',
                removePhoto: false,
                cameraOpen: false,
                cameraError: '',
                stream: null,
                objectUrl: null,

                async openCamera() {
                    this.cameraError = '';
                    if (this.removePhoto) {
                        return;
                    }

                    if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
                        this.cameraError = 'Camera is not supported on this device/browser.';
                        return;
                    }

                    try {
                        this.stream = await navigator.mediaDevices.getUserMedia({
                            video: { facingMode: 'user' },
                            audio: false,
                        });
                        this.cameraOpen = true;
                        await this.$nextTick();

                        if (this.$refs.video) {
                            this.$refs.video.srcObject = this.stream;
                            await this.$refs.video.play();
                        }
                    } catch (error) {
                        this.cameraError = 'Unable to access camera. Please allow permission and try again.';
                        this.stopCamera();
                    }
                },

                stopCamera() {
                    if (this.stream) {
                        this.stream.getTracks().forEach((track) => track.stop());
                    }

                    this.stream = null;
                    this.cameraOpen = false;
                },

                capturePhoto() {
                    this.cameraError = '';
                    const video = this.$refs.video;
                    if (!video) {
                        this.cameraError = 'Camera preview is not ready.';
                        return;
                    }

                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth || 960;
                    canvas.height = video.videoHeight || 720;
                    const context = canvas.getContext('2d');
                    if (!context) {
                        this.cameraError = 'Unable to capture image. Please try again.';
                        return;
                    }

                    context.drawImage(video, 0, 0, canvas.width, canvas.height);
                    this.capturedData = canvas.toDataURL('image/jpeg', 0.85);

                    if (this.objectUrl) {
                        URL.revokeObjectURL(this.objectUrl);
                        this.objectUrl = null;
                    }

                    this.previewUrl = this.capturedData;
                    this.removePhoto = false;
                    this.clearFileInput();
                    this.stopCamera();
                },

                handleFileChange(event) {
                    this.cameraError = '';
                    this.capturedData = '';
                    const file = event.target?.files?.[0] ?? null;

                    if (!file) {
                        return;
                    }

                    if (this.objectUrl) {
                        URL.revokeObjectURL(this.objectUrl);
                    }

                    this.objectUrl = URL.createObjectURL(file);
                    this.previewUrl = this.objectUrl;
                    this.removePhoto = false;
                },

                clearFileInput() {
                    if (this.$refs.photoFile) {
                        this.$refs.photoFile.value = '';
                    }
                },

                onRemovePhotoToggle(checked) {
                    if (!checked) {
                        return;
                    }

                    this.stopCamera();
                    this.capturedData = '';
                    this.previewUrl = '';

                    if (this.objectUrl) {
                        URL.revokeObjectURL(this.objectUrl);
                        this.objectUrl = null;
                    }

                    this.clearFileInput();
                },
            };
        }
    </script>
</x-app-layout>
