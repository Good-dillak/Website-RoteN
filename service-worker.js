const CACHE_NAME = 'lolehrote-v3'; // UBAH ke versi BARU
const urlsToCache = [
  '/static/index.html',
  '/static/article.html',
  '/static/category.html',
  '/static/videos.html',
  '/static/search.html',
  '/static/sitemap.html',
  '/static/404.html',
  '/static/offline.html',
  '/static/manifest.json',
  '/assets/css/style.css',
  '/assets/js/script.js',
  // Hapus URL eksternal dari urlsToCache untuk mencegah kegagalan install jika CDN down
];

// Install & Cache
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
  );
});

// Activate & Cleanup Old Caches
self.addEventListener('activate', event => {
    const cacheWhitelist = [CACHE_NAME];
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheWhitelist.indexOf(cacheName) === -1) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// Fetch dengan strategi hybrid (Cache-First untuk internal, Network-First/Fallback untuk lainnya)
self.addEventListener('fetch', event => {
  const requestUrl = new URL(event.request.url);

  // Strategi Cache-First (terutama untuk assets internal statis)
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Jika ada di cache, segera kembalikan
        if (response) return response;

        // Jika tidak ada di cache, lakukan fetch
        return fetch(event.request)
          .then(fetchResponse => {
            // Hanya cache GET request dan jika status OK
            if (!fetchResponse || fetchResponse.status !== 200 || event.request.method !== 'GET') {
              return fetchResponse;
            }

            // Jangan cache endpoint API atau PHP dinamis yang tidak perlu di-offline
            if (requestUrl.pathname.endsWith('.php') || requestUrl.pathname.includes('subscribe.php')) {
               return fetchResponse; 
            }
            
            // Jangan cache URL eksternal, atau biarkan strategi default browser yang mengurusnya
            if (requestUrl.origin !== location.origin) {
                return fetchResponse;
            }

            // Klon respons karena stream hanya bisa dibaca sekali
            const responseToCache = fetchResponse.clone();
            caches.open(CACHE_NAME).then(cache => {
              cache.put(event.request, responseToCache);
            });

            return fetchResponse;
          })
          .catch(() => {
            // Fallback ke offline.html hanya untuk navigasi (page)
            if (event.request.mode === 'navigate') {
              return caches.match('/static/offline.html');
            }
          });
      })
  );
});