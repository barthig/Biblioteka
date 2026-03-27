const SW_URL = '/sw.js'

let deferredInstallPrompt = null
let activeRegistration = null

function broadcastInstallAvailability() {
  window.dispatchEvent(new CustomEvent('pwa:install-available', {
    detail: {
      available: Boolean(deferredInstallPrompt)
    }
  }))
}

export async function triggerPwaInstall() {
  if (!deferredInstallPrompt) {
    return false
  }

  const promptEvent = deferredInstallPrompt
  deferredInstallPrompt = null
  broadcastInstallAvailability()

  await promptEvent.prompt()
  const outcome = await promptEvent.userChoice
  window.dispatchEvent(new CustomEvent('pwa:install-finished', { detail: outcome }))

  return outcome?.outcome === 'accepted'
}

export function reloadForPwaUpdate() {
  if (!activeRegistration?.waiting) {
    window.location.reload()
    return
  }

  activeRegistration.waiting.postMessage({ type: 'SKIP_WAITING' })
}

export function registerServiceWorker() {
  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault()
    deferredInstallPrompt = event
    broadcastInstallAvailability()
  })

  window.addEventListener('appinstalled', () => {
    deferredInstallPrompt = null
    broadcastInstallAvailability()
    window.dispatchEvent(new CustomEvent('pwa:installed'))
  })

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
      activeRegistration = registration

      if (registration.waiting) {
        window.dispatchEvent(new CustomEvent('pwa:update-available'))
      }

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

      navigator.serviceWorker.addEventListener('controllerchange', () => {
        window.location.reload()
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
