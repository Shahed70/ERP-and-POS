// AquaFeed ERP Offline Service Worker
const CACHE_NAME = 'aquafeed-cache-v1';
const OFFLINE_URLS = [
    '/admin/offline-pos',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(OFFLINE_URLS).catch((err) => {
                console.log('SW pre-cache error, will cache on demand:', err);
            });
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Only cache GET requests
    if (request.method !== 'GET') {
        return;
    }

    // Do not cache API sync endpoints that require real network
    if (request.url.includes('/admin/offline-sync/sync') || request.url.includes('/admin/offline-sync/ping')) {
        return;
    }

    event.respondWith(
        fetch(request)
            .then((response) => {
                // If valid response, clone and cache for offline usage
                if (response && response.status === 200 && response.type === 'basic') {
                    const responseToCache = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, responseToCache);
                    });
                }
                return response;
            })
            .catch(() => {
                // If offline / network error, return matching cached copy
                return caches.match(request).then((cachedResponse) => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    if (request.mode === 'navigate') {
                        return caches.match('/admin/offline-pos');
                    }
                });
            })
    );
});
