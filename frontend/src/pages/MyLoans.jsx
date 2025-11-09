import React, { useEffect, useState } from 'react'
import { apiFetch } from '../api'
import { useAuth } from '../context/AuthContext'

export default function MyLoans() {
  const { token, user } = useAuth()
  const [loans, setLoans] = useState([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)

  useEffect(() => {
    if (!token) {
      setLoans([])
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
          if (err.status === 401) {
            setError('Sesja wygasła. Zaloguj się ponownie, aby zobaczyć wypożyczenia.')
          } else {
            setError(err.message || 'Nie udało się pobrać wypożyczeń')
          }
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

  if (!token || !user?.id) {
    return <div>Musisz się zalogować, aby zobaczyć swoje wypożyczenia.</div>
  }

  if (loading) return <div>Ładowanie wypożyczeń...</div>
  if (error) return <div className="error">Błąd: {error}</div>

  if (!loans.length) {
    return <div>Brak aktywnych wypożyczeń.</div>
  }

  return (
    <div>
      <h2>Moje wypożyczenia</h2>
      <ul className="loans-list">
        {loans.map(loan => (
          <li key={loan.id} className="loan-item">
            <div>
              <strong>{loan.book?.title ?? 'Nieznana książka'}</strong>
              <div className="meta">Termin zwrotu: {loan.dueAt ? new Date(loan.dueAt).toLocaleDateString() : '—'}</div>
            </div>
            {loan.returnedAt && (
              <span className="tag">Zwrócono</span>
            )}
          </li>
        ))}
      </ul>
    </div>
  )
}
