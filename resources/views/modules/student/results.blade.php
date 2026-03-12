<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            My Results
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($message)
                        <div class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                            {{ $message }}
                        </div>
                    @elseif (!$student)
                        <div class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                            No student profile found.
                        </div>
                    @else
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                            <div class="rounded-md border border-gray-200 bg-gray-50 p-3">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Student ID</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900">{{ $student->student_id }}</p>
                            </div>
                            <div class="rounded-md border border-gray-200 bg-gray-50 p-3">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Name</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900">{{ $student->name }}</p>
                            </div>
                            <div class="rounded-md border border-gray-200 bg-gray-50 p-3">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Class</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900">{{ trim(($student->classRoom?->name ?? '').' '.($student->classRoom?->section ?? '')) ?: '-' }}</p>
                            </div>
                            <div class="rounded-md border border-gray-200 bg-gray-50 p-3">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Total Exam Groups</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900">{{ $groupedResults->count() }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @if ($student && !$groupedResults->isEmpty())
                @foreach ($groupedResults as $examName => $examResult)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <h3 class="text-lg font-semibold text-gray-900">{{ $examName }}</h3>
                                <span class="inline-flex rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">
                                    Overall: {{ $examResult['summary']['percentage'] }}% ({{ $examResult['summary']['grade'] }})
                                </span>
                            </div>

                            <div class="mt-4 overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Subject</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Total</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Obtained</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">%</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Grade</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        @foreach ($examResult['rows'] as $row)
                                            <tr>
                                                <td class="px-3 py-2 text-sm text-gray-900">{{ $row['subject'] }}</td>
                                                <td class="px-3 py-2 text-sm text-gray-700">{{ $row['total_marks'] }}</td>
                                                <td class="px-3 py-2 text-sm text-gray-700">{{ $row['obtained_marks'] }}</td>
                                                <td class="px-3 py-2 text-sm text-gray-700">{{ $row['percentage'] }}</td>
                                                <td class="px-3 py-2 text-sm text-gray-700">{{ $row['grade'] }}</td>
                                                <td class="px-3 py-2 text-sm text-gray-700">{{ $row['result_date'] ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-sm font-semibold text-gray-900">Totals</th>
                                            <th class="px-3 py-2 text-left text-sm font-semibold text-gray-900">{{ $examResult['summary']['total_marks'] }}</th>
                                            <th class="px-3 py-2 text-left text-sm font-semibold text-gray-900">{{ $examResult['summary']['obtained_marks'] }}</th>
                                            <th class="px-3 py-2 text-left text-sm font-semibold text-gray-900">{{ $examResult['summary']['percentage'] }}</th>
                                            <th class="px-3 py-2 text-left text-sm font-semibold text-gray-900">{{ $examResult['summary']['grade'] }}</th>
                                            <th class="px-3 py-2 text-left text-sm font-semibold text-gray-900">-</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach
            @elseif ($student)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-sm text-gray-600">
                        No results are available yet for your profile.
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

