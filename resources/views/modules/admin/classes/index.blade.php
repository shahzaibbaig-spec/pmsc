<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Classes</h2>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900">{{ $editingClass ? 'Edit Class' : 'Add Class' }}</h3>
            <p class="mt-1 text-sm text-gray-600">Create classes and update their names and sections.</p>
            @if (session('status'))
                <p role="status" class="mt-4 text-green-700">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <ul role="alert" class="mt-4 text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
            <form method="POST" action="{{ $editingClass ? route('admin.classes.update', $editingClass) : route('admin.classes.store') }}" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                @csrf
                @if ($editingClass)
                    @method('PUT')
                @endif
                <div>
                    <x-input-label for="name" value="Class Name" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $editingClass?->name)" maxlength="100" required />
                </div>
                <div>
                    <x-input-label for="section" value="Section (Optional)" />
                    <x-text-input id="section" name="section" class="mt-1 block w-full" :value="old('section', $editingClass?->section)" maxlength="20" />
                </div>
                <div class="flex items-end gap-3">
                    <x-primary-button>{{ $editingClass ? 'Save Changes' : 'Add Class' }}</x-primary-button>
                    @if ($editingClass)
                        <a href="{{ route('admin.classes.index') }}" class="text-sm text-gray-600 underline">Cancel</a>
                    @endif
                </div>
            </form>
        </div>
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900">Class List</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Class Name</th>
                            <th class="px-4 py-3 text-left">Section</th>
                            <th class="px-4 py-3 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($classes as $classRoom)
                            <tr>
                                <td class="px-4 py-3">{{ $classRoom->name }}</td>
                                <td class="px-4 py-3">{{ $classRoom->section ?? '—' }}</td>
                                <td class="px-4 py-3"><a class="text-indigo-600 underline" href="{{ route('admin.classes.index', ['edit' => $classRoom->id, 'page' => $classes->currentPage()]) }}">Edit<span class="sr-only"> {{ $classRoom->name }} {{ $classRoom->section }}</span></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-3 text-gray-500">No classes yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $classes->links() }}</div>
        </div>
    </div>
</x-app-layout>
