<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            RBAC Matrix
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="text-sm text-gray-600 mb-4">
                        Rows are roles and columns are permissions. Toggle checkboxes, then click Save Changes.
                    </p>

                    <div class="mb-4 flex items-center justify-between rounded-md border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm">
                        <p id="rbacStatus" class="text-indigo-900">No unsaved changes.</p>
                        <div class="flex items-center gap-2">
                            <button
                                id="rbacResetBtn"
                                type="button"
                                class="rounded-md border border-indigo-300 px-3 py-1.5 font-medium text-indigo-800 hover:bg-indigo-100"
                            >
                                Reset
                            </button>
                            <button
                                id="rbacSaveBtn"
                                type="button"
                                class="rounded-md bg-indigo-600 px-3 py-1.5 font-medium text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                                disabled
                            >
                                Save Changes
                            </button>
                        </div>
                    </div>

                    <div id="matrixLoading" class="text-sm text-gray-500">Loading RBAC matrix...</div>
                    <div id="matrixContainer" class="overflow-x-auto hidden"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const matrixContainer = document.getElementById('matrixContainer');
        const matrixLoading = document.getElementById('matrixLoading');
        const rbacStatus = document.getElementById('rbacStatus');
        const rbacSaveBtn = document.getElementById('rbacSaveBtn');
        const rbacResetBtn = document.getElementById('rbacResetBtn');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const pendingChanges = new Map();
        let rolesCache = [];
        let permissionsCache = [];

        function changeKey(role, permission) {
            return `${role}__${permission}`;
        }

        function updateStatus() {
            const count = pendingChanges.size;
            if (count === 0) {
                rbacStatus.textContent = 'No unsaved changes.';
                rbacSaveBtn.disabled = true;
                return;
            }

            rbacStatus.textContent = `${count} unsaved change(s).`;
            rbacSaveBtn.disabled = false;
        }

        function renderMatrix(roles, permissions) {
            const headCells = permissions.map(p => `<th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider">${p.name}</th>`).join('');

            const bodyRows = roles.map(role => {
                const permSet = new Set(role.permissions);
                const checks = permissions.map(permission => {
                    const checked = permSet.has(permission.name) ? 'checked' : '';
                    return `
                        <td class="px-3 py-2 border-t text-center">
                            <input
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 permission-toggle"
                                data-role="${role.name}"
                                data-permission="${permission.name}"
                                ${checked}
                            >
                        </td>
                    `;
                }).join('');

                return `
                    <tr>
                        <td class="px-3 py-2 border-t font-medium whitespace-nowrap">${role.name}</td>
                        ${checks}
                    </tr>
                `;
            }).join('');

            matrixContainer.innerHTML = `
                <table class="min-w-full text-sm border border-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider">Role \\ Permission</th>
                            ${headCells}
                        </tr>
                    </thead>
                    <tbody>
                        ${bodyRows}
                    </tbody>
                </table>
            `;

            document.querySelectorAll('.permission-toggle').forEach((checkbox) => {
                checkbox.addEventListener('change', (event) => {
                    const input = event.target;
                    const enabled = input.checked;
                    pendingChanges.set(changeKey(input.dataset.role, input.dataset.permission), {
                        role: input.dataset.role,
                        permission: input.dataset.permission,
                        enabled: enabled,
                    });
                    updateStatus();
                });
            });
        }

        async function loadMatrix() {
            try {
                const response = await fetch('{{ route('admin.rbac-matrix.data') }}', {
                    headers: { 'Accept': 'application/json' }
                });
                const payload = await response.json();
                rolesCache = payload.roles || [];
                permissionsCache = payload.permissions || [];
                pendingChanges.clear();
                renderMatrix(rolesCache, permissionsCache);
                updateStatus();
                matrixLoading.classList.add('hidden');
                matrixContainer.classList.remove('hidden');
            } catch (error) {
                matrixLoading.textContent = 'Unable to load RBAC matrix.';
            }
        }

        async function saveChanges() {
            if (pendingChanges.size === 0) {
                return;
            }

            rbacSaveBtn.disabled = true;
            rbacResetBtn.disabled = true;

            try {
                const response = await fetch('{{ route('admin.rbac-matrix.save') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        changes: Array.from(pendingChanges.values())
                    })
                });

                const payload = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(payload.message || 'Failed to save matrix changes.');
                }

                pendingChanges.clear();
                updateStatus();
                await loadMatrix();
                alert(payload.message || 'RBAC matrix changes saved.');
            } catch (error) {
                updateStatus();
                alert(error.message || 'Could not save RBAC changes.');
            } finally {
                rbacResetBtn.disabled = false;
            }
        }

        rbacSaveBtn.addEventListener('click', saveChanges);
        rbacResetBtn.addEventListener('click', loadMatrix);

        loadMatrix();
    </script>
</x-app-layout>
