const CACHE_NAME = 'health-tracker-v1';
const urlsToCache = [
  '/',
  '/assets/css/app.css',
  '/assets/js/menu.js',
  '/assets/images/icon-192x192.png',
  '/assets/images/icon-512x512.png'
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(urlsToCache))
  );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
  // Don't cache authenticated pages or API calls - use network
  if (event.request.url.includes('/api/') || 
      event.request.url.includes('login') ||
      event.request.url.includes('logout')) {
    // Let these requests use default network behavior
    return;
  }
  
  event.respondWith(
    caches.match(event.request)
      .then((response) => response || fetch(event.request))
  );
});
