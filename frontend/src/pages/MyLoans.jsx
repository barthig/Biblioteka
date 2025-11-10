import React, { useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { apiFetch } from '../api'
import { useAuth } from '../context/AuthContext'

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString() : '—'
}

export default function MyLoans() {
  const { token, user } = useAuth()
  const [loans, setLoans] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
    if (!token) {
      setLoans([])
      setLoading(false)
      return
    }

    let cancelled = false

    async function load() {
      setLoading(true)
      setError(null)
      try {
        const data = await apiFetch('/api/loans')
        if (!cancelled) {
          setLoans(Array.isArray(data) ? data : [])
        }
      } catch (err) {
        if (!cancelled) {
          setError(err.message || 'Nie udało się pobrać wypożyczeń')
        }
      } finally {
        if (!cancelled) {
          setLoading(false)
        }
      }
    }

    load()
    return () => {
      cancelled = true
    }
  }, [token])

  const activeLoans = useMemo(() => loans.filter(loan => !loan.returnedAt), [loans])
  const historyLoans = useMemo(
    () => loans.filter(loan => loan.returnedAt).sort((a, b) => new Date(b.returnedAt).getTime() - new Date(a.returnedAt).getTime()),
    [loans]
  )

  if (!token || !user?.id) {
    return (
      <div className="page page--centered">
        <div className="surface-card empty-state">
          Aby zobaczyć swoje wypożyczenia, <Link to="/login">zaloguj się</Link>.
        </div>
      </div>
    )
  }

  if (loading) {
    return (
      <div className="page">
        <div className="surface-card empty-state">Ładowanie wypożyczeń...</div>
      </div>
    )
  }

  return (
    <div className="page">
      <header className="page-header">
        <div>
          <h1>Moje wypożyczenia</h1>
          <p className="support-copy">Sprawdź bieżące wypożyczenia oraz historię zwrotów.</p>
        </div>
      </header>

      {error && (
        <div className="surface-card">
          <p className="error">{error}</p>
        </div>
      )}

      <div className="page-grid">
        <section className="surface-card">
          <h2>Aktywne wypożyczenia</h2>
          {activeLoans.length === 0 ? (
            <div className="empty-state">Brak aktywnych wypożyczeń.</div>
          ) : (
            <ul className="resource-list">
              {activeLoans.map(loan => (
                <li key={loan.id} className="resource-item">
                  <div>
                    <strong>{loan.book?.title ?? 'Nieznana książka'}</strong>
                    <div className="resource-item__meta">
                      <span>Termin zwrotu: {formatDate(loan.dueAt)}</span>
                      {loan.bookCopy?.inventoryCode && <span>Kod egz.: {loan.bookCopy.inventoryCode}</span>}
                    </div>
                  </div>
                  <span className="status-pill">Wypożyczono</span>
                </li>
              ))}
            </ul>
          )}
        </section>

        <section className="surface-card">
          <h2>Historia zwrotów</h2>
          {historyLoans.length === 0 ? (
            <div className="empty-state">Brak zwróconych wypożyczeń.</div>
          ) : (
            <ul className="resource-list">
              {historyLoans.map(loan => (
                <li key={loan.id} className="resource-item">
                  <div>
                    <strong>{loan.book?.title ?? 'Nieznana książka'}</strong>
                    <div className="resource-item__meta">
                      <span>Wypożyczono: {formatDate(loan.borrowedAt)}</span>
                      <span>Zwrócono: {formatDate(loan.returnedAt)}</span>
                    </div>
                  </div>
                  <span className="status-pill is-returned">Zwrócono</span>
                </li>
              ))}
            </ul>
          )}
        </section>
      </div>
    </div>
  )
}
