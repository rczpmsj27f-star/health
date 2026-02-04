// Service Worker for Medication Reminder PWA
const CACHE_NAME = 'medication-reminder-v2';
const urlsToCache = [
    '/',
    '/index.html',
    '/styles.css',
    '/app.js',
    '/manifest.json',
    '/icons/icon-192x192.svg',
    '/icons/icon-512x512.svg'
];

// Install event - cache resources
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Opened cache');
                return cache.addAll(urlsToCache.filter(url => url !== '/'));
            })
            .catch(err => {
                console.log('Cache installation failed:', err);
            })
    );
    self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', event => {
    // Skip API calls and handle them with network-first strategy
    if (event.request.url.includes('/api/')) {
        event.respondWith(
            fetch(event.request)
                .catch(() => {
                    return new Response(
                        JSON.stringify({ error: 'Network unavailable' }),
                        { headers: { 'Content-Type': 'application/json' } }
                    );
                })
        );
        return;
    }
    
    // For other requests, use cache-first strategy
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                if (response) {
                    return response;
                }
                
                return fetch(event.request)
                    .then(response => {
                        // Don't cache non-successful responses
                        if (!response || response.status !== 200 || response.type !== 'basic') {
                            return response;
                        }
                        
                        // Clone the response
                        const responseToCache = response.clone();
                        
                        caches.open(CACHE_NAME)
                            .then(cache => {
                                cache.put(event.request, responseToCache);
                            });
                        
                        return response;
                    });
            })
            .catch(() => {
                // Return a fallback page if available
                return caches.match('/index.html');
            })
    );
});

// Notification click event
self.addEventListener('notificationclick', event => {
    event.notification.close();
    
    if (event.action === 'mark-taken') {
        // Handle marking medication as taken
        const { medicationId, scheduleTime } = event.notification.data;
        
        if (medicationId && scheduleTime) {
            event.waitUntil(
                fetch(`http://localhost:3000/api/medications/${medicationId}/taken`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ scheduleTime })
                })
                .then(() => {
                    console.log('Medication marked as taken');
                })
                .catch(err => {
                    console.error('Error marking medication as taken:', err);
                })
            );
        }
    } else if (event.action === 'snooze') {
        // Handle snooze - could implement additional logic here
        console.log('Notification snoozed');
    }
    
    // Open the app
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then(clientList => {
                // If app is already open, focus it
                for (let client of clientList) {
                    if (client.url.includes(self.location.origin) && 'focus' in client) {
                        return client.focus();
                    }
                }
                
                // Otherwise open a new window
                if (clients.openWindow) {
                    return clients.openWindow('/');
                }
            })
    );
});

// Background sync event (for future use)
self.addEventListener('sync', event => {
    if (event.tag === 'sync-medications') {
        event.waitUntil(
            // Sync medication data when back online
            fetch('/api/medications')
                .then(response => response.json())
                .then(data => {
                    console.log('Medications synced:', data);
                })
                .catch(err => {
                    console.error('Sync failed:', err);
                })
        );
    }
});
