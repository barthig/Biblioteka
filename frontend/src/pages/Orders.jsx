import React, { useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { apiFetch } from '../api'
import { useAuth } from '../context/AuthContext'
import { useResourceCache } from '../context/ResourceCacheContext'

const ACTIVE_STATUSES = ['PENDING', 'READY']

const STATUS_LABELS = {
  PENDING: 'Przyjęte',
  READY: 'Gotowe do odbioru',
  CANCELLED: 'Anulowane',
  COLLECTED: 'Zrealizowane',
  EXPIRED: 'Wygasło',
}

const PICKUP_LABELS = {
  STORAGE_DESK: 'Magazyn / odbiór w wypożyczalni',
  OPEN_SHELF: 'Półka w wolnym dostępie / odbiór w wypożyczalni',
}

function formatDateTime(value) {
  return value ? new Date(value).toLocaleString() : '—'
}

export default function Orders() {
  const { user } = useAuth()
  const [orders, setOrders] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [actionError, setActionError] = useState(null)
  const { getCachedResource, setCachedResource, invalidateResource } = useResourceCache()
  const CACHE_KEY = '/api/orders?history=true'
  const CACHE_TTL = 60000

  useEffect(() => {
    if (!user?.id) {
      setOrders([])
      setLoading(false)
      invalidateResource(`orders:${CACHE_KEY}`)
      return
    }

    let isMounted = true

    async function load() {
      const cached = getCachedResource(`orders:${CACHE_KEY}`, CACHE_TTL)
      if (cached) {
        setOrders(cached)
        setLoading(false)
        setError(null)
        return
      }

      setLoading(true)
      setError(null)
      try {
        const data = await apiFetch('/api/orders?history=true')
        if (isMounted) {
          setOrders(Array.isArray(data) ? data : [])
          setCachedResource(`orders:${CACHE_KEY}`, Array.isArray(data) ? data : [])
        }
      } catch (err) {
        if (isMounted) {
          setError(err.message || 'Nie udało się pobrać zamówień')
        }
      } finally {
        if (isMounted) setLoading(false)
      }
    }

    load()
    return () => {
      isMounted = false
    }
  }, [getCachedResource, invalidateResource, setCachedResource, user?.id])

  const activeOrders = useMemo(
    () => orders.filter(order => ACTIVE_STATUSES.includes(order.status)),
    [orders]
  )
  const historicalOrders = useMemo(
    () => orders.filter(order => !ACTIVE_STATUSES.includes(order.status)),
    [orders]
  )

  async function cancelOrder(id) {
    setActionError(null)
    try {
      await apiFetch(`/api/orders/${id}`, { method: 'DELETE' })
      setOrders(prev => {
        const updated = prev.map(order => (
          order.id === id
            ? { ...order, status: 'CANCELLED', cancelledAt: new Date().toISOString(), bookCopy: null }
            : order
        ))
        setCachedResource(`orders:${CACHE_KEY}`, updated)
        return updated
      })
    } catch (err) {
      setActionError(err.message || 'Nie udało się anulować zamówienia')
    }
  }

  if (!user?.id) {
    return (
      <div className="page page--centered">
        <div className="surface-card empty-state">
          Aby zamawiać książki do odbioru, <Link to="/login">zaloguj się</Link> lub <Link to="/register">utwórz konto</Link>.
        </div>
      </div>
    )
  }

  if (loading) {
    return (
      <div className="page">
        <div className="surface-card empty-state">Ładowanie zamówień...</div>
      </div>
    )
  }

  return (
    <div className="page">
      <header className="page-header">
        <div>
          <h1>Zamówienia do odbioru</h1>
          <p className="support-copy">Rezerwuj dostępne egzemplarze, aby bibliotekarz przygotował je do wydania.</p>
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

      <div className="page-grid">
        <section className="surface-card">
          <h2>Aktywne</h2>
          {activeOrders.length === 0 ? (
            <div className="empty-state">Brak aktywnych zamówień.</div>
          ) : (
            <ul className="resource-list">
              {activeOrders.map(order => (
                <li key={order.id} className="resource-item">
                  <div>
                    <strong>{order.book?.title ?? 'Nieznana książka'}</strong>
                    <div className="resource-item__meta">
                      <span>Zamówiono: {formatDateTime(order.createdAt)}</span>
                      <span>Termin odbioru: {formatDateTime(order.pickupDeadline)}</span>
                      {order.pickupType && (
                        <span>Odbiór: {PICKUP_LABELS[order.pickupType] ?? order.pickupType}</span>
                      )}
                    </div>
                    {order.bookCopy?.inventoryCode && (
                      <div className="resource-item__meta">
                        <span>Egzemplarz: {order.bookCopy.inventoryCode}</span>
                        {order.bookCopy.location && <span>Lokalizacja: {order.bookCopy.location}</span>}
                      </div>
                    )}
                  </div>
                  <div className="resource-item__actions">
                    <span className={`status-pill ${order.status === 'READY' ? 'is-returned' : ''}`}>
                      {STATUS_LABELS[order.status] ?? order.status}
                    </span>
                    <button
                      type="button"
                      className="btn btn-outline"
                      onClick={() => cancelOrder(order.id)}
                    >
                      Anuluj
                    </button>
                  </div>
                </li>
              ))}
            </ul>
          )}
        </section>

        <section className="surface-card">
          <h2>Historia</h2>
          {historicalOrders.length === 0 ? (
            <div className="empty-state">Brak zrealizowanych zamówień.</div>
          ) : (
            <ul className="resource-list">
              {historicalOrders.map(order => (
                <li key={order.id} className="resource-item">
                  <div>
                    <strong>{order.book?.title ?? 'Nieznana książka'}</strong>
                    <div className="resource-item__meta">
                      <span>Zamówiono: {formatDateTime(order.createdAt)}</span>
                      {order.collectedAt && <span>Odebrano: {formatDateTime(order.collectedAt)}</span>}
                      {order.cancelledAt && <span>Anulowano: {formatDateTime(order.cancelledAt)}</span>}
                      {order.expiredAt && <span>Wygasło: {formatDateTime(order.expiredAt)}</span>}
                      {order.pickupType && (
                        <span>Odbiór: {PICKUP_LABELS[order.pickupType] ?? order.pickupType}</span>
                      )}
                    </div>
                    {order.bookCopy?.inventoryCode && (
                      <div className="resource-item__meta">
                        <span>Egzemplarz: {order.bookCopy.inventoryCode}</span>
                        {order.bookCopy.location && <span>Lokalizacja: {order.bookCopy.location}</span>}
                      </div>
                    )}
                  </div>
                  <span className={`status-pill ${order.status === 'COLLECTED' ? 'is-returned' : 'is-danger'}`}>
                    {STATUS_LABELS[order.status] ?? order.status}
                  </span>
                </li>
              ))}
            </ul>
          )}
        </section>
      </div>
    </div>
  )
}
