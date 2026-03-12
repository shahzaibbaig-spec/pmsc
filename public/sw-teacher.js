const SW_VERSION = 'teacher-pwa-v2';
const SHELL_CACHE = `${SW_VERSION}-shell`;
const RUNTIME_CACHE = `${SW_VERSION}-runtime`;

const SHELL_PAGES = [
    '/teacher/dashboard',
    '/teacher/attendance',
    '/teacher/exams',
];

const DYNAMIC_ENDPOINT_PREFIXES = [
    '/teacher/attendance/options',
    '/teacher/attendance/sheet',
    '/teacher/exams/options',
    '/teacher/exams/sheet',
    '/api/timetable/teacher',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(SHELL_CACHE).then(async (cache) => {
            for (const page of SHELL_PAGES) {
                try {
                    const response = await fetch(page, {
                        credentials: 'include',
                    });
                    if (response && response.ok) {
                        await cache.put(page, response.clone());
                    }
                } catch (error) {
                }
            }
        }).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => Promise.all(
            cacheNames
                .filter((cacheName) => cacheName.startsWith('teacher-pwa-') && !cacheName.startsWith(SW_VERSION))
                .map((cacheName) => caches.delete(cacheName))
        )).then(() => self.clients.claim())
    );
});

const isSameOrigin = (url) => url.origin === self.location.origin;
const isTeacherShellPath = (pathname) => SHELL_PAGES.includes(pathname);
const isDynamicEndpoint = (pathname) => DYNAMIC_ENDPOINT_PREFIXES.some((prefix) => pathname.startsWith(prefix));

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);
    if (!isSameOrigin(url)) {
        return;
    }

    if (isDynamicEndpoint(url.pathname)) {
        event.respondWith(networkFirstDynamic(request));
        return;
    }

    if (request.mode === 'navigate' && isTeacherShellPath(url.pathname)) {
        event.respondWith(networkFirstShell(request));
    }
});

async function networkFirstShell(request) {
    const runtimeCache = await caches.open(RUNTIME_CACHE);

    try {
        const networkResponse = await fetch(request);
        if (networkResponse && networkResponse.ok) {
            runtimeCache.put(request, networkResponse.clone());
            return networkResponse;
        }
    } catch (error) {
    }

    const cachedResponse = await runtimeCache.match(request);
    if (cachedResponse) {
        return cachedResponse;
    }

    return caches.match('/teacher/dashboard');
}

async function networkFirstDynamic(request) {
    const runtimeCache = await caches.open(RUNTIME_CACHE);

    try {
        const networkResponse = await fetch(request);
        if (networkResponse && networkResponse.ok) {
            runtimeCache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        const cachedResponse = await runtimeCache.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }

        return new Response(
            JSON.stringify({
                message: 'Offline. No cached data available for this request.',
            }),
            {
                status: 503,
                headers: {
                    'Content-Type': 'application/json',
                },
            }
        );
    }
}

self.addEventListener('push', (event) => {
    let payload = {};

    try {
        payload = event.data ? event.data.json() : {};
    } catch (error) {
        payload = {
            title: 'Notification',
            message: event.data ? event.data.text() : 'You have a new update.',
        };
    }

    const title = payload.title || 'Notification';
    const options = {
        body: payload.message || payload.body || 'You have a new update.',
        icon: payload.icon || '/favicon.ico',
        badge: payload.badge || '/favicon.ico',
        data: {
            url: payload.url || '/notifications',
        },
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = event.notification?.data?.url || '/notifications';

    event.waitUntil((async () => {
        const windowClients = await clients.matchAll({ type: 'window', includeUncontrolled: true });
        for (const client of windowClients) {
            if (client.url.includes(self.location.origin) && 'focus' in client) {
                client.navigate(targetUrl);
                return client.focus();
            }
        }

        if (clients.openWindow) {
            return clients.openWindow(targetUrl);
        }

        return null;
    })());
});
