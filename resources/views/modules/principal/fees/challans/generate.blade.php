<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Generate Challans
        </h2>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc ps-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900">Class-wise Challan Generation</h3>
                    <p class="mt-1 text-sm text-gray-600">Select session, class, month, and due date. System will generate challans for all active students in that class.</p>

                    <form method="POST" action="{{ route('principal.fees.challans.store') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-4">
                        @csrf

                        <div>
                            <x-input-label for="session" value="Session" />
                            <select id="session" name="session" required class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($sessions as $session)
                                    <option value="{{ $session }}" @selected(old('session', $defaultSession) === $session)>{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="class_id" value="Class" />
                            <select id="class_id" name="class_id" required class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select class</option>
                                @foreach($classes as $classRoom)
                                    <option value="{{ $classRoom->id }}" @selected((string) old('class_id') === (string) $classRoom->id)>
                                        {{ trim($classRoom->name.' '.($classRoom->section ?? '')) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="month" value="Month (YYYY-MM)" />
                            <x-text-input id="month" name="month" type="month" class="mt-1 block min-h-11 w-full" value="{{ old('month', $defaultMonth) }}" required />
                        </div>

                        <div>
                            <x-input-label for="due_date" value="Due Date" />
                            <x-text-input id="due_date" name="due_date" type="date" class="mt-1 block min-h-11 w-full" value="{{ old('due_date', $defaultDueDate) }}" required />
                        </div>

                        <div class="md:col-span-4 flex flex-wrap gap-2">
                            <button type="submit" class="inline-flex min-h-11 items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                Generate Challans
                            </button>
                            <a href="{{ route('principal.fees.challans.index') }}" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                View Challans
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            @if ($latestSummary)
                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="p-5 sm:p-6">
                        <h3 class="text-lg font-medium text-gray-900">Latest Generation Summary</h3>
                        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div class="rounded-md border border-slate-200 p-4">
                                <p class="text-xs uppercase tracking-wider text-slate-500">Class</p>
                                <p class="mt-1 text-sm font-medium text-slate-900">{{ $latestSummary['class_name'] }}</p>
                            </div>
                            <div class="rounded-md border border-slate-200 p-4">
                                <p class="text-xs uppercase tracking-wider text-slate-500">Session / Month</p>
                                <p class="mt-1 text-sm font-medium text-slate-900">{{ $latestSummary['session'] }} / {{ $latestSummary['month_label'] }}</p>
                            </div>
                            <div class="rounded-md border border-slate-200 p-4">
                                <p class="text-xs uppercase tracking-wider text-slate-500">Due Date</p>
                                <p class="mt-1 text-sm font-medium text-slate-900">{{ $latestSummary['due_date'] }}</p>
                            </div>
                            <div class="rounded-md border border-emerald-200 bg-emerald-50 p-4">
                                <p class="text-xs uppercase tracking-wider text-emerald-700">Created</p>
                                <p class="mt-1 text-xl font-semibold text-emerald-800">{{ $latestSummary['created'] }}</p>
                            </div>
                            <div class="rounded-md border border-amber-200 bg-amber-50 p-4">
                                <p class="text-xs uppercase tracking-wider text-amber-700">Skipped Existing</p>
                                <p class="mt-1 text-xl font-semibold text-amber-800">{{ $latestSummary['skipped_existing'] }}</p>
                            </div>
                            <div class="rounded-md border border-slate-200 p-4">
                                <p class="text-xs uppercase tracking-wider text-slate-500">No Billable Heads</p>
                                <p class="mt-1 text-xl font-semibold text-slate-900">{{ $latestSummary['skipped_no_items'] }}</p>
                            </div>
                            <div class="rounded-md border border-indigo-200 bg-indigo-50 p-4">
                                <p class="text-xs uppercase tracking-wider text-indigo-700">Arrears Added</p>
                                <p class="mt-1 text-xl font-semibold text-indigo-800">{{ number_format((float) ($latestSummary['total_arrears_added'] ?? 0), 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
