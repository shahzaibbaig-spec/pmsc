<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Notifications
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-5">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
                        <div class="md:col-span-3">
                            <x-input-label for="search" value="Search" />
                            <x-text-input id="search" type="text" class="mt-1 block w-full" placeholder="Search notifications" />
                        </div>
                        <div>
                            <x-input-label for="per_page" value="Per Page" />
                            <select id="per_page" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="10">10</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button id="markAllBtn" type="button" class="inline-flex min-h-11 w-full items-center justify-center rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">
                                Mark All Read
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <button id="pushToggleBtn" type="button" class="inline-flex min-h-11 items-center rounded-md border border-indigo-300 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50">
                            Enable Push
                        </button>
                        <p id="pushStatusText" class="text-xs text-gray-500">Push status: unknown</p>
                    </div>

                    <div id="messageBox" class="mt-4 hidden rounded-md p-3 text-sm"></div>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-[680px] divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="sticky left-0 z-20 bg-gray-50 px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Title</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Message</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Time</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Action</th>
                            </tr>
                        </thead>
                        <tbody id="rowsBody" class="divide-y divide-gray-200 bg-white">
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">Loading notifications...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between gap-2 px-5 py-4">
                    <p id="paginationInfo" class="text-sm text-gray-600">-</p>
                    <div class="flex gap-2">
                        <button id="prevBtn" type="button" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50">
                            Previous
                        </button>
                        <button id="nextBtn" type="button" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const searchInput = document.getElementById('search');
        const perPageInput = document.getElementById('per_page');
        const markAllBtn = document.getElementById('markAllBtn');
        const rowsBody = document.getElementById('rowsBody');
        const paginationInfo = document.getElementById('paginationInfo');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const messageBox = document.getElementById('messageBox');
        const pushToggleBtn = document.getElementById('pushToggleBtn');
        const pushStatusText = document.getElementById('pushStatusText');
        const vapidPublicKey = @json(config('webpush.vapid.public_key'));

        const state = {
            page: 1,
            per_page: 10,
            search: ''
        };

        function showMessage(message, type = 'success') {
            messageBox.classList.remove('hidden', 'bg-red-50', 'text-red-700', 'bg-green-50', 'text-green-700');
            messageBox.textContent = message;
            if (type === 'error') {
                messageBox.classList.add('bg-red-50', 'text-red-700');
            } else {
                messageBox.classList.add('bg-green-50', 'text-green-700');
            }
        }

        function clearMessage() {
            messageBox.classList.add('hidden');
            messageBox.textContent = '';
        }

        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);

            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }

            return outputArray;
        }

        async function ensureServiceWorker() {
            if (!('serviceWorker' in navigator)) {
                throw new Error('Service worker is not supported in this browser.');
            }

            return navigator.serviceWorker.getRegistration().then((existing) => {
                if (existing) {
                    return existing;
                }

                return navigator.serviceWorker.register('/sw-teacher.js');
            });
        }

        async function syncPushSubscription(subscription) {
            const payload = subscription.toJSON();
            const response = await fetch(`{{ route('notifications.push.subscribe') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                throw new Error('Failed to save push subscription.');
            }
        }

        async function removePushSubscription(endpoint) {
            const response = await fetch(`{{ route('notifications.push.unsubscribe') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ endpoint }),
            });

            if (!response.ok) {
                throw new Error('Failed to remove push subscription.');
            }
        }

        async function refreshPushStatus() {
            if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
                pushStatusText.textContent = 'Push status: not supported in this browser';
                pushToggleBtn.disabled = true;
                return;
            }

            const registration = await ensureServiceWorker();
            const subscription = await registration.pushManager.getSubscription();
            if (subscription) {
                pushStatusText.textContent = 'Push status: enabled';
                pushToggleBtn.textContent = 'Disable Push';
            } else {
                pushStatusText.textContent = 'Push status: disabled';
                pushToggleBtn.textContent = 'Enable Push';
            }
        }

        async function togglePush() {
            clearMessage();

            if (!vapidPublicKey) {
                showMessage('VAPID public key is missing. Push is disabled; in-app notifications remain active.', 'error');
                return;
            }

            if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
                showMessage('Push is not supported in this browser. In-app notifications remain active.', 'error');
                return;
            }

            pushToggleBtn.disabled = true;

            try {
                const registration = await ensureServiceWorker();
                let subscription = await registration.pushManager.getSubscription();

                if (subscription) {
                    const endpoint = subscription.endpoint;
                    await subscription.unsubscribe();
                    await removePushSubscription(endpoint);
                    showMessage('Push notifications disabled.');
                } else {
                    const permission = await Notification.requestPermission();
                    if (permission !== 'granted') {
                        showMessage('Notification permission not granted. In-app notifications remain active.', 'error');
                        return;
                    }

                    subscription = await registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
                    });

                    await syncPushSubscription(subscription);
                    showMessage('Push notifications enabled.');
                }
            } catch (error) {
                showMessage(error.message || 'Failed to update push settings.', 'error');
            } finally {
                pushToggleBtn.disabled = false;
                await refreshPushStatus();
            }
        }

        async function loadRows() {
            const params = new URLSearchParams({
                page: state.page,
                per_page: state.per_page,
                search: state.search,
            });

            rowsBody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">Loading notifications...</td></tr>';

            const response = await fetch(`{{ route('notifications.data') }}?${params.toString()}`, {
                headers: { 'Accept': 'application/json' },
            });

            if (!response.ok) {
                rowsBody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-sm text-red-600">Failed to load notifications.</td></tr>';
                return;
            }

            const result = await response.json();
            const rows = result.data || [];

            if (!rows.length) {
                rowsBody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">No notifications found.</td></tr>';
            } else {
                rowsBody.innerHTML = rows.map(row => `
                    <tr class="${row.is_read ? '' : 'bg-indigo-50/30'}">
                        <td class="sticky left-0 z-10 whitespace-nowrap bg-white px-4 py-3 text-sm font-medium text-gray-900">${window.NSMS.escapeHtml(row.title)}</td>
                        <td class="max-w-[360px] px-4 py-3 text-sm text-gray-700">${window.NSMS.escapeHtml(row.message)}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500">${window.NSMS.escapeHtml(row.created_at_human || '-')}</td>
                        <td class="px-4 py-3 text-sm">
                            ${row.is_read
                                ? '<span class="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700">Read</span>'
                                : '<span class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-700">Unread</span>'}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            ${row.is_read ? '-' : `<button type="button" class="mark-read-btn inline-flex min-h-10 items-center rounded-md border border-indigo-300 px-3 text-xs font-medium text-indigo-700 hover:bg-indigo-50" data-id="${row.id}">Mark Read</button>`}
                            ${row.url ? `<a href="${row.url}" class="ms-2 inline-flex min-h-10 items-center rounded-md border border-gray-300 px-3 text-xs font-medium text-gray-700 hover:bg-gray-50">Open</a>` : ''}
                        </td>
                    </tr>
                `).join('');
            }

            paginationInfo.textContent = `Page ${result.meta.page} of ${result.meta.last_page} | Total: ${result.meta.total}`;
            prevBtn.disabled = result.meta.page <= 1;
            nextBtn.disabled = result.meta.page >= result.meta.last_page;
        }

        async function markAsRead(notificationId) {
            const response = await fetch(`/notifications/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            if (!response.ok) {
                showMessage('Failed to mark notification as read.', 'error');
                return;
            }

            await loadRows();
        }

        async function markAllRead() {
            clearMessage();
            const response = await fetch(`{{ route('notifications.read-all') }}`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            if (!response.ok) {
                showMessage('Failed to mark all notifications.', 'error');
                return;
            }

            showMessage('All notifications marked as read.');
            await loadRows();
        }

        rowsBody.addEventListener('click', async (event) => {
            const target = event.target;
            if (!target.classList.contains('mark-read-btn')) {
                return;
            }

            await markAsRead(target.dataset.id);
        });

        searchInput.addEventListener('input', window.NSMS.debounce(async () => {
            state.search = searchInput.value.trim();
            state.page = 1;
            await loadRows();
        }, 300));

        perPageInput.addEventListener('change', async () => {
            state.per_page = Number(perPageInput.value || 10);
            state.page = 1;
            await loadRows();
        });

        prevBtn.addEventListener('click', async () => {
            if (state.page > 1) {
                state.page -= 1;
                await loadRows();
            }
        });

        nextBtn.addEventListener('click', async () => {
            state.page += 1;
            await loadRows();
        });

        markAllBtn.addEventListener('click', markAllRead);
        pushToggleBtn.addEventListener('click', togglePush);

        loadRows();
        refreshPushStatus().catch(() => null);
    </script>
</x-app-layout>
