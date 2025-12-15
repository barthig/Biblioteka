import React, { useEffect, useState } from 'react'
import { notificationService } from '../services/notificationService'
import { useAuth } from '../context/AuthContext'

export default function Notifications() {
  const { user } = useAuth()
  const isAdmin = user?.roles?.includes('ROLE_ADMIN')
  const [items, setItems] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [info, setInfo] = useState(null)

  useEffect(() => {
    let active = true
    async function load() {
      setLoading(true)
      setError(null)
      try {
        const data = await notificationService.list()
        if (!active) return
        const list = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : []
        setItems(list)
      } catch (err) {
        if (active) setError(err.message || 'Nie udało się pobrać powiadomień')
      } finally {
        if (active) setLoading(false)
      }
    }
    load()
    return () => { active = false }
  }, [])

  async function sendTest() {
    setError(null)
    setInfo(null)
    try {
      await notificationService.sendTest()
      setInfo('Wysłano testowe powiadomienie.')
    } catch (err) {
      setError(err.message || 'Nie udało się wysłać testowego powiadomienia')
    }
  }

  return (
    <div className="page">
      <header className="page-header">
        <div>
          <h1>Powiadomienia</h1>
          <p>Lista ostatnich powiadomień systemowych</p>
        </div>
        {isAdmin && (
          <button className="btn btn-primary" onClick={sendTest}>
            Wyślij test
          </button>
        )}
      </header>

      {loading && (
        <div className="surface-card">Ładowanie…</div>
      )}

      {error && (
        <div className="surface-card error">{error}</div>
      )}

      {info && (
        <div className="surface-card success">{info}</div>
      )}

      {!loading && !error && (
        <div className="surface-card">
          {items.length === 0 ? (
            <p>Brak powiadomień.</p>
          ) : (
            <ul className="list list--bordered">
              {items.map((n) => (
                <li key={n.id || `${n.type}-${n.createdAt}`}>
                  <div className="list__title">{n.title || n.subject || 'Powiadomienie'}</div>
                  <div className="list__meta">
                    <span>{n.type || 'info'}</span>
                    {n.createdAt && <span>{new Date(n.createdAt).toLocaleString('pl-PL')}</span>}
                  </div>
                  {n.message && <div className="list__desc">{n.message}</div>}
                </li>
              ))}
            </ul>
          )}
        </div>
      )}
    </div>
  )
}
