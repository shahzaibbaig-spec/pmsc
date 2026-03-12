<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Student
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($errors->any())
                        <div class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
                            <ul class="list-disc ps-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.students.update', $student) }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="student_id" value="Student ID" />
                            <x-text-input id="student_id" name="student_id" type="text" class="mt-1 block w-full" :value="old('student_id', $student->student_id)" required />
                        </div>

                        <div>
                            <x-input-label for="name" value="Name" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $student->name)" required />
                        </div>

                        <div>
                            <x-input-label for="father_name" value="Father Name" />
                            <x-text-input id="father_name" name="father_name" type="text" class="mt-1 block w-full" :value="old('father_name', $student->father_name)" />
                        </div>

                        <div>
                            <x-input-label for="class_id" value="Class" />
                            <select id="class_id" name="class_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">Select class</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" @selected(old('class_id', $student->class_id) == $class->id)>
                                        {{ $class->name }} {{ $class->section }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="date_of_birth" value="Date of Birth" />
                            <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="mt-1 block w-full" :value="old('date_of_birth', optional($student->date_of_birth)->format('Y-m-d'))" />
                        </div>

                        <div>
                            <x-input-label for="age" value="Age (optional if DOB given)" />
                            <x-text-input id="age" name="age" type="number" min="1" max="100" class="mt-1 block w-full" :value="old('age', $student->age)" />
                        </div>

                        <div>
                            <x-input-label for="contact" value="Contact" />
                            <x-text-input id="contact" name="contact" type="text" class="mt-1 block w-full" :value="old('contact', $student->contact)" />
                        </div>

                        <div>
                            <x-input-label for="status" value="Status" />
                            <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="active" @selected(old('status', $student->status) === 'active')>active</option>
                                <option value="inactive" @selected(old('status', $student->status) === 'inactive')>inactive</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label for="address" value="Address" />
                            <textarea id="address" name="address" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('address', $student->address) }}</textarea>
                        </div>

                        <div class="md:col-span-2 flex items-center gap-3">
                            <x-primary-button>Update Student</x-primary-button>
                            <a href="{{ route('admin.students.show', $student) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Back to Profile</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

