// Service Worker para Eventos Musicales PWA / TWA Android
const CACHE_NAME = 'eventos-musicales-v2';
const STATIC_ASSETS = [
    '/favicon.svg',
    '/favicon.ico',
    '/manifest.json',
    '/js/offline-audio-cache.js'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS);
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    // Network first strategy with offline fallback for static assets
    if (event.request.method !== 'GET') return;

    event.respondWith(
        fetch(event.request)
            .catch(() => caches.match(event.request))
    );
});
