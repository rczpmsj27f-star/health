const CACHE_NAME = 'health-tracker-v2'; // Version bump to force update
const urlsToCache = [
  '/',
  '/assets/css/app.css',
  '/assets/js/menu.js',
  '/assets/images/icon-192x192.png',
  '/assets/images/icon-512x512.png'
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
  self.skipWaiting(); // Force immediate activation
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(urlsToCache))
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
  // Don't intercept external requests (like OneSignal CDN)
  const url = new URL(event.request.url);
  if (url.origin !== location.origin) {
    return; // Let external requests go directly to network
  }
  
  // Don't cache authenticated pages or API calls - use network
  if (event.request.url.includes('/api/') || 
      event.request.url.includes('login') ||
      event.request.url.includes('logout')) {
    return;
  }
  
  event.respondWith(
    caches.match(event.request)
      .then((response) => response || fetch(event.request))
  );
});
