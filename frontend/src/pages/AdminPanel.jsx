import React, { useEffect, useState } from 'react'
import { apiFetch } from '../api'

export default function AdminPanel() {
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [settings, setSettings] = useState([])
  const [integrations, setIntegrations] = useState([])

  useEffect(() => {
    async function load() {
      setLoading(true)
      setError(null)
      try {
        const [settingsRes, integrationsRes] = await Promise.all([
          apiFetch('/api/admin/system/settings'),
          apiFetch('/api/admin/system/integrations')
        ])
        setSettings(Array.isArray(settingsRes) ? settingsRes : [])
        setIntegrations(Array.isArray(integrationsRes) ? integrationsRes : [])
      } catch (err) {
        setError(err.message || 'Nie udalo sie pobrac danych panelu admina')
      } finally {
        setLoading(false)
      }
    }
    load()
  }, [])

  return (
    <div className="page">
      <header className="page-header">
        <div>
          <h1>Panel administratora</h1>
          <p className="support-copy">Zarzadzaj konfiguracja systemu i integracjami.</p>
        </div>
      </header>

      <div className="grid two-columns">
        <section className="surface-card">
          <h2>Ustawienia systemowe</h2>
          {loading && <p>Loading...</p>}
          {error && <div className="error">{error}</div>}
          {!loading && !error && (
            <ul className="list">
              {settings.length === 0 && <li>Brak ustawien do wyswietlenia.</li>}
              {settings.map(item => (
                <li key={item.key}>
                  <strong>{item.key}</strong>
                  <div className="support-copy">{String(item.value ?? '')}</div>
                </li>
              ))}
            </ul>
          )}
        </section>

        <section className="surface-card">
          <h2>Integracje</h2>
          {loading && <p>Loading...</p>}
          {error && <div className="error">{error}</div>}
          {!loading && !error && (
            <ul className="list">
              {integrations.length === 0 && <li>Brak zdefiniowanych integracji.</li>}
              {integrations.map(item => (
                <li key={item.id}>
                  <div className="list-row">
                    <div>
                      <strong>{item.name || 'Integracja'}</strong>
                      <div className="support-copy">{item.type || 'typ nieznany'}</div>
                    </div>
                    <span className={`status-pill ${item.enabled ? 'is-success' : 'is-muted'}`}>
                      {item.enabled ? 'Aktywna' : 'Wylaczona'}
                    </span>
                  </div>
                </li>
              ))}
            </ul>
          )}
        </section>
      </div>
    </div>
  )
}
