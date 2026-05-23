const CACHE_NAME = 'gold-workshop-static-v1';
const FALLBACK_PAGE = '/offline.html';
const CACHE_ASSETS = [
  '/',
  '/offline.html',
  '/css/app.css',
  '/css/offline.css',
  '/js/app.js',
  '/js/calculations.js',
  '/js/idb-manager.js',
  '/js/offline-detector.js',
  '/js/sync-manager.js',
  '/js/api-client.js',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(CACHE_ASSETS)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  if (event.request.method === 'GET' && url.origin === self.location.origin) {
    if (url.pathname.startsWith('/api/v1/')) {
      event.respondWith(networkFirst(event.request));
      return;
    }

    if (event.request.mode === 'navigate') {
      event.respondWith(
        fetch(event.request)
          .then((response) => {
            if (response.ok) {
              return response;
            }
            throw new Error('Network response not ok');
          })
          .catch(() => caches.match(FALLBACK_PAGE))
      );
      return;
    }
  }

  event.respondWith(cacheFirst(event.request));
});

async function cacheFirst(request) {
  const cached = await caches.match(request);
  return cached || fetch(request).catch(() => caches.match(FALLBACK_PAGE));
}

async function networkFirst(request) {
  try {
    const response = await fetch(request);
    const cache = await caches.open(CACHE_NAME);
    if (response && response.ok) {
      cache.put(request, response.clone());
    }
    return response;
  } catch (error) {
    const cached = await caches.match(request);
    return cached || caches.match(FALLBACK_PAGE);
  }
}

self.addEventListener('sync', (event) => {
  if (event.tag === 'gold-workshop-sync') {
    event.waitUntil(processQueuedOperations());
  }
});

async function processQueuedOperations() {
  return self.clients.matchAll({ includeUncontrolled: true }).then((clients) => {
    clients.forEach((client) => {
      client.postMessage({ type: 'SYNC_COMPLETE', success: true });
    });
  });
}
