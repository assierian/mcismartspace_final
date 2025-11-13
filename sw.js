const CACHE_NAME = 'mcismartspace-v1.0.1';
const OFFLINE_URL = '/offline.html';
const urlsToCache = [
  '/',
  '/index.php',
  '/public/css/login.css',
  '/public/js/alert.js',
  '/public/assets/final_logo.svg',
  '/public/assets/logo.webp',
  '/partials/terms.css',
  '/manifest.json',
  OFFLINE_URL
];

// Track online status
let isOnline = true;
self.addEventListener('online', () => {
  isOnline = true;
  console.log('Service Worker: App is online');
});

self.addEventListener('offline', () => {
  isOnline = false;
  console.log('Service Worker: App is offline');
});

// Install event - cache resources
self.addEventListener('install', (event) => {
  console.log('Service Worker: Installing...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Service Worker: Caching files');
        return cache.addAll(urlsToCache);
      })
      .then(() => {
        console.log('Service Worker: Installation complete');
        return self.skipWaiting();
      })
      .catch((error) => {
        console.error('Service Worker: Installation failed', error);
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  console.log('Service Worker: Activating...');
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log('Service Worker: Deleting old cache', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      console.log('Service Worker: Activation complete');
      return self.clients.claim();
    })
  );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
  // Skip non-GET requests
  if (event.request.method !== 'GET') {
    return;
  }

  // Skip chrome-extension and other non-http requests
  if (!event.request.url.startsWith('http')) {
    return;
  }

  event.respondWith(
    caches.match(event.request)
      .then((response) => {
        // Return cached version or fetch from network
        if (response) {
          console.log('Service Worker: Serving from cache', event.request.url);
          return response;
        }

        console.log('Service Worker: Fetching from network', event.request.url);
        return fetch(event.request).then((response) => {
          // Check if we received a valid response
          if (!response || response.status !== 200 || response.type !== 'basic') {
            return response;
          }

          // Clone the response for caching
          const responseToCache = response.clone();

          caches.open(CACHE_NAME)
            .then((cache) => {
              cache.put(event.request, responseToCache);
            });

          return response;
        });
      })
      .catch((error) => {
        console.error('Service Worker: Fetch failed', error);
        
        // Check if the request is for a page (HTML)
        const isPageRequest = event.request.mode === 'navigate' || 
                             (event.request.method === 'GET' && 
                              event.request.headers.get('accept') && 
                              event.request.headers.get('accept').includes('text/html'));
        
        if (isPageRequest) {
          console.log('Service Worker: Serving offline page');
          return caches.match(OFFLINE_URL).then(offlineResponse => {
            if (offlineResponse) {
              return offlineResponse;
            }
            // Fallback if offline page is not cached
            return new Response(`
              <!DOCTYPE html>
              <html>
              <head>
                <title>Offline - MCiSmartSpace</title>
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <style>
                  body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                  .container { max-width: 400px; margin: 0 auto; }
                  button { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
                </style>
              </head>
              <body>
                <div class="container">
                  <h1>You're Offline</h1>
                  <p>Please check your connection and try again.</p>
                  <button onclick="window.location.reload()">Retry</button>
                </div>
                <script>
                  setInterval(() => {
                    if (navigator.onLine) {
                      window.location.reload();
                    }
                  }, 3000);
                </script>
              </body>
              </html>
            `, {
              status: 200,
              statusText: 'OK',
              headers: new Headers({
                'Content-Type': 'text/html'
              })
            });
          });
        }
        
        // For non-HTML requests, return a simple error message
        return new Response('Offline - Resource unavailable', {
          status: 503,
          statusText: 'Service Unavailable',
          headers: new Headers({
            'Content-Type': 'text/plain'
          })
        });
      })
  );
});

// Background sync for offline functionality
self.addEventListener('sync', (event) => {
  console.log('Service Worker: Background sync', event.tag);
  
  if (event.tag === 'background-sync') {
    event.waitUntil(
      // Handle background sync tasks here
      console.log('Service Worker: Performing background sync')
    );
  }
});

// Push notification handling
self.addEventListener('push', (event) => {
  console.log('Service Worker: Push received');
  
  const options = {
    body: event.data ? event.data.text() : 'New notification from MCiSmartSpace',
    icon: '/public/assets/final_logo.svg',
    badge: '/public/assets/logo.webp',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    },
    actions: [
      {
        action: 'explore',
        title: 'Open App',
        icon: '/public/assets/final_logo.svg'
      },
      {
        action: 'close',
        title: 'Close',
        icon: '/public/assets/final_logo.svg'
      }
    ]
  };

  event.waitUntil(
    self.registration.showNotification('MCiSmartSpace', options)
  );
});

// Notification click handling
self.addEventListener('notificationclick', (event) => {
  console.log('Service Worker: Notification clicked');
  
  event.notification.close();

  if (event.action === 'explore') {
    event.waitUntil(
      clients.openWindow('/')
    );
  }
});
