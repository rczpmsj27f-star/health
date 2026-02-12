// Dynamic cache version - uses timestamp to automatically invalidate old caches on deployment
const CACHE_VERSION = 'v1-' + Date.now();
const CACHE_NAME = `health-${CACHE_VERSION}`;

// Only cache images and HTML - CSS/JS will ALWAYS fetch from network
const urlsToCache = [
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

// Activate event - clean up old caches aggressively
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          // Delete ALL caches that don't match current cache name (including old versions)
          if (cacheName !== CACHE_NAME) {
            console.log('Service Worker: Clearing old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  // Take control of all pages immediately
  self.clients.claim();
});

// Fetch event - smart caching strategy by resource type
self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);
  
  // Don't intercept external requests (like OneSignal CDN, external APIs)
  if (url.origin !== location.origin) {
    return; // Let external requests go directly to network
  }
  
  // Only handle GET requests - non-GET requests fall through to network
  if (event.request.method !== 'GET') {
    return;
  }
  
  // Don't cache authenticated pages or API calls - use network
  if (url.pathname.includes('/api/') || 
      url.pathname.includes('login') ||
      url.pathname.includes('logout')) {
    return;
  }
  
  // NEVER cache CSS or JS files - always fetch fresh from network
  // This ensures cache-buster query params (?v=time()) work as intended
  if (url.pathname.endsWith('.css') || url.pathname.endsWith('.js')) {
    event.respondWith(
      fetch(event.request, { redirect: 'follow' })
        .catch(() => {
          // If network fails, return empty response to avoid breaking page
          return new Response('', { 
            status: 503, 
            statusText: 'Service Unavailable' 
          });
        })
    );
    return;
  }
  
  // For images: Cache first, then network (faster loads)
  if (url.pathname.match(/\.(png|jpg|jpeg|gif|svg|webp|ico)$/i)) {
    event.respondWith(
      caches.match(event.request)
        .then((cachedResponse) => {
          if (cachedResponse) {
            return cachedResponse;
          }
          
          return fetch(event.request, { redirect: 'follow' })
            .then((networkResponse) => {
              // Cache the image for future use
              if (networkResponse && networkResponse.status === 200) {
                const responseToCache = networkResponse.clone();
                caches.open(CACHE_NAME).then((cache) => {
                  cache.put(event.request, responseToCache);
                });
              }
              return networkResponse;
            });
        })
    );
    return;
  }
  
  // For HTML pages: Cache with network fallback (for offline support)
  event.respondWith(
    fetch(event.request, { redirect: 'follow' })
      .then((networkResponse) => {
        // Cache successful HTML responses
        if (networkResponse && networkResponse.status === 200) {
          const responseToCache = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, responseToCache);
          });
        }
        return networkResponse;
      })
      .catch(() => {
        // If network fails, try cache (offline support)
        return caches.match(event.request)
          .then((cachedResponse) => {
            if (cachedResponse) {
              return cachedResponse;
            }
            // No cache and no network - return error
            return new Response('Offline - Page not cached', {
              status: 503,
              statusText: 'Service Unavailable'
            });
          });
      })
  );
});
