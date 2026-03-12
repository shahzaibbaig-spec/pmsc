<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            User Management
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900" id="formTitle">Create User</h3>
                    <p class="text-sm text-gray-600 mt-1">Only Admin can create, edit, delete users and assign roles.</p>

                    <div id="formErrors" class="mt-4 hidden rounded-md bg-red-50 p-3 text-sm text-red-700"></div>

                    <form id="userForm" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <input type="hidden" id="userId" name="user_id">

                        <div>
                            <x-input-label for="name" value="Name" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required />
                        </div>

                        <div>
                            <x-input-label for="email" value="Email" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" required />
                        </div>

                        <div>
                            <x-input-label for="password" value="Password" />
                            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" />
                            <p class="mt-1 text-xs text-gray-500">Required for create. Leave empty on edit to keep old password.</p>
                        </div>

                        <div>
                            <x-input-label for="role" value="Role" />
                            <select id="role" name="role" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                @foreach($roles as $role)
                                    <option value="{{ $role }}">{{ $role }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="status" value="Status" />
                            <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="active">active</option>
                                <option value="inactive">inactive</option>
                            </select>
                        </div>

                        <div class="flex items-end gap-3">
                            <x-primary-button id="saveButton">Save User</x-primary-button>
                            <button type="button" id="cancelEdit" class="hidden inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                Cancel Edit
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row gap-3 md:items-center md:justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Users</h3>
                        <div class="flex items-center gap-2">
                            <input
                                id="searchInput"
                                type="text"
                                placeholder="Search by name, email, role"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                            >
                            <select id="perPage" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Name</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Email</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Role</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody" class="divide-y divide-gray-200 bg-white">
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">Loading users...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex items-center justify-between">
                        <p id="paginationInfo" class="text-sm text-gray-600">-</p>
                        <div class="flex items-center gap-2">
                            <button id="prevPage" type="button" class="rounded-md border px-3 py-1 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50">Previous</button>
                            <button id="nextPage" type="button" class="rounded-md border px-3 py-1 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const roles = @json($roles);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        let state = {
            page: 1,
            perPage: 10,
            search: ''
        };

        let usersCache = [];

        const userForm = document.getElementById('userForm');
        const formErrors = document.getElementById('formErrors');
        const formTitle = document.getElementById('formTitle');
        const userIdInput = document.getElementById('userId');
        const saveButton = document.getElementById('saveButton');
        const cancelEditButton = document.getElementById('cancelEdit');
        const usersTableBody = document.getElementById('usersTableBody');
        const paginationInfo = document.getElementById('paginationInfo');
        const prevPageButton = document.getElementById('prevPage');
        const nextPageButton = document.getElementById('nextPage');
        const searchInput = document.getElementById('searchInput');
        const perPageInput = document.getElementById('perPage');

        function resetForm() {
            userForm.reset();
            userIdInput.value = '';
            formTitle.textContent = 'Create User';
            saveButton.textContent = 'Save User';
            cancelEditButton.classList.add('hidden');
            hideErrors();
        }

        function showErrors(messages) {
            formErrors.classList.remove('hidden');
            formErrors.innerHTML = '<ul class="list-disc ps-5">' + messages.map(m => `<li>${m}</li>`).join('') + '</ul>';
        }

        function hideErrors() {
            formErrors.classList.add('hidden');
            formErrors.innerHTML = '';
        }

        async function fetchUsers() {
            const params = new URLSearchParams({
                page: state.page,
                per_page: state.perPage,
                search: state.search
            });

            const response = await fetch(`{{ route('admin.users.data') }}?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                throw new Error('Failed to load users');
            }

            return response.json();
        }

        function renderUsersTable(payload) {
            usersCache = payload.data;

            if (payload.data.length === 0) {
                usersTableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">No users found.</td>
                    </tr>
                `;
            } else {
                usersTableBody.innerHTML = payload.data.map(user => {
                    const roleOptions = roles.map(role => `<option value="${role}" ${role === user.role ? 'selected' : ''}>${role}</option>`).join('');

                    return `
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-800">${user.name}</td>
                            <td class="px-4 py-3 text-sm text-gray-800">${user.email}</td>
                            <td class="px-4 py-3 text-sm text-gray-800">${user.role ?? '-'}</td>
                            <td class="px-4 py-3 text-sm text-gray-800">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium ${user.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-800'}">
                                    ${user.status}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" class="edit-btn rounded-md bg-amber-500 px-3 py-1 text-white hover:bg-amber-600" data-id="${user.id}">Edit</button>
                                    <button type="button" class="delete-btn rounded-md bg-red-600 px-3 py-1 text-white hover:bg-red-700" data-id="${user.id}">Delete</button>
                                    <select data-id="${user.id}" class="assign-role-select rounded-md border-gray-300 text-sm">
                                        ${roleOptions}
                                    </select>
                                    <button type="button" class="assign-role-btn rounded-md bg-indigo-600 px-3 py-1 text-white hover:bg-indigo-700" data-id="${user.id}">Assign Role</button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            paginationInfo.textContent = `Page ${payload.meta.current_page} of ${payload.meta.last_page} | Total: ${payload.meta.total}`;
            prevPageButton.disabled = payload.meta.current_page <= 1;
            nextPageButton.disabled = payload.meta.current_page >= payload.meta.last_page;
        }

        async function loadUsers() {
            usersTableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">Loading users...</td>
                </tr>
            `;

            try {
                const payload = await fetchUsers();
                renderUsersTable(payload);
            } catch (error) {
                usersTableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-red-600">Failed to load users.</td>
                    </tr>
                `;
            }
        }

        async function submitUserForm(event) {
            event.preventDefault();
            hideErrors();

            const userId = userIdInput.value;
            const isEdit = userId !== '';
            const endpoint = isEdit ? `/admin/users/${userId}` : `{{ route('admin.users.store') }}`;
            const method = isEdit ? 'PUT' : 'POST';

            const payload = {
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
                role: document.getElementById('role').value,
                status: document.getElementById('status').value
            };

            if (isEdit && payload.password === '') {
                delete payload.password;
            }

            try {
                const response = await fetch(endpoint, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (!response.ok) {
                    if (result.errors) {
                        const messages = Object.values(result.errors).flat();
                        showErrors(messages);
                    } else {
                        showErrors([result.message || 'Request failed']);
                    }
                    return;
                }

                if (isEdit && payload.role) {
                    await assignRole(userId, payload.role, false);
                }

                resetForm();
                await loadUsers();
            } catch (error) {
                showErrors(['Unexpected error occurred.']);
            }
        }

        function startEdit(userId) {
            const user = usersCache.find(u => String(u.id) === String(userId));
            if (!user) return;

            userIdInput.value = user.id;
            document.getElementById('name').value = user.name;
            document.getElementById('email').value = user.email;
            document.getElementById('password').value = '';
            document.getElementById('role').value = user.role ?? roles[0];
            document.getElementById('status').value = user.status ?? 'active';

            formTitle.textContent = 'Edit User';
            saveButton.textContent = 'Update User';
            cancelEditButton.classList.remove('hidden');
            hideErrors();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        async function deleteUser(userId) {
            if (!confirm('Are you sure you want to delete this user?')) {
                return;
            }

            const response = await fetch(`/admin/users/${userId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            const result = await response.json();
            if (!response.ok) {
                alert(result.message || 'Delete failed.');
                return;
            }

            await loadUsers();
        }

        async function assignRole(userId, role, alertOnSuccess = true) {
            const response = await fetch(`/admin/users/${userId}/assign-role`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ role })
            });

            const result = await response.json();
            if (!response.ok) {
                alert(result.message || 'Role assignment failed.');
                return false;
            }

            if (alertOnSuccess) {
                alert('Role assigned successfully.');
            }

            await loadUsers();
            return true;
        }

        userForm.addEventListener('submit', submitUserForm);
        cancelEditButton.addEventListener('click', resetForm);

        prevPageButton.addEventListener('click', async () => {
            if (state.page > 1) {
                state.page -= 1;
                await loadUsers();
            }
        });

        nextPageButton.addEventListener('click', async () => {
            state.page += 1;
            await loadUsers();
        });

        const onUsersSearch = window.NSMS.debounce(async () => {
            state.search = searchInput.value.trim();
            state.page = 1;
            await loadUsers();
        }, 300);
        searchInput.addEventListener('input', onUsersSearch);

        perPageInput.addEventListener('change', async () => {
            state.perPage = Number(perPageInput.value);
            state.page = 1;
            await loadUsers();
        });

        usersTableBody.addEventListener('click', async (event) => {
            const target = event.target;

            if (target.classList.contains('edit-btn')) {
                startEdit(target.dataset.id);
                return;
            }

            if (target.classList.contains('delete-btn')) {
                await deleteUser(target.dataset.id);
                return;
            }

            if (target.classList.contains('assign-role-btn')) {
                const userId = target.dataset.id;
                const select = usersTableBody.querySelector(`.assign-role-select[data-id="${userId}"]`);
                await assignRole(userId, select.value, true);
            }
        });

        window.NSMS.lazyInit(usersTableBody, loadUsers);
    </script>
</x-app-layout>
