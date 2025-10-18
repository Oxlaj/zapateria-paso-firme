const CACHE_NAME = 'oxlaj-cache-v1';
const CORE_ASSETS = [
  '/',
  '/index.html',
  '/assets/css/styles.css',
  '/assets/js/data.js',
  '/assets/js/main.js',
  '/assets/img/logo-oxlaj.svg'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(CORE_ASSETS))
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.map(k => k !== CACHE_NAME ? caches.delete(k) : null)))
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;
  event.respondWith(
    caches.match(req).then((cached) => cached || fetch(req))
  );
});

// Manejo de clic en notificaciones
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clientList => {
      for (const client of clientList) {
        if ('focus' in client) return client.focus();
      }
      if (self.clients.openWindow) return self.clients.openWindow('/');
    })
  );
});

// Permite mostrar notificaciones locales desde la página via postMessage
self.addEventListener('message', (event) => {
  const { type, title, options } = event.data || {};
  if (type === 'showLocalNotification' && title) {
    self.registration.showNotification(title, options || {});
  }
});
