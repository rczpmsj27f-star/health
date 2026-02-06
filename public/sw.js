const CACHE_NAME = 'health-tracker-v3'; // Version bump to force update
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
  // Take control of all pages immediately - necessary for the redirect fix to work on already-open pages
  self.clients.claim();
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
  
  // Only handle GET requests - non-GET requests fall through to network
  // (redirects on POST/PUT/DELETE can be problematic and should be handled by the server)
  if (event.request.method !== 'GET') {
    return;
  }
  
  event.respondWith(
    caches.match(event.request)
      .then((cachedResponse) => {
        if (cachedResponse) {
          return cachedResponse;
        }
        
        // Fetch with redirect: 'follow' to fix Safari "Response served by service worker has redirections" error
        // This ensures redirects are resolved before returning, preventing Safari from throwing WebKitInternal:0 error
        return fetch(event.request, { redirect: 'follow' });
      })
  );
});
