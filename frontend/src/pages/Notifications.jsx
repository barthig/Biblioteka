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
  const latestDate = useMemo(() => {
    const dates = items
      .map(item => item.createdAt)
      .filter(Boolean)
      .map(value => new Date(value))
      .sort((a, b) => b.getTime() - a.getTime())
    return dates[0] ?? null
  }, [items])

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
      <PageHeader
        title="Powiadomienia"
        subtitle="Lista ostatnich powiadomień systemowych"
        actions={isAdmin ? <button className="btn btn-primary" onClick={sendTest}>Wyślij test</button> : null}
      />

      <StatGrid>
        <StatCard title="Wszystkie powiadomienia" value={items.length} subtitle="Łącznie" />
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
        </SectionCard>
      )}
    </div>
  )
}
