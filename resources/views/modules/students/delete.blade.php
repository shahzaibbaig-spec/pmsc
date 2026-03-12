<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Delete Student
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Confirm Deletion</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        You are about to delete <span class="font-semibold">{{ $student->name }}</span>
                        ({{ $student->student_id }}). This action cannot be undone.
                    </p>

                    <div class="mt-6 flex items-center gap-3">
                        <button id="confirmDelete"
                                class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                            Delete Student
                        </button>
                        <a href="{{ route('admin.students.index') }}"
                           class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const deleteButton = document.getElementById('confirmDelete');

        deleteButton.addEventListener('click', async () => {
            deleteButton.disabled = true;
            try {
                const response = await fetch('{{ route('admin.students.destroy', $student) }}', {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const payload = await response.json();
                if (!response.ok) {
                    throw new Error(payload.message || 'Delete failed');
                }

                window.location.href = '{{ route('admin.students.index') }}';
            } catch (error) {
                alert('Failed to delete student.');
                deleteButton.disabled = false;
            }
        });
    </script>
</x-app-layout>

