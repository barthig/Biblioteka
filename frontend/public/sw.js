const VERSION = 'v2'
const SHELL_CACHE = `biblioteka-shell-${VERSION}`
const RUNTIME_CACHE = `biblioteka-runtime-${VERSION}`
const API_CACHE = `biblioteka-api-${VERSION}`
const OFFLINE_URL = '/offline.html'
const APP_SHELL = [
  '/',
  OFFLINE_URL,
  '/manifest.json',
  '/favicon.svg',
  '/icon-192.png',
  '/icon-512.png',
  '/icon-512-maskable.png'
]

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(SHELL_CACHE).then((cache) => cache.addAll(APP_SHELL))
  )
  self.skipWaiting()
})

self.addEventListener('message', (event) => {
  if (event.data?.type === 'SKIP_WAITING') {
    self.skipWaiting()
  }
})

self.addEventListener('activate', (event) => {
  const keep = new Set([SHELL_CACHE, RUNTIME_CACHE, API_CACHE])
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((key) => !keep.has(key)).map((key) => caches.delete(key)))
    )
  )
  self.clients.claim()
})

self.addEventListener('fetch', (event) => {
  const { request } = event
  if (request.method !== 'GET') {
    return
  }

  const url = new URL(request.url)
  const isSameOrigin = url.origin === self.location.origin

  if (request.mode === 'navigate') {
    event.respondWith(handleNavigation(request))
    return
  }

  if (isSameOrigin && url.pathname.startsWith('/api/')) {
    event.respondWith(handleApiRequest(request))
    return
  }

  if (isStaticAsset(request, url, isSameOrigin)) {
    event.respondWith(cacheFirst(request, RUNTIME_CACHE))
    return
  }

  if (isSameOrigin) {
    event.respondWith(staleWhileRevalidate(request, RUNTIME_CACHE))
  }
})

async function handleNavigation(request) {
  try {
    const networkResponse = await fetch(request)
    const cache = await caches.open(RUNTIME_CACHE)
    cache.put(request, networkResponse.clone())
    return networkResponse
  } catch {
    return (await caches.match(request)) || (await caches.match('/')) || (await caches.match(OFFLINE_URL))
  }
}

async function handleApiRequest(request) {
  const cache = await caches.open(API_CACHE)

  try {
    const networkResponse = await fetch(request)
    if (networkResponse.ok) {
      cache.put(request, networkResponse.clone())
    }
    return networkResponse
  } catch {
    const cached = await cache.match(request)
    if (cached) {
      return cached
    }

    return new Response(
      JSON.stringify({
        code: 'OFFLINE',
        message: 'API unavailable while offline'
      }),
      {
        status: 503,
        headers: {
          'Content-Type': 'application/json'
        }
      }
    )
  }
}

async function cacheFirst(request, cacheName) {
  const cache = await caches.open(cacheName)
  const cached = await cache.match(request)
  if (cached) {
    return cached
  }

  const response = await fetch(request)
  if (response.ok) {
    cache.put(request, response.clone())
  }
  return response
}

async function staleWhileRevalidate(request, cacheName) {
  const cache = await caches.open(cacheName)
  const cached = await cache.match(request)

  const networkPromise = fetch(request)
    .then((response) => {
      if (response.ok) {
        cache.put(request, response.clone())
      }
      return response
    })
    .catch(() => null)

  return cached || networkPromise || caches.match(OFFLINE_URL)
}

function isStaticAsset(request, url, isSameOrigin) {
  if (!isSameOrigin) {
    return false
  }

  if (request.destination === 'script' || request.destination === 'style' || request.destination === 'image' || request.destination === 'font') {
    return true
  }

  return url.pathname.startsWith('/assets/') || url.pathname.endsWith('.js') || url.pathname.endsWith('.css')
}
