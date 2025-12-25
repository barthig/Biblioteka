import React, { useEffect, useMemo, useState } from 'react'
import { notificationService } from '../services/notificationService'
import { useAuth } from '../context/AuthContext'
import PageHeader from '../components/ui/PageHeader'
import StatGrid from '../components/ui/StatGrid'
import StatCard from '../components/ui/StatCard'
import SectionCard from '../components/ui/SectionCard'
import FeedbackCard from '../components/ui/FeedbackCard'

export default function Notifications() {
  const { user } = useAuth()
  const isAdmin = user?.roles?.includes('ROLE_ADMIN')
  const [items, setItems] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [info, setInfo] = useState(null)
  const [readKeys, setReadKeys] = useState(() => {
    try {
      const stored = localStorage.getItem('notifications:read')
      const parsed = stored ? JSON.parse(stored) : []
      return new Set(Array.isArray(parsed) ? parsed : [])
    } catch {
      return new Set()
    }
  })
  const STORAGE_KEY = 'notifications:read'
  const latestDate = useMemo(() => {
    const dates = items
      .map(item => item.createdAt)
      .filter(Boolean)
      .map(value => new Date(value))
      .sort((a, b) => b.getTime() - a.getTime())
    return dates[0] ?? null
  }, [items])

  const unreadCount = useMemo(() => {
    return items.reduce((count, item) => {
      const key = getNotificationKey(item)
      return readKeys.has(key) ? count : count + 1
    }, 0)
  }, [items, readKeys])

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

  function persistReadKeys(next) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(Array.from(next)))
    } catch {
      // ignore storage failures
    }
  }

  function getNotificationKey(item) {
    if (item?.id) return `id:${item.id}`
    const createdAt = item?.createdAt ?? ''
    const type = item?.type ?? ''
    const message = item?.message ?? ''
    return `fallback:${type}:${createdAt}:${message}`
  }

  function toggleRead(item) {
    const key = getNotificationKey(item)
    setReadKeys(prev => {
      const next = new Set(prev)
      if (next.has(key)) {
        next.delete(key)
      } else {
        next.add(key)
      }
      persistReadKeys(next)
      return next
    })
  }

  function markAllRead() {
    const next = new Set(readKeys)
    items.forEach(item => next.add(getNotificationKey(item)))
    setReadKeys(next)
    persistReadKeys(next)
  }

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
      <PageHeader
        title="Powiadomienia"
        subtitle="Lista ostatnich powiadomień systemowych"
        actions={(isAdmin || items.length > 0) ? (
          <div style={{ display: 'flex', gap: 'var(--space-2)', flexWrap: 'wrap' }}>
            {items.length > 0 && (
              <button className="btn btn-outline" onClick={markAllRead}>
                Oznacz wszystkie jako przeczytane
              </button>
            )}
            {isAdmin && (
              <button className="btn btn-primary" onClick={sendTest}>Wyslij test</button>
            )}
          </div>
        ) : null}
      />

      <StatGrid>
        <StatCard title="Wszystkie powiadomienia" value={items.length} subtitle="Łącznie" />
        <StatCard title="Nieprzeczytane" value={unreadCount} subtitle="Wymaga uwagi" />
        <StatCard title="Ostatnia aktualizacja" value={latestDate ? latestDate.toLocaleDateString('pl-PL') : '-'} subtitle="Najnowsza wiadomość" />
        <StatCard title="Status" value={error ? 'Błąd' : 'OK'} subtitle="Połączenie z usługą" />
      </StatGrid>

      {loading && (
        <SectionCard>Ładowanie...</SectionCard>
      )}

      {error && <FeedbackCard variant="error">{error}</FeedbackCard>}
      {info && <FeedbackCard variant="success">{info}</FeedbackCard>}

      {!loading && !error && (
        <SectionCard>
          {items.length === 0 ? (
            <p>Brak powiadomień.</p>
          ) : (
            <ul className="list list--bordered">
              {items.map((n) => (
                <li key={n.id || `${n.type}-${n.createdAt}`}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', gap: 'var(--space-3)', alignItems: 'flex-start', flexWrap: 'wrap' }}>
                    <div style={{ minWidth: '16rem', flex: '1 1 18rem' }}>
                      <div className="list__title">{n.title || n.subject || 'Powiadomienie'}</div>
                      {n.message && <div className="support-copy">{n.message}</div>}
                    </div>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: 'var(--space-2)', alignItems: 'flex-end', flex: '0 1 16rem' }}>
                      <div className="list__meta" style={{ display: 'flex', gap: 'var(--space-2)', alignItems: 'center', flexWrap: 'wrap', justifyContent: 'flex-end' }}>
                        <span className={`status-pill ${readKeys.has(getNotificationKey(n)) ? '' : 'is-warning'}`}>
                          {readKeys.has(getNotificationKey(n)) ? 'Przeczytane' : 'Nieprzeczytane'}
                        </span>
                        <span>{n.type || 'info'}</span>
                        <span>{n.createdAt ? new Date(n.createdAt).toLocaleString('pl-PL') : '-'}</span>
                      </div>
                      <button type="button" className="btn btn-ghost" onClick={() => toggleRead(n)}>
                        {readKeys.has(getNotificationKey(n)) ? 'Oznacz jako nieprzeczytane' : 'Oznacz jako przeczytane'}
                      </button>
                    </div>
                  </div>
                </li>
              ))}
            </ul>
          )}
        </SectionCard>
      )}
    </div>
  )
}
