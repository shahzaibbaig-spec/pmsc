<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Student Dashboard
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="text-lg font-medium">Welcome, Student.</p>
                    <p class="text-sm text-gray-600 mt-2">Access attendance, marks, and report card information.</p>

                    <div class="mt-4">
                        <a
                            href="{{ route('student.results.index') }}"
                            class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                        >
                            View My Results
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
