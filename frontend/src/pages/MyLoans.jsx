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
  const [actionError, setActionError] = useState(null)
  const [actionSuccess, setActionSuccess] = useState(null)
  const [extendDays, setExtendDays] = useState({})
  const [extendLoading, setExtendLoading] = useState({})

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
      setActionError(null)
      setActionSuccess(null)
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

  async function extendLoan(id, days = 14) {
    setActionError(null)
    setActionSuccess(null)
    setExtendLoading(prev => ({ ...prev, [id]: true }))
    try {
      const updatedLoan = await apiFetch(`/api/loans/${id}/extend`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ days }),
      })
      setLoans(prev => prev.map(loan => (loan.id === id ? updatedLoan : loan)))
      setActionSuccess(`Termin wypożyczenia został przedłużony do ${formatDate(updatedLoan.dueAt)}.`)
      setExtendDays(prev => ({ ...prev, [id]: 14 }))
    } catch (err) {
      setActionError(err.message || 'Nie udało się przedłużyć wypożyczenia')
    } finally {
      setExtendLoading(prev => ({ ...prev, [id]: false }))
    }
  }

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
      {actionError && (
        <div className="surface-card">
          <p className="error">{actionError}</p>
        </div>
      )}
      {actionSuccess && (
        <div className="surface-card">
          <p className="success">{actionSuccess}</p>
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
                      {typeof loan.extensionsCount === 'number' && <span>Przedłużenia: {loan.extensionsCount}</span>}
                    </div>
                    {loan.lastExtendedAt && (
                      <div className="resource-item__meta">
                        <span>Ostatnio przedłużono: {formatDate(loan.lastExtendedAt)}</span>
                      </div>
                    )}
                  </div>
                  <div className="resource-item__actions loan-actions">
                    <span className="status-pill">Wypożyczono</span>
                    {(loan.extensionsCount ?? 0) < 1 && (
                      <div className="loan-extend">
                        <label htmlFor={`loan-extend-${loan.id}`} className="sr-only">Liczba dni przedłużenia</label>
                        <select
                          id={`loan-extend-${loan.id}`}
                          value={extendDays[loan.id] ?? 14}
                          onChange={event => setExtendDays(prev => ({ ...prev, [loan.id]: Number(event.target.value) }))}
                        >
                          {[7, 14, 21, 28].map(option => (
                            <option key={option} value={option}>{`+${option} dni`}</option>
                          ))}
                        </select>
                        <button
                          type="button"
                          className="btn btn-outline"
                          disabled={extendLoading[loan.id]}
                          onClick={() => extendLoan(loan.id, extendDays[loan.id] ?? 14)}
                        >
                          {extendLoading[loan.id] ? 'Przetwarzanie...' : 'Przedłuż'}
                        </button>
                      </div>
                    )}
                  </div>
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
