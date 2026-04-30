<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-slate-900">Assign KCAT</h2></x-slot>

    <div class="mx-auto max-w-4xl py-8">
        <form method="POST" action="{{ route('career-counselor.kcat.assignments.store') }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <div>
                <label class="text-sm font-semibold text-slate-700">KCAT Test</label>
                <select name="kcat_test_id" class="mt-1 block w-full rounded-xl border-slate-300 text-sm" required>
                    @foreach ($tests as $test)
                        <option value="{{ $test->id }}">{{ $test->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="text-sm font-semibold text-slate-700">Assign To</label>
                    <select name="assigned_to_type" class="mt-1 block w-full rounded-xl border-slate-300 text-sm" required>
                        <option value="student">Student</option>
                        <option value="class">Class</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Due Date</label>
                    <input type="date" name="due_date" class="mt-1 block w-full rounded-xl border-slate-300 text-sm">
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div><label class="text-sm font-semibold text-slate-700">Student ID</label><input type="number" name="student_id" placeholder="Required for student" class="mt-1 block w-full rounded-xl border-slate-300 text-sm"></div>
                <div><label class="text-sm font-semibold text-slate-700">Class</label><select name="class_id" class="mt-1 block w-full rounded-xl border-slate-300 text-sm"><option value="">Select class</option>@foreach ($classes as $class)<option value="{{ $class->id }}">{{ $class->name }} {{ $class->section }}</option>@endforeach</select></div>
                <div><label class="text-sm font-semibold text-slate-700">Section</label><input name="section" class="mt-1 block w-full rounded-xl border-slate-300 text-sm"></div>
            </div>
            <div class="flex justify-end"><button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Assign</button></div>
        </form>
    </div>
</x-app-layout>
