const CACHE_NAME = 'ajbva-cache-v1';
const urlsToCache = [
  '/',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css',
  'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
  'https://cdn.ckeditor.com/ckeditor5/40.2.0/classic/ckeditor.js',
  '/images/logo.svg',
  '/images/avatar-default.svg'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) return response;
        return fetch(event.request).catch(() => {
          // Si on est hors ligne et qu'on demande une page HTML, on pourrait renvoyer une page "Offline"
          return caches.match('/');
        });
      })
  );
});
