const SW_URL = '/sw.js'

export function registerServiceWorker() {
  if (!import.meta.env.PROD) {
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', async () => {
        const registrations = await navigator.serviceWorker.getRegistrations()
        await Promise.all(registrations.map((registration) => registration.unregister()))
      })
    }
    return
  }

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
      window.dispatchEvent(
        new CustomEvent('pwa:registration-failed', {
          detail: error instanceof Error ? error.message : 'unknown-error',
        }),
      )
    }
  })
}
