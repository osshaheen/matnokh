const CACHE = 'matnokh-v1'
self.addEventListener('install', (e) => { self.skipWaiting() })
self.addEventListener('activate', (e) => { e.waitUntil(self.clients.claim()) })
self.addEventListener('fetch', (e) => {
  // network-first for navigations; never cache the API
  const url = new URL(e.request.url)
  if (url.pathname.startsWith('/api')) return
  if (e.request.mode === 'navigate') {
    e.respondWith(fetch(e.request).catch(() => caches.match(e.request)))
  }
})
