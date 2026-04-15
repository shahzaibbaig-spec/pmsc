<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">E Resources</h2>
            <p class="mt-1 text-sm text-slate-500">Class-wise downloadable and printable teaching resources.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <p class="font-semibold">Please fix the following errors:</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (! $teacher)
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-6 text-sm text-amber-800">
                    Teacher profile was not found for this account.
                </div>
            @else
                <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3" x-data="{ q: '' }">
                        <div>
                            <label for="session" class="mb-1 block text-sm font-medium text-slate-700">Session</label>
                            <select
                                id="session"
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                onchange="window.location.href = `{{ route('teacher.e-resources.index') }}?session=${encodeURIComponent(this.value)}`"
                            >
                                @foreach ($sessions as $session)
                                    <option value="{{ $session }}" @selected($selectedSession === $session)>{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label for="resourceSearch" class="mb-1 block text-sm font-medium text-slate-700">Search Resources</label>
                            <input
                                id="resourceSearch"
                                type="text"
                                x-model="q"
                                placeholder="Search by resource name or file type..."
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                        </div>

                        <div class="md:col-span-3 rounded-md border border-indigo-100 bg-indigo-50 px-4 py-3 text-xs text-indigo-700">
                            All resources are grouped class-wise. Use Download for saving locally and Print/Open for browser preview and printing.
                        </div>

                        <div class="md:col-span-3 space-y-6">
                            @foreach ($classResources as $classGroup)
                                <div class="rounded-lg border border-slate-200">
                                    <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                                        <h3 class="text-sm font-semibold text-slate-900">{{ $classGroup['class_name'] }}</h3>
                                    </div>

                                    @if (empty($classGroup['resources']))
                                        <div class="px-4 py-4 text-sm text-slate-500">
                                            No resources currently labeled for this class.
                                        </div>
                                    @else
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-slate-200">
                                                <thead class="bg-white">
                                                    <tr>
                                                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Label</th>
                                                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Type</th>
                                                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Size</th>
                                                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100 bg-white">
                                                    @foreach ($classGroup['resources'] as $resource)
                                                        <tr
                                                            x-show="q.trim() === '' || @js($resource['search_haystack']).includes(q.toLowerCase())"
                                                        >
                                                            <td class="px-4 py-2 text-sm text-slate-800">{{ $resource['label'] }}</td>
                                                            <td class="px-4 py-2 text-sm text-slate-600">{{ $resource['extension'] }}</td>
                                                            <td class="px-4 py-2 text-sm text-slate-600">{{ $resource['size'] }}</td>
                                                            <td class="px-4 py-2 text-sm">
                                                                <div class="flex flex-wrap gap-2">
                                                                    <a
                                                                        href="{{ $resource['download_url'] }}"
                                                                        class="inline-flex min-h-9 items-center rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                                                    >
                                                                        Download
                                                                    </a>
                                                                    @if ($resource['printable'])
                                                                        <a
                                                                            href="{{ $resource['print_url'] }}"
                                                                            target="_blank"
                                                                            rel="noopener"
                                                                            class="inline-flex min-h-9 items-center rounded-md border border-indigo-300 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50"
                                                                        >
                                                                            Print / Open
                                                                        </a>
                                                                    @else
                                                                        <span class="inline-flex min-h-9 items-center rounded-md border border-slate-200 px-3 py-1.5 text-xs text-slate-400">
                                                                            Not Printable
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            @endforeach

                            <div class="rounded-lg border border-slate-200">
                                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                                    <h3 class="text-sm font-semibold text-slate-900">General Resources</h3>
                                </div>

                                @if (empty($generalResources))
                                    <div class="px-4 py-4 text-sm text-slate-500">
                                        No general resources found.
                                    </div>
                                @else
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-slate-200">
                                            <thead class="bg-white">
                                                <tr>
                                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Label</th>
                                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Type</th>
                                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Size</th>
                                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100 bg-white">
                                                @foreach ($generalResources as $resource)
                                                    <tr
                                                        x-show="q.trim() === '' || @js($resource['search_haystack']).includes(q.toLowerCase())"
                                                    >
                                                        <td class="px-4 py-2 text-sm text-slate-800">{{ $resource['label'] }}</td>
                                                        <td class="px-4 py-2 text-sm text-slate-600">{{ $resource['extension'] }}</td>
                                                        <td class="px-4 py-2 text-sm text-slate-600">{{ $resource['size'] }}</td>
                                                        <td class="px-4 py-2 text-sm">
                                                            <div class="flex flex-wrap gap-2">
                                                                <a
                                                                    href="{{ $resource['download_url'] }}"
                                                                    class="inline-flex min-h-9 items-center rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                                                >
                                                                    Download
                                                                </a>
                                                                @if ($resource['printable'])
                                                                    <a
                                                                        href="{{ $resource['print_url'] }}"
                                                                        target="_blank"
                                                                        rel="noopener"
                                                                        class="inline-flex min-h-9 items-center rounded-md border border-indigo-300 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50"
                                                                    >
                                                                        Print / Open
                                                                    </a>
                                                                @else
                                                                    <span class="inline-flex min-h-9 items-center rounded-md border border-slate-200 px-3 py-1.5 text-xs text-slate-400">
                                                                        Not Printable
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

