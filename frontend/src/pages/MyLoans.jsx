import React, { useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { apiFetch } from '../api'
import { useAuth } from '../context/AuthContext'
import { useResourceCache } from '../context/ResourceCacheContext'
import PageHeader from '../components/ui/PageHeader'
import StatGrid from '../components/ui/StatGrid'
import StatCard from '../components/ui/StatCard'
import SectionCard from '../components/ui/SectionCard'
import FeedbackCard from '../components/ui/FeedbackCard'

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('pl-PL') : '-'
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
  const { getCachedResource, setCachedResource, invalidateResource } = useResourceCache()
  const CACHE_KEY = 'loans:/api/loans'
  const CACHE_TTL = 60000

  useEffect(() => {
    if (!token) {
      setLoans([])
      setLoading(false)
      invalidateResource('loans:*')
      return
    }

    let cancelled = false

    async function load() {
      const cached = getCachedResource(CACHE_KEY, CACHE_TTL)
      if (typeof cached !== 'undefined') {
        const loansList = Array.isArray(cached) ? cached : (Array.isArray(cached?.data) ? cached.data : [])
        setLoans(loansList)
        setLoading(false)
        setError(null)
      } else {
        setLoading(true)
      }

      setError(null)
      setActionError(null)
      setActionSuccess(null)
      try {
        const data = await apiFetch('/api/loans')
        if (!cancelled) {
          const list = Array.isArray(data?.data) ? data.data : []
          setLoans(list)
          setCachedResource(CACHE_KEY, list)
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

    // Poll for cache changes every 2 seconds
    const interval = setInterval(() => {
      if (token) {
        const cached = getCachedResource(CACHE_KEY, CACHE_TTL)
        if (!cached) {
          load()
        }
      }
    }, 2000)

    return () => {
      cancelled = true
      clearInterval(interval)
    }
  }, [CACHE_KEY, CACHE_TTL, getCachedResource, invalidateResource, setCachedResource, token])

  const activeLoans = useMemo(() => {
    const loansArray = Array.isArray(loans) ? loans : []
    return loansArray.filter(loan => !loan.returnedAt)
  }, [loans])
  
  const historyLoans = useMemo(() => {
    const loansArray = Array.isArray(loans) ? loans : []
    return loansArray.filter(loan => loan.returnedAt).sort((a, b) => new Date(b.returnedAt).getTime() - new Date(a.returnedAt).getTime())
  }, [loans])

  const nextDue = useMemo(() => {
    const dates = activeLoans
      .map(loan => loan.dueAt)
      .filter(Boolean)
      .map(value => new Date(value))
      .sort((a, b) => a.getTime() - b.getTime())
    return dates.length > 0 ? dates[0] : null
  }, [activeLoans])

  async function extendLoan(id, days = 14) {
    setActionError(null)
    setActionSuccess(null)
    setExtendLoading(prev => ({ ...prev, [id]: true }))
    try {
      const response = await apiFetch(`/api/loans/${id}/extend`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ days }),
      })
      const updatedLoan = response?.data || response
      setLoans(prev => {
        const next = prev.map(loan => (loan.id === id ? updatedLoan : loan))
        setCachedResource(CACHE_KEY, next)
        return next
      })
      // Invalidate reservations cache as book availability may have changed
      invalidateResource('reservations:*')
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
      <PageHeader
        title="Moje wypożyczenia"
        subtitle="Sprawdź bieżące wypożyczenia oraz historię zwrotów."
      />

      <StatGrid>
        <StatCard title="Aktywne wypożyczenia" value={activeLoans.length} subtitle="Do zwrotu" />
        <StatCard title="Zwrócone książki" value={historyLoans.length} subtitle="Historia" />
        <StatCard title="Najbliższy termin" value={nextDue ? formatDate(nextDue) : '-'} subtitle="Najbliższy zwrot" />
      </StatGrid>

      {error && <FeedbackCard variant="error">{error}</FeedbackCard>}
      {actionError && <FeedbackCard variant="error">{actionError}</FeedbackCard>}
      {actionSuccess && <FeedbackCard variant="success">{actionSuccess}</FeedbackCard>}

      <div className="page-grid">
        <SectionCard title="Aktywne wypożyczenia">
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
        </SectionCard>

        <SectionCard title="Historia zwrotów">
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
        </SectionCard>
      </div>
    </div>
  )
}
