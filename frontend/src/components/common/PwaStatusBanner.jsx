import React, { useEffect, useState } from 'react'
import { reloadForPwaUpdate, triggerPwaInstall } from '../../registerServiceWorker'

export default function PwaStatusBanner() {
  const [updateAvailable, setUpdateAvailable] = useState(false)
  const [installAvailable, setInstallAvailable] = useState(false)
  const [offline, setOffline] = useState(() => typeof navigator !== 'undefined' ? !navigator.onLine : false)
  const [message, setMessage] = useState(null)

  useEffect(() => {
    const handleUpdateAvailable = () => setUpdateAvailable(true)
    const handleInstallAvailable = event => setInstallAvailable(Boolean(event.detail?.available))
    const handleInstalled = () => {
      setInstallAvailable(false)
      setMessage('Aplikacja zostala zainstalowana na urzadzeniu.')
    }
    const handleRegistrationFailed = event => {
      setMessage(`Nie udalo sie wlaczyc funkcji offline: ${event.detail || 'nieznany blad'}`)
    }
    const handleOnline = () => setOffline(false)
    const handleOffline = () => setOffline(true)

    window.addEventListener('pwa:update-available', handleUpdateAvailable)
    window.addEventListener('pwa:install-available', handleInstallAvailable)
    window.addEventListener('pwa:installed', handleInstalled)
    window.addEventListener('pwa:registration-failed', handleRegistrationFailed)
    window.addEventListener('online', handleOnline)
    window.addEventListener('offline', handleOffline)

    return () => {
      window.removeEventListener('pwa:update-available', handleUpdateAvailable)
      window.removeEventListener('pwa:install-available', handleInstallAvailable)
      window.removeEventListener('pwa:installed', handleInstalled)
      window.removeEventListener('pwa:registration-failed', handleRegistrationFailed)
      window.removeEventListener('online', handleOnline)
      window.removeEventListener('offline', handleOffline)
    }
  }, [])

  async function handleInstall() {
    const accepted = await triggerPwaInstall()
    if (!accepted) {
      setMessage('Instalacja aplikacji zostala anulowana.')
    }
  }

  if (!updateAvailable && !installAvailable && !offline && !message) {
    return null
  }

  return (
    <section className="pwa-banner" aria-live="polite">
      {offline && (
        <div className="pwa-banner__item pwa-banner__item--warning">
          <div>
            <strong>Tryb offline</strong>
            <p>Widoczne sa zapisane zasoby i ostatnio odwiedzone dane. Operacje wymagajace sieci moga byc niedostepne.</p>
          </div>
        </div>
      )}

      {installAvailable && (
        <div className="pwa-banner__item">
          <div>
            <strong>Zainstaluj aplikacje</strong>
            <p>Dodaj Biblioteke do ekranu glownego, aby korzystac z niej jak z aplikacji natywnej.</p>
          </div>
          <button type="button" className="btn btn-primary" onClick={handleInstall}>
            Zainstaluj
          </button>
        </div>
      )}

      {updateAvailable && (
        <div className="pwa-banner__item">
          <div>
            <strong>Dostepna jest nowa wersja</strong>
            <p>Odswiez aplikacje, aby wlaczyc najnowsza wersje zapisanej powloki i zasobow.</p>
          </div>
          <button type="button" className="btn btn-secondary" onClick={reloadForPwaUpdate}>
            Odswiez aplikacje
          </button>
        </div>
      )}

      {message && (
        <div className="pwa-banner__item pwa-banner__item--info">
          <div>
            <strong>Status PWA</strong>
            <p>{message}</p>
          </div>
          <button type="button" className="btn btn-link" onClick={() => setMessage(null)}>
            Zamknij
          </button>
        </div>
      )}
    </section>
  )
}