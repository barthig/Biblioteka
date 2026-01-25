import React, { useEffect, useState } from 'react'
import { systemLogService } from '../../services/systemLogService'
import { useAuth } from '../../context/AuthContext'
import PageHeader from '../../components/ui/PageHeader'
import StatGrid from '../../components/ui/StatGrid'
import StatCard from '../../components/ui/StatCard'
import SectionCard from '../../components/ui/SectionCard'
import FeedbackCard from '../../components/ui/FeedbackCard'

export default function SystemLogs() {
  const { user } = useAuth()
  const isAdmin = user?.roles?.includes('ROLE_ADMIN')
  const [logs, setLogs] = useState('')
  const [error, setError] = useState(null)
  const [loading, setLoading] = useState(true)
  const logLines = logs ? logs.split('\n').length : 0

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
        <SectionCard>Brak uprawnień do logów systemowych.</SectionCard>
      </div>
    )
  }

  return (
    <div className="page">
      <PageHeader title="Logi systemowe" subtitle="Podgląd serwerowych logów aplikacji" />

      <StatGrid>
        <StatCard title="Wpisy" value={logLines || '-'} subtitle="Liczba linii" />
        <StatCard title="Status" value={loading ? 'Ładuję' : (error ? 'Błąd' : 'Gotowe')} subtitle="Odczyt logów" />
        <StatCard title="Zakres" value="Serwer" subtitle="Logi aplikacji" />
      </StatGrid>

      {loading && <SectionCard>Ładowanie...</SectionCard>}
      {error && <FeedbackCard variant="error">{error}</FeedbackCard>}
      {!loading && !error && (
        <SectionCard>
          <pre style={{ whiteSpace: 'pre-wrap', maxHeight: '600px', overflow: 'auto' }}>{logs || 'Brak logów'}</pre>
        </SectionCard>
      )}
    </div>
  )
}
