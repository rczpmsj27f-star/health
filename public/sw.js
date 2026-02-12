// Dynamic cache version - uses timestamp to automatically invalidate old caches on deployment
// Note: Date.now() is evaluated when Service Worker installs/updates, not on every page load
// The browser caches the Service Worker file, so this only changes when sw.js is updated
// The 'v2-' prefix reflects the new caching strategy: PHP files never cached, only images
const CACHE_VERSION = 'v2-' + Date.now();
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

// Regex for matching image file extensions
const IMAGE_EXTENSIONS = /\.(png|jpg|jpeg|gif|svg|webp|ico)$/i;

// Only cache images - CSS/JS/PHP will ALWAYS fetch from network
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
  const isCss = url.pathname.endsWith('.css');
  const isJs = url.pathname.endsWith('.js');
  
  if (isCss || isJs) {
    event.respondWith(
      fetch(event.request, { redirect: 'follow' })
        .catch((error) => {
          // Log the error for debugging
          console.error('Service Worker: Failed to fetch CSS/JS:', url.pathname, error);
          console.warn('Service Worker: Returning empty response - styles/functionality may be affected');
          // Return empty response to avoid parsing errors in browser
          // The browser will gracefully handle missing CSS (no styles applied) or JS (no execution)
          // This prevents cascade failures from invalid CSS/JS syntax
          const fileType = isCss ? 'text/css' : 'application/javascript';
          return new Response('', { 
            status: 503, 
            statusText: 'Service Unavailable',
            headers: { 'Content-Type': fileType }
          });
        })
    );
    return;
  }
  
  // For images: Cache first, then network (faster loads)
  if (url.pathname.match(IMAGE_EXTENSIONS)) {
    event.respondWith(
      caches.match(event.request)
        .then((cachedResponse) => {
          if (cachedResponse) {
            return cachedResponse;
          }
          
          return fetch(event.request, { redirect: 'follow' })
            .then((networkResponse) => {
              // Cache successful responses (2xx status codes)
              if (networkResponse && networkResponse.ok) {
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
  
  // For PHP files: Network-first, NEVER cache successful responses (only use cache as offline fallback)
  // This ensures dynamic PHP content is always fresh from the server
  const isPhp = url.pathname.endsWith('.php');
  
  if (isPhp) {
    event.respondWith(
      fetch(event.request, { redirect: 'follow' })
        .then((networkResponse) => {
          // DO NOT cache PHP responses - they are dynamic and should always be fresh
          return networkResponse;
        })
        .catch((error) => {
          // If network fails, try cache (offline support only)
          console.error('Service Worker: Network failed for PHP file:', url.pathname, error);
          return caches.match(event.request)
            .then((cachedResponse) => {
              if (cachedResponse) {
                console.log('Service Worker: Serving PHP from cache (offline):', url.pathname);
                return cachedResponse;
              }
              // No cache and no network - return user-friendly offline page
              console.error('Service Worker: No cache available for:', url.pathname);
              return new Response(OFFLINE_HTML, {
                status: 503,
                statusText: 'Service Unavailable',
                headers: { 'Content-Type': 'text/html' }
              });
            });
        })
    );
    return;
  }
  
  // For static HTML pages: Network-first with cache (for offline support)
  event.respondWith(
    fetch(event.request, { redirect: 'follow' })
      .then((networkResponse) => {
        // Cache successful HTML responses (2xx status codes)
        if (networkResponse && networkResponse.ok) {
          const responseToCache = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, responseToCache);
          });
        }
        return networkResponse;
      })
      .catch((error) => {
        // If network fails, try cache (offline support)
        console.error('Service Worker: Network failed for:', url.pathname, error);
        return caches.match(event.request)
          .then((cachedResponse) => {
            if (cachedResponse) {
              console.log('Service Worker: Serving from cache:', url.pathname);
              return cachedResponse;
            }
            // No cache and no network - return user-friendly offline page
            console.error('Service Worker: No cache available for:', url.pathname);
            return new Response(OFFLINE_HTML, {
              status: 503,
              statusText: 'Service Unavailable',
              headers: { 'Content-Type': 'text/html' }
            });
          });
      })
  );
});
