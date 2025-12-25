import React, { useEffect, useMemo, useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { apiFetch } from '../api'
import { FaBullhorn, FaPlus } from 'react-icons/fa'
import { useAuth } from '../context/AuthContext'
import { announcementService } from '../services/announcementService'
import PageHeader from '../components/ui/PageHeader'
import SectionCard from '../components/ui/SectionCard'
import FeedbackCard from '../components/ui/FeedbackCard'

const EVENT_KEYWORDS = [
  'spotkanie',
  'warsztat',
  'warsztaty',
  'konkurs',
  'wydarzen',
  'wydarzenie',
  'klub',
  'prelekcj',
  'seminarium',
  'panel dyskusyjny',
  'czytanie'
]

function isEventAnnouncement(announcement) {
  if (announcement?.eventAt) {
    return true
  }

  const type = `${announcement?.type || ''}`.toLowerCase()
  if (['event', 'events', 'wydarzenie', 'wydarzenia'].includes(type)) {
    return true
  }

  const haystack = `${announcement?.title || ''} ${announcement?.content || ''}`.toLowerCase()
  return EVENT_KEYWORDS.some(keyword => haystack.includes(keyword))
}

function getExcerpt(text, maxLength = 160) {
  if (!text) return ''
  const trimmed = text.trim().replace(/\s+/g, ' ')
  return trimmed.length > maxLength ? `${trimmed.substring(0, maxLength)}...` : trimmed
}

function formatDateTime(value) {
  if (!value) return ''
  return new Date(value).toLocaleString('pl-PL', {
    dateStyle: 'medium',
    timeStyle: 'short'
  })
}

export default function Announcements() {
  const { id } = useParams()
  const navigate = useNavigate()
  const { user } = useAuth()
  const [announcements, setAnnouncements] = useState([])
  const [selectedAnnouncement, setSelectedAnnouncement] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [actionMessage, setActionMessage] = useState(null)
  const [actionError, setActionError] = useState(null)
  const [currentPage, setCurrentPage] = useState(1)
  const [totalPages, setTotalPages] = useState(1)
  const [refreshKey, setRefreshKey] = useState(0)
  const [form, setForm] = useState({
    title: '',
    content: '',
    location: '',
    kind: 'announcement',
    eventDate: '',
    eventTime: ''
  })
  const [formError, setFormError] = useState(null)
  const [formSuccess, setFormSuccess] = useState(null)
  const [formLoading, setFormLoading] = useState(false)

  const isAdmin = user?.roles?.includes('ROLE_ADMIN')
  const isLibrarian = user?.roles?.includes('ROLE_LIBRARIAN')
  const canManage = isAdmin || isLibrarian

  useEffect(() => {
    const abortController = new AbortController()

    async function fetchData() {
      setLoading(true)
      setError(null)
      setActionMessage(null)
      setActionError(null)

      try {
        if (id) {
          const data = await apiFetch(`/api/announcements/${id}`, { signal: abortController.signal })
          if (!abortController.signal.aborted) {
            setSelectedAnnouncement(data)
          }
        } else {
          const data = await apiFetch(`/api/announcements?page=${currentPage}&limit=10`, { signal: abortController.signal })
          const announcementsArray = data.data || data.items || data || []
          if (!abortController.signal.aborted) {
            setAnnouncements(announcementsArray)
            setTotalPages(data.meta?.totalPages || data.totalPages || 1)
          }
        }
      } catch (err) {
        console.error('Błąd ładowania ogłoszeń:', err)
        if (!abortController.signal.aborted) {
          setError(err.message || 'Nie udało się załadować ogłoszeń')
          setAnnouncements([])
        }
      } finally {
        if (!abortController.signal.aborted) {
          setLoading(false)
        }
      }
    }

    fetchData()

    return () => {
      abortController.abort()
    }
  }, [id, currentPage, refreshKey])

  const { events, notices } = useMemo(() => {
    const eventList = []
    const noticeList = []
    announcements.forEach(item => {
      if (isEventAnnouncement(item)) {
        eventList.push(item)
      } else {
        noticeList.push(item)
      }
    })
    return { events: eventList, notices: noticeList }
  }, [announcements])

  function resetForm() {
    setForm({
      title: '',
      content: '',
      location: '',
      kind: 'announcement',
      eventDate: '',
      eventTime: ''
    })
  }

  function handleFormChange(event) {
    const { name, value } = event.target
    setForm(prev => ({ ...prev, [name]: value }))
  }

  async function handleCreate(event) {
    event.preventDefault()
    setFormError(null)
    setFormSuccess(null)

    if (!form.title.trim() || !form.content.trim()) {
      setFormError('Uzupełnij tytuł i treść.')
      return
    }

    let eventAt = null
    if (form.kind === 'event') {
      if (!form.eventDate || !form.eventTime) {
        setFormError('Podaj datę i godzinę wydarzenia.')
        return
      }
      const eventDateTime = new Date(`${form.eventDate}T${form.eventTime}:00`)
      if (Number.isNaN(eventDateTime.getTime())) {
        setFormError('Nieprawidłowa data lub godzina wydarzenia.')
        return
      }
      const now = new Date()
      if (eventDateTime <= now) {
        setFormError('Data wydarzenia musi być w przyszłości.')
        return
      }
      eventAt = eventDateTime.toISOString()
    }

    const payload = {
      title: form.title.trim(),
      content: form.content.trim(),
      location: form.location.trim() || null,
      type: form.kind === 'event' ? 'event' : 'info',
      showOnHomepage: true,
      eventAt
    }

    setFormLoading(true)
    try {
      const created = await apiFetch('/api/announcements', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      if (created?.id) {
        await apiFetch(`/api/announcements/${created.id}/publish`, { method: 'POST' })
      }
      setFormSuccess('Ogloszenie zostalo dodane.')
      resetForm()
      setCurrentPage(1)
      setRefreshKey(prev => prev + 1)
    } catch (err) {
      setFormError(err.message || 'Nie udało się dodać ogłoszenia.')
    } finally {
      setFormLoading(false)
    }
  }

  if (loading) {
    return (
      <div className="page">
        <SectionCard>Ładowanie ogłoszeń...</SectionCard>
      </div>
    )
  }

  if (error) {
    return (
      <div className="page">
        <SectionCard>
          <p className="error">{error}</p>
          <button className="btn btn-primary" onClick={() => window.location.reload()}>
            Spróbuj ponownie
          </button>
        </SectionCard>
      </div>
    )
  }

  if (selectedAnnouncement) {
    return (
      <div className="page">
        <PageHeader
          title={selectedAnnouncement.title}
          subtitle={formatDateTime(selectedAnnouncement.eventAt || selectedAnnouncement.createdAt)}
          actions={(
            <button onClick={() => navigate('/announcements')} className="btn btn-outline">
              ← Powrót do listy
            </button>
          )}
        />

        {actionMessage && <FeedbackCard variant="success">{actionMessage}</FeedbackCard>}
        {actionError && <FeedbackCard variant="error">{actionError}</FeedbackCard>}

        <SectionCard>
          <div>{selectedAnnouncement.content}</div>
          <div style={{ marginTop: '1.5rem', display: 'flex', gap: '0.5rem', flexWrap: 'wrap' }}>
            {user && (
              <button
                className="btn btn-outline"
                onClick={async (e) => {
                  e.stopPropagation()
                  setActionError(null)
                  setActionMessage(null)
                  try {
                    await announcementService.acknowledgeAnnouncement(selectedAnnouncement.id)
                    setActionMessage('Potwierdzono przeczytanie ogłoszenia.')
                  } catch (err) {
                    setActionError(err.message || 'Nie udało się potwierdzić ogłoszenia.')
                  }
                }}
              >
                Potwierdź
              </button>
            )}
            {canManage && (
              <button
                className="btn btn-primary"
                onClick={async (e) => {
                  e.stopPropagation()
                  setActionError(null)
                  setActionMessage(null)
                  try {
                    await announcementService.restoreAnnouncement(selectedAnnouncement.id)
                    setActionMessage('Przywrócono ogłoszenie.')
                  } catch (err) {
                    setActionError(err.message || 'Nie udało się przywrócić ogłoszenia.')
                  }
                }}
              >
                Przywróć
              </button>
            )}
          </div>
        </SectionCard>
      </div>
    )
  }

  const renderList = (items, emptyLabel, showEventTime) => {
    if (items.length === 0) {
      return (
        <div className="empty-state">
          <FaBullhorn style={{ fontSize: '3rem', color: 'var(--color-muted)' }} />
          <h3>{emptyLabel}</h3>
          <p>Brak pozycji do wyświetlenia.</p>
        </div>
      )
    }

    return (
      <div style={{ display: 'grid', gap: '1rem' }}>
        {items.map(item => (
          <SectionCard
            key={item.id}
            onClick={() => navigate(`/announcements/${item.id}`)}
            style={{ cursor: 'pointer', transition: 'all 0.2s' }}
          >
            <h3>{item.title}</h3>
            {item.eventAt ? (
              <div style={{ marginTop: '0.5rem', display: 'flex', alignItems: 'center', gap: '0.75rem', flexWrap: 'wrap' }}>
                <span style={{ color: 'var(--color-muted)', fontSize: '0.9rem' }}>
                  <strong>Termin:</strong> {formatDateTime(item.eventAt)}
                </span>
                <span style={{ color: 'var(--color-muted)', fontSize: '0.9rem' }}>
                  <strong>Lokalizacja:</strong> {item.location || 'Brak danych'}
                </span>
              </div>
            ) : (
              <p style={{ color: 'var(--color-muted)', fontSize: '0.9rem', marginTop: '0.5rem' }}>
                <strong>Dodano:</strong> {new Date(item.createdAt).toLocaleDateString('pl-PL')}
              </p>
            )}
            {item.content && (
              <p style={{ marginTop: '1rem' }}>
                {getExcerpt(item.content)}
              </p>
            )}
          </SectionCard>
        ))}
      </div>
    )
  }

  return (
    <div className="page">
      <PageHeader
        title="Ogłoszenia"
        subtitle="Aktualne informacje, komunikaty i wydarzenia."
        actions={canManage ? (
          <button className="btn btn-primary" onClick={() => document.getElementById('announcement-form')?.scrollIntoView({ behavior: 'smooth' })}>
            <FaPlus /> Nowe ogłoszenie
          </button>
        ) : null}
      />

      {canManage && (
        <SectionCard title="Dodaj ogłoszenie lub wydarzenie" subtitle="Wydarzenia wymagają przyszłej daty i godziny.">
          <form id="announcement-form" onSubmit={handleCreate} className="form-grid">
            <div className="form-field">
              <label htmlFor="announcement-title">Tytuł</label>
              <input
                id="announcement-title"
                name="title"
                value={form.title}
                onChange={handleFormChange}
                placeholder="Wpisz tytuł"
                required
              />
            </div>
            <div className="form-field">
              <label htmlFor="announcement-location">Lokalizacja</label>
              <input
                id="announcement-location"
                name="location"
                value={form.location}
                onChange={handleFormChange}
                placeholder="np. Sala spotkan, pietro 1"
              />
            </div>
            <div className="form-field">
              <label htmlFor="announcement-kind">Typ</label>
              <select
                id="announcement-kind"
                name="kind"
                value={form.kind}
                onChange={handleFormChange}
              >
                <option value="announcement">Ogłoszenie</option>
                <option value="event">Wydarzenie</option>
              </select>
            </div>
            <div className="form-field form-field--full">
              <label htmlFor="announcement-content">Treść</label>
              <textarea
                id="announcement-content"
                name="content"
                rows={4}
                value={form.content}
                onChange={handleFormChange}
                placeholder="Wpisz treść ogłoszenia"
                required
              />
            </div>
            <div className="form-field">
              <label htmlFor="announcement-date">Data wydarzenia</label>
              <input
                id="announcement-date"
                name="eventDate"
                type="date"
                value={form.eventDate}
                onChange={handleFormChange}
                disabled={form.kind !== 'event'}
              />
            </div>
            <div className="form-field">
              <label htmlFor="announcement-time">Godzina wydarzenia</label>
              <input
                id="announcement-time"
                name="eventTime"
                type="time"
                value={form.eventTime}
                onChange={handleFormChange}
                disabled={form.kind !== 'event'}
              />
            </div>
            <div className="form-actions form-field--full">
              <button className="btn btn-primary" type="submit" disabled={formLoading}>
                {formLoading ? 'Zapisywanie...' : 'Dodaj'}
              </button>
            </div>
          </form>
          {formError && <FeedbackCard variant="error">{formError}</FeedbackCard>}
          {formSuccess && <FeedbackCard variant="success">{formSuccess}</FeedbackCard>}
        </SectionCard>
      )}

      <SectionCard title={`Wydarzenia (${events.length})`}>
        {renderList(events, 'Brak wydarzeń', true)}
      </SectionCard>

      <SectionCard title={`Ogłoszenia (${notices.length})`}>
        {renderList(notices, 'Brak ogłoszeń', false)}
      </SectionCard>
    </div>
  )
}
