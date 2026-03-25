import React, { useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { apiFetch } from '../../api'
import { useAuth } from '../../context/AuthContext'
import { useResourceCache } from '../../context/ResourceCacheContext'
import PageHeader from '../../components/ui/PageHeader'
import StatGrid from '../../components/ui/StatGrid'
import StatCard from '../../components/ui/StatCard'
import SectionCard from '../../components/ui/SectionCard'
import FeedbackCard from '../../components/ui/FeedbackCard'

const ACTIVE_STATUSES = ['ACTIVE', 'PREPARED']
const RESERVATIONS_HISTORY_ENDPOINT = '/api/reservations?history=true'

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('pl-PL') : '-'
}

function canCancelReservation(reservation) {
  if (!reservation || !ACTIVE_STATUSES.includes(reservation.status)) {
    return false
  }

  return reservation.bookCopy?.status !== 'BORROWED'
}

export default function Reservations() {
  const { user } = useAuth()
  const [reservations, setReservations] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [actionError, setActionError] = useState(null)
  const [cancelling, setCancelling] = useState({})
  const { getCachedResource, setCachedResource, invalidateResource } = useResourceCache()
  const CACHE_KEY = `reservations:${user?.id ?? 'anon'}:${RESERVATIONS_HISTORY_ENDPOINT}`
  const CACHE_TTL = 60000
  const currentUserId = user?.id ? Number(user.id) : null

  const filterReservationsForCurrentUser = useMemo(() => (items) => {
    if (!currentUserId) {
      return []
    }

    return (Array.isArray(items) ? items : []).filter((reservation) => {
      const reservationUserId = Number(
        reservation?.user?.id
        ?? reservation?.userId
        ?? reservation?.readerId
        ?? 0
      )

      return reservationUserId === currentUserId
    })
  }, [currentUserId])

  useEffect(() => {
    let active = true

    async function load() {
      const cached = getCachedResource(CACHE_KEY, CACHE_TTL)
      if (cached) {
        const list = filterReservationsForCurrentUser(
          Array.isArray(cached) ? cached : (Array.isArray(cached?.data) ? cached.data : [])
        )
        setReservations(list)
        setLoading(false)
        setError(null)
        return
      }

      setLoading(true)
      setError(null)
      try {
        const data = await apiFetch(RESERVATIONS_HISTORY_ENDPOINT)
        if (active) {
          const list = filterReservationsForCurrentUser(Array.isArray(data?.data) ? data.data : [])
          setReservations(list)
          setCachedResource(CACHE_KEY, list)
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
      invalidateResource('reservations:*')
    }

    return () => {
      active = false
    }
  }, [CACHE_KEY, CACHE_TTL, currentUserId, filterReservationsForCurrentUser, getCachedResource, invalidateResource, setCachedResource, user?.id])

  const statusMeta = (status) => {
    switch (status) {
      case 'ACTIVE':
        return { label: 'Aktywna', className: '' }
      case 'PREPARED':
        return { label: 'Przygotowana do odbioru', className: 'is-warning' }
      case 'FULFILLED':
        return { label: 'Zrealizowana', className: 'is-returned' }
      case 'CANCELLED':
        return { label: 'Anulowana', className: 'is-danger' }
      case 'EXPIRED':
        return { label: 'Wygasła', className: 'is-danger' }
      default:
        return { label: 'Nieznany', className: 'is-danger' }
    }
  }

  const activeReservations = useMemo(() => {
    const reservationsArray = Array.isArray(reservations) ? reservations : []
    return reservationsArray.filter(reservation => ACTIVE_STATUSES.includes(reservation.status))
  }, [reservations])
  
  const activeOnlyReservations = useMemo(() => {
    return activeReservations.filter(reservation => reservation.status === 'ACTIVE')
  }, [activeReservations])

  const preparedReservations = useMemo(() => {
    return activeReservations.filter(reservation => reservation.status === 'PREPARED')
  }, [activeReservations])

  const historicalReservations = useMemo(() => {
    const reservationsArray = Array.isArray(reservations) ? reservations : []
    return reservationsArray.filter(reservation => !ACTIVE_STATUSES.includes(reservation.status))
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
    if (cancelling[id]) {
      return
    }

    setActionError(null)
    setCancelling(prev => ({ ...prev, [id]: true }))
    try {
      await apiFetch(`/api/reservations/${id}`, { method: 'DELETE' })
      setReservations(prev => {
        const next = prev.map(item => (
          item.id === id
            ? { ...item, status: 'CANCELLED', cancelledAt: new Date().toISOString() }
            : item
        ))
        setCachedResource(CACHE_KEY, next)
        return next
      })
      // Invalidate recommended cache as book availability may have changed
      invalidateResource('recommended:*')
    } catch (err) {
      if (err?.status === 422 || err?.status === 404) {
        invalidateResource(CACHE_KEY)
        setReservations(prev => prev.map(item => (
          item.id === id
            ? {
                ...item,
                status: item.bookCopy?.status === 'BORROWED' ? 'PREPARED' : item.status,
              }
            : item
        )))
        setActionError(
          err?.data?.error?.message
            || 'Ta rezerwacja nie może już zostać anulowana.'
        )
      } else {
        setActionError(err.message || 'Nie udało się anulować rezerwacji')
      }
    } finally {
      setCancelling(prev => ({ ...prev, [id]: false }))
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
        <StatCard title="Aktywne rezerwacje" value={activeOnlyReservations.length} subtitle="W trakcie realizacji" />
        <StatCard title="Przygotowane" value={preparedReservations.length} subtitle="Do odbioru" />
        <StatCard title="Zrealizowane" value={historicalReservations.length} subtitle="Historia" />
        <StatCard title="Najblizszy termin" value={nextExpiry ? formatDate(nextExpiry) : '-'} valueClassName="stat-card__value--sm" />
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
                    {(() => {
                      const meta = statusMeta(reservation.status)
                      return (
                        <span className={`status-pill ${meta.className}`.trim()}>
                          {meta.label}
                        </span>
                      )
                    })()}
                    {canCancelReservation(reservation) ? (
                      <button
                        type="button"
                        className="btn btn-outline"
                        disabled={Boolean(cancelling[reservation.id])}
                        onClick={() => cancelReservation(reservation.id)}
                      >
                        {cancelling[reservation.id] ? 'Anulowanie...' : 'Anuluj'}
                      </button>
                    ) : (
                      <span className="resource-item__hint">
                        {reservation.bookCopy?.status === 'BORROWED' ? 'Egzemplarz jest już przygotowany do realizacji.' : 'Anulowanie niedostępne.'}
                      </span>
                    )}
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
                  {(() => {
                    const meta = statusMeta(reservation.status)
                    return (
                      <span className={`status-pill ${meta.className}`.trim()}>
                        {meta.label}
                      </span>
                    )
                  })()}
                </li>
              ))}
            </ul>
          )}
        </SectionCard>
      </div>
    </div>
  )
}




