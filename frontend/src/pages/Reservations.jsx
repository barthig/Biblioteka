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
import { logger } from '../utils/logger'

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('pl-PL') : '-'
}

export default function Reservations() {
  const { user } = useAuth()
  const [reservations, setReservations] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [actionError, setActionError] = useState(null)
  const { getCachedResource, setCachedResource, invalidateResource } = useResourceCache()
  const CACHE_KEY = '/api/reservations?history=true'
  const CACHE_TTL = 60000

  useEffect(() => {
    let active = true

    async function load() {
      const cached = getCachedResource(`reservations:${CACHE_KEY}`, CACHE_TTL)
      if (cached) {
        const list = Array.isArray(cached) ? cached : (Array.isArray(cached?.data) ? cached.data : [])
        setReservations(list)
        setLoading(false)
        setError(null)
        return
      }

      setLoading(true)
      setError(null)
      try {
        const data = await apiFetch('/api/reservations?history=true')
        if (active) {
          const list = Array.isArray(data?.data) ? data.data : []
          setReservations(list)
          setCachedResource(`reservations:${CACHE_KEY}`, list)
        }
      } catch (err) {
        if (active) {
          setError(err.message || 'Nie udało się pobrać rezerwacji')
        }
      } finally {
        if (active) {
          setLoading(false)
        }
      }
    }

    if (user?.id) {
      load()
    } else {
      setLoading(false)
      setReservations([])
      invalidateResource('reservations:/api/reservations*')
    }

    // Poll for cache changes every 2 seconds
    const interval = setInterval(() => {
      if (user?.id) {
        const cached = getCachedResource(`reservations:${CACHE_KEY}`, CACHE_TTL)
        if (!cached) {
          load()
        }
      }
    }, 2000)

    return () => {
      active = false
      clearInterval(interval)
    }
  }, [getCachedResource, invalidateResource, setCachedResource, user?.id, CACHE_KEY, CACHE_TTL])

  const activeReservations = useMemo(() => {
    const reservationsArray = Array.isArray(reservations) ? reservations : []
    return reservationsArray.filter(reservation => reservation.status === 'ACTIVE')
  }, [reservations])
  
  const historicalReservations = useMemo(() => {
    const reservationsArray = Array.isArray(reservations) ? reservations : []
    return reservationsArray.filter(reservation => reservation.status !== 'ACTIVE')
  }, [reservations])

  const nextExpiry = useMemo(() => {
    const dates = activeReservations
      .map(reservation => reservation.expiresAt)
      .filter(Boolean)
      .map(value => new Date(value))
      .sort((a, b) => a.getTime() - b.getTime())
    return dates.length > 0 ? dates[0] : null
  }, [activeReservations])

  async function cancelReservation(id) {
    setActionError(null)
    try {
      await apiFetch(`/api/reservations/${id}`, { method: 'DELETE' })
      setReservations(prev => {
        const next = prev.map(item => (
          item.id === id
            ? { ...item, status: 'CANCELLED', cancelledAt: new Date().toISOString() }
            : item
        ))
        setCachedResource(`reservations:${CACHE_KEY}`, next)
        return next
      })
      // Invalidate recommended cache as book availability may have changed
      invalidateResource('recommended:*')
    } catch (err) {
      logger.error('Error cancelling reservation:', err)
      setActionError(err.message || 'Nie udało się anulować rezerwacji')
    }
  }

  if (!user?.id) {
    return (
      <div className="page page--centered">
        <div className="surface-card empty-state">
          Aby zarządzać rezerwacjami, <Link to="/login">zaloguj się</Link> lub <Link to="/register">utwórz konto</Link>.
        </div>
      </div>
    )
  }

  if (loading) {
    return (
      <div className="page">
        <div className="surface-card empty-state">Ładowanie rezerwacji...</div>
      </div>
    )
  }

  return (
    <div className="page">
      <PageHeader
        title="Moje rezerwacje"
        subtitle="Śledź kolejkę zamówień i odbieraj egzemplarze na czas."
      />

      <StatGrid>
        <StatCard title="Aktywne rezerwacje" value={activeReservations.length} subtitle="Do odbioru" />
        <StatCard title="Zrealizowane" value={historicalReservations.length} subtitle="Historia" />
        <StatCard title="Najbliższy termin" value={nextExpiry ? formatDate(nextExpiry) : '-'} valueClassName="stat-card__value--sm" />
      </StatGrid>

      {error && <FeedbackCard variant="error">{error}</FeedbackCard>}
      {actionError && <FeedbackCard variant="error">{actionError}</FeedbackCard>}

      <div className="page-grid">
        <SectionCard title="Aktywne">
          {activeReservations.length === 0 ? (
            <div className="empty-state">Brak aktywnych rezerwacji.</div>
          ) : (
            <ul className="resource-list">
              {activeReservations.map(reservation => (
                <li key={reservation.id} className="resource-item">
                  <div>
                    <strong>{reservation.book?.title ?? 'Nieznana książka'}</strong>
                    <div className="resource-item__meta">
                      <span>Zarezerwowano: {formatDate(reservation.reservedAt)}</span>
                      <span>Wygasa: {formatDate(reservation.expiresAt)}</span>
                    </div>
                    {reservation.bookCopy?.inventoryCode && (
                      <div className="resource-item__meta">
                        <span>Kod egzemplarza: {reservation.bookCopy.inventoryCode}</span>
                      </div>
                    )}
                  </div>
                  <div className="resource-item__actions">
                    <span className="status-pill">Aktywna</span>
                    <button
                      type="button"
                      className="btn btn-outline"
                      onClick={() => cancelReservation(reservation.id)}
                    >
                      Anuluj
                    </button>
                  </div>
                </li>
              ))}
            </ul>
          )}
        </SectionCard>

        <SectionCard title="Historia">
          {historicalReservations.length === 0 ? (
            <div className="empty-state">Brak zrealizowanych lub anulowanych rezerwacji.</div>
          ) : (
            <ul className="resource-list">
              {historicalReservations.map(reservation => (
                <li key={reservation.id} className="resource-item">
                  <div>
                    <strong>{reservation.book?.title ?? 'Nieznana książka'}</strong>
                    <div className="resource-item__meta">
                      <span>Zarezerwowano: {formatDate(reservation.reservedAt)}</span>
                      {reservation.fulfilledAt && <span>Zrealizowano: {formatDate(reservation.fulfilledAt)}</span>}
                      {reservation.cancelledAt && <span>Anulowano: {formatDate(reservation.cancelledAt)}</span>}
                    </div>
                  </div>
                  <span className={`status-pill ${reservation.status === 'FULFILLED' ? 'is-returned' : 'is-danger'}`}>
                    {reservation.status === 'FULFILLED' ? 'Zrealizowana' : (reservation.status === 'CANCELLED' ? 'Anulowana' : 'Wygasła')}
                  </span>
                </li>
              ))}
            </ul>
          )}
        </SectionCard>
      </div>
    </div>
  )
}
