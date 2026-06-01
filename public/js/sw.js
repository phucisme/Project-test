// Service Worker - Cloud Garden Game
const CACHE_NAME = 'cloud-garden-v1.0.0';
const PRELOAD_URLS = [
    '/',
    '/public/index.html',
    '/public/css/style.css',
    '/public/css/animations.css',
    '/public/css/responsive.css',
    '/public/js/api.js',
    '/public/js/game.js',
    '/public/js/ui.js',
    '/manifest.json'
];

// Install event
self.addEventListener('install', (event) => {
    console.log('[SW] Installing Service Worker...');
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('[SW] Caching assets...');
            return cache.addAll(PRELOAD_URLS);
        })
    );
    self.skipWaiting();
});

// Activate event
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating Service Worker...');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('[SW] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Fetch event
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }

    // API requests - Network first with cache fallback
    if (url.pathname.includes('/api/')) {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    // Cache successful responses
                    if (response.ok) {
                        const cache = caches.open(CACHE_NAME);
                        cache.then((c) => c.put(request, response.clone()));
                    }
                    return response;
                })
                .catch(() => {
                    // Fall back to cache if offline
                    return caches.match(request);
                })
        );
    } else {
        // Static assets - Cache first with network fallback
        event.respondWith(
            caches.match(request)
                .then((response) => {
                    if (response) {
                        // Update cache in background
                        fetch(request).then((freshResponse) => {
                            if (freshResponse.ok) {
                                caches.open(CACHE_NAME).then((cache) => {
                                    cache.put(request, freshResponse);
                                });
                            }
                        });
                        return response;
                    }

                    return fetch(request)
                        .then((response) => {
                            // Cache new responses
                            if (response.ok) {
                                const cache = caches.open(CACHE_NAME);
                                cache.then((c) => c.put(request, response.clone()));
                            }
                            return response;
                        });
                })
                .catch(() => {
                    // Return offline page if available
                    return caches.match('/offline.html').catch(() => {
                        return new Response('Offline', { status: 503 });
                    });
                })
        );
    }
});

// Background Sync
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-garden') {
        event.waitUntil(syncGarden());
    }
});

async function syncGarden() {
    console.log('[SW] Syncing garden data...');
    try {
        const response = await fetch('/src/api/game.php?action=get_garden');
        if (response.ok) {
            const data = await response.json();
            // Store in cache or IndexedDB
            const cache = await caches.open(CACHE_NAME);
            cache.put('/api/garden', response.clone());
        }
    } catch (error) {
        console.log('[SW] Sync failed:', error);
    }
}

// Push Notifications
self.addEventListener('push', (event) => {
    if (event.data) {
        const data = event.data.json();
        const options = {
            body: data.body,
            icon: '/public/images/icon-192x192.png',
            badge: '/public/images/badge-72x72.png',
            tag: 'cloud-garden-notification',
            requireInteraction: false
        };

        event.waitUntil(
            self.registration.showNotification(data.title || 'Cloud Garden', options)
        );
    }
});

// Notification Click
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(
        clients.matchAll({ type: 'window' }).then((clientList) => {
            for (let i = 0; i < clientList.length; i++) {
                if (clientList[i].url === '/' && 'focus' in clientList[i]) {
                    return clientList[i].focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow('/');
            }
        })
    );
});

console.log('[SW] Service Worker loaded');
