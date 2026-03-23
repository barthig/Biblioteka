const SW_URL = '/sw.js'

export function registerServiceWorker() {
  if (!('serviceWorker' in navigator)) {
    return
  }

  window.addEventListener('load', async () => {
    try {
      const registration = await navigator.serviceWorker.register(SW_URL)

      registration.addEventListener('updatefound', () => {
        const installingWorker = registration.installing
        if (!installingWorker) {
          return
        }

        installingWorker.addEventListener('statechange', () => {
          if (installingWorker.state === 'installed' && navigator.serviceWorker.controller) {
            window.dispatchEvent(new CustomEvent('pwa:update-available'))
          }
        })
      })
    } catch (error) {
      console.warn('Service worker registration failed', error)
    }
  })
}
