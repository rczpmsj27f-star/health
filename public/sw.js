/**
 * Service Worker with Cache-Control Header Respect
 * - Respects server Cache-Control headers
 * - Never caches if server says no-cache/no-store
 * - Keeps OneSignal push notifications working
 * - Provides offline support for cached content
 */

const CACHE_VERSION = 'v1-' + Date.now();
const CACHE_NAME = `health-${CACHE_VERSION}`;

// Activate: Clean up old cache versions
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames
                    .filter(name => name.startsWith('health-') && name !== CACHE_NAME)
                    .map(name => {
                        console.log('🗑️ Deleting old cache:', name);
                        return caches.delete(name);
                    })
            );
        })
    );
    // Take control of all pages immediately
    self.clients.claim();
});

// Fetch: Smart caching with header respect
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // ✅ PHP FILES: Network first, respect Cache-Control headers
    if (url.pathname.endsWith('.php')) {
        event.respondWith(
            fetch(request)
                .then(response => {
                    // CRITICAL: Check server cache headers
                    const cacheControl = response.headers.get('cache-control') || '';
                    const pragma = response.headers.get('pragma') || '';
                    
                    // If server explicitly says don't cache, OBEY IT
                    if (cacheControl.includes('no-cache') || 
                        cacheControl.includes('no-store') ||
                        pragma.includes('no-cache')) {
                        console.log('📄 Not caching PHP (server no-cache):', url.pathname);
                        return response; // Return fresh, don't cache
                    }
                    
                    // Otherwise cache as backup for offline only
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(request, responseClone);
                    });
                    return response;
                })
                .catch(() => {
                    // Offline: Use cached version as fallback
                    console.log('📡 Network failed, using cache for:', url.pathname);
                    return caches.match(request);
                })
        );
        return;
    }

    // ✅ CSS/JS: NETWORK ONLY, never cache (always fresh)
    if (url.pathname.match(/\.(css|js)$/i)) {
        event.respondWith(
            fetch(request).catch(() => {
                // If network fails and we have cached version, use it
                return caches.match(request) || new Response('Offline - resource unavailable', { status: 503 });
            })
        );
        return;
    }

    // ✅ IMAGES: Cache first (safe to cache, rarely change)
    if (url.pathname.match(/\.(jpg|jpeg|png|gif|webp|svg|ico)$/i)) {
        event.respondWith(
            caches.open(CACHE_NAME).then(cache => {
                return cache.match(request).then(response => {
                    if (response) {
                        return response; // Return cached
                    }
                    
                    // Not in cache, fetch from network
                    return fetch(request).then(networkResponse => {
                        // Cache successful response for next time
                        cache.put(request, networkResponse.clone());
                        return networkResponse;
                    });
                });
            }).catch(() => {
                // Offline and no cache
                return new Response('Image unavailable offline', { status: 503 });
            })
        );
        return;
    }

    // ✅ DEFAULT: Network first, cache fallback
    event.respondWith(
        fetch(request)
            .then(response => {
                // Optionally cache successful responses
                if (response.ok) {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(request, responseClone);
                    });
                }
                return response;
            })
            .catch(() => {
                return caches.match(request) || new Response('Offline - resource unavailable', { status: 503 });
            })
    );
});

// ✅ IMPORT ONESIGNAL - Keep push notifications working
importScripts('https://cdn.onesignal.com/sdks/OneSignalSDK.js');

console.log('✅ Service Worker loaded with cache-control header respect');
