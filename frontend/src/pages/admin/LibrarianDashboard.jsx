import React, { useEffect, useState } from 'react'
import toast from 'react-hot-toast'
import { apiFetch } from '../../api'
import { useResourceCache } from '../../context/ResourceCacheContext'
import { StatCardSkeleton } from '../../components/ui/Skeleton'
import PageHeader from '../../components/ui/PageHeader'
import StatGrid from '../../components/ui/StatGrid'
import StatCard from '../../components/ui/StatCard'
import SectionCard from '../../components/ui/SectionCard'
import FeedbackCard from '../../components/ui/FeedbackCard'

export default function LibrarianDashboard() {
  const { getCachedResource, prefetchResource } = useResourceCache()
  const [stats, setStats] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
    loadStats()
  }, [])

  async function loadStats() {
    const cacheKey = 'librarian:/api/statistics/dashboard'
    const cached = getCachedResource(cacheKey, 120000)
    if (typeof cached !== 'undefined') {
      setStats(cached)
      setLoading(false)
      setError(null)
      return
    }

    setLoading(true)
    setError(null)
    try {
      const data = await prefetchResource(cacheKey, () => apiFetch('/api/statistics/dashboard'), 120000)
      setStats(data)
    } catch (err) {
      setError(err.message || 'Nie udało się załadować statystyk.')
      toast.error('Błąd podczas ładowania statystyk.')
    } finally {
      setLoading(false)
    }
  }

  if (loading) {
    return (
      <div className="page">
        <PageHeader title="Dashboard" subtitle="Statystyki biblioteki" />
        <div aria-live="polite" aria-busy="true" role="status">
          <StatGrid>
            <StatCardSkeleton />
            <StatCardSkeleton />
            <StatCardSkeleton />
            <StatCardSkeleton />
          </StatGrid>
        </div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="page">
        <PageHeader title="Dashboard" subtitle="Statystyki biblioteki" />
        <FeedbackCard variant="error">{error}</FeedbackCard>
      </div>
    )
  }

  if (!stats) return null

  return (
    <div className="page">
      <PageHeader
        title="Dashboard"
        subtitle="Statystyki biblioteki"
        actions={(
          <button className="btn btn-outline" onClick={loadStats} aria-label="Odśwież statystyki biblioteki">
            Odśwież
          </button>
        )}
      />

      <StatGrid>
        <StatCard title="Aktywne wypożyczenia" value={stats.activeLoans} subtitle="Obecnie wypożyczone" />
        <StatCard title="Zaległe zwroty" value={stats.overdueLoans} subtitle="Po terminie" alert={stats.overdueLoans > 0} />
        <StatCard title="Rezerwacje" value={stats.pendingReservations} subtitle="Oczekujące" />
        <StatCard title="Użytkownicy" value={stats.totalUsers} subtitle="Zarejestrowani" />
        <StatCard title="Książki" value={stats.totalBooks} subtitle="W katalogu" />
        <StatCard title="Dostępne egzemplarze" value={stats.availableCopies} subtitle="Wolne do wypożyczenia" />
      </StatGrid>

      <div className="grid grid-2" style={{ marginTop: '24px' }}>
        <SectionCard title="Popularne książki">
          {stats.popularBooks && stats.popularBooks.length > 0 ? (
            <div className="table-responsive">
              <table className="table">
                <thead>
                  <tr>
                    <th>Tytuł</th>
                    <th>Autor</th>
                    <th>Wypożyczenia</th>
                  </tr>
                </thead>
                <tbody>
                  {stats.popularBooks.map((book, index) => (
                    <tr key={book.id}>
                      <td>
                        <strong>{index + 1}.</strong> {book.title}
                      </td>
                      <td>{book.author}</td>
                      <td>{book.borrowCount}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <p>Brak danych.</p>
          )}
        </SectionCard>

        <SectionCard title="Ostatnia aktywność">
          {stats.recentActivity && stats.recentActivity.length > 0 ? (
            <div className="activity-log">
              {stats.recentActivity.map(log => (
                <div key={log.id} className="activity-item">
                  <div className="activity-icon">{activityIcon(log.action)}</div>
                  <div className="activity-content">
                    <div className="activity-text">
                      <strong>{log.user}</strong> - {log.action} ({log.entity} #{log.entityId})
                    </div>
                    <div className="activity-time">{formatTimestamp(log.timestamp)}</div>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <p>Brak aktywności.</p>
          )}
        </SectionCard>
      </div>
    </div>
  )
}

function activityIcon(action) {
  switch (action) {
    case 'create':
      return '+'
    case 'update':
      return 'ed'
    case 'delete':
      return 'x'
    case 'borrow':
      return 'B'
    case 'return':
      return 'R'
    default:
      return 'i'
  }
}

function formatTimestamp(timestamp) {
  const date = new Date(timestamp)
  const now = new Date()
  const diffMs = now - date
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMins < 1) return 'przed chwilą'
  if (diffMins < 60) return `${diffMins} min temu`
  if (diffHours < 24) return `${diffHours} h temu`
  if (diffDays < 7) return `${diffDays} dni temu`

  return date.toLocaleDateString('pl-PL')
}
