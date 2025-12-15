import React, { useEffect, useState } from 'react'
import { systemLogService } from '../services/systemLogService'
import { useAuth } from '../context/AuthContext'

export default function SystemLogs() {
  const { user } = useAuth()
  const isAdmin = user?.roles?.includes('ROLE_ADMIN')
  const [logs, setLogs] = useState('')
  const [error, setError] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    let active = true
    async function load() {
      setLoading(true)
      setError(null)
      try {
        const data = await systemLogService.list()
        if (!active) return
        if (typeof data === 'string') {
          setLogs(data)
        } else if (Array.isArray(data?.logs)) {
          setLogs(data.logs.join('\n'))
        } else {
          setLogs(JSON.stringify(data, null, 2))
        }
      } catch (err) {
        if (active) setError(err.message || 'Nie udało się pobrać logów')
      } finally {
        if (active) setLoading(false)
      }
    }
    if (isAdmin) {
      load()
    }
    return () => { active = false }
  }, [isAdmin])

  if (!isAdmin) {
    return (
      <div className="page">
        <div className="surface-card">Brak uprawnień do logów systemowych.</div>
      </div>
    )
  }

  return (
    <div className="page">
      <header className="page-header">
        <div>
          <h1>Logi systemowe</h1>
          <p>Podgląd serwerowych logów aplikacji</p>
        </div>
      </header>
      {loading && <div className="surface-card">Ładowanie...</div>}
      {error && <div className="surface-card error">{error}</div>}
      {!loading && !error && (
        <div className="surface-card">
          <pre style={{ whiteSpace: 'pre-wrap', maxHeight: '600px', overflow: 'auto' }}>{logs || 'Brak logów'}</pre>
        </div>
      )}
    </div>
  )
}
