// Dynamic cache version - uses timestamp to automatically invalidate old caches on deployment
// Note: Date.now() is evaluated when Service Worker installs/updates, not on every page load
// The browser caches the Service Worker file, so this only changes when sw.js is updated
// The 'v1-' prefix allows manual cache invalidation by incrementing to 'v2-', 'v3-', etc. if needed
const CACHE_VERSION = 'v1-' + Date.now();
const CACHE_NAME = `health-${CACHE_VERSION}`;

// User-friendly offline page HTML
const OFFLINE_HTML = `<!DOCTYPE html>
<html>
<head>
  <title>Offline</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body { 
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      margin: 0;
      background: #f5f5f5;
      padding: 20px;
      text-align: center;
    }
    .container {
      background: white;
      padding: 40px;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      max-width: 400px;
    }
    h1 { color: #333; margin-top: 0; }
    p { color: #666; line-height: 1.6; }
  </style>
</head>
<body>
  <div class="container">
    <h1>You are offline</h1>
    <p>This page is not available offline. Please check your internet connection and try again.</p>
  </div>
</body>
</html>`;

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
        .catch((error) => {
          // Log the error for debugging
          console.error('Service Worker: Failed to fetch CSS/JS:', url.pathname, error);
          // Return a response with comment explaining the failure for easier debugging
          const fileType = url.pathname.endsWith('.css') ? 'text/css' : 'application/javascript';
          const commentStyle = url.pathname.endsWith('.css') ? '/*' : '//';
          const closeComment = url.pathname.endsWith('.css') ? '*/' : '';
          return new Response(
            `${commentStyle} Failed to load: ${url.pathname} - Network unavailable ${closeComment}`, 
            { 
              status: 503, 
              statusText: 'Service Unavailable',
              headers: { 'Content-Type': fileType }
            }
          );
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
      .catch((error) => {
        // Log the error for debugging
        console.log('Service Worker: Network failed, trying cache:', url.pathname);
        // If network fails, try cache (offline support)
        return caches.match(event.request)
          .then((cachedResponse) => {
            if (cachedResponse) {
              console.log('Service Worker: Serving from cache:', url.pathname);
              return cachedResponse;
            }
            // No cache and no network - return user-friendly offline page
            console.error('Service Worker: No cache available for:', url.pathname, error);
            return new Response(OFFLINE_HTML, {
              status: 503,
              statusText: 'Service Unavailable',
              headers: { 'Content-Type': 'text/html' }
            });
          });
      })
  );
});
