import React, { useEffect, useState } from 'react'

export default function Dashboard() {
  const [stats, setStats] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
    let mounted = true
    async function load() {
      setLoading(true)
      try {
        const res = await fetch('/api/dashboard')
        if (!res.ok) throw new Error(`${res.status}`)
        const data = await res.json()
        if (mounted) setStats(data)
      } catch (err) {
        if (mounted) setError(err.message)
      } finally {
        if (mounted) setLoading(false)
      }
    }
    load()
    return () => (mounted = false)
  }, [])

  if (loading) return <div>Loading dashboard...</div>
  if (error) return <div className="error">Error: {error}</div>

  return (
    <div>
      <h2>Dashboard</h2>
      {stats ? (
        <ul>
          <li>Books: {stats.booksCount ?? '—'}</li>
          <li>Users: {stats.usersCount ?? '—'}</li>
          <li>Loans: {stats.loansCount ?? '—'}</li>
        </ul>
      ) : (
        <div>No stats available</div>
      )}
    </div>
  )
}
