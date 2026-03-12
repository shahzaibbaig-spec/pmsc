<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Mark Entry
        </h2>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
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
                    <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">Student</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $mark->student?->name ?? 'Student' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">Class</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ trim(($mark->exam?->classRoom?->name ?? 'Class').' '.($mark->exam?->classRoom?->section ?? '')) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">Subject</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $mark->exam?->subject?->name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">Exam Type</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $examTypeLabel }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">Entered At</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ optional($mark->created_at)->format('Y-m-d H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">Session</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $mark->session }}</dd>
                        </div>
                    </dl>

                    <form method="POST" action="{{ route('teacher.marks.entries.update', $mark) }}" class="mt-6 space-y-4">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="obtained_marks" value="Obtained Marks" />
                                <x-text-input
                                    id="obtained_marks"
                                    name="obtained_marks"
                                    type="number"
                                    min="0"
                                    max="{{ $mark->total_marks }}"
                                    class="mt-1 block min-h-11 w-full"
                                    :value="old('obtained_marks', $mark->obtained_marks)"
                                    required
                                />
                            </div>

                            <div>
                                <x-input-label for="total_marks" value="Total Marks" />
                                <x-text-input
                                    id="total_marks"
                                    type="number"
                                    class="mt-1 block min-h-11 w-full bg-gray-100"
                                    :value="$mark->total_marks"
                                    readonly
                                />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="edit_reason" value="Edit Reason" />
                            <textarea
                                id="edit_reason"
                                name="edit_reason"
                                rows="3"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Reason for modifying this mark entry"
                            >{{ old('edit_reason') }}</textarea>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button type="submit" class="inline-flex min-h-11 items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                Update Entry
                            </button>
                            <a href="{{ route('teacher.marks.entries.index') }}" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Back
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
