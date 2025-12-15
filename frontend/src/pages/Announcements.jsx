import React, { useEffect, useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { apiFetch } from '../api'
import { FaBullhorn, FaPlus } from 'react-icons/fa'
import { useAuth } from '../context/AuthContext'
import { announcementService } from '../services/announcementService'

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
  }, [id, currentPage])

  if (loading) {
    return (
      <div className="page">
        <div className="surface-card">
          <p>Ładowanie ogłoszeń...</p>
        </div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="page">
        <div className="surface-card">
          <p className="error">{error}</p>
          <button className="btn btn-primary" onClick={() => window.location.reload()}>
            Spróbuj ponownie
          </button>
        </div>
      </div>
    )
  }

  if (selectedAnnouncement) {
    return (
      <div className="page">
        <button onClick={() => navigate('/announcements')} className="btn btn-outline">
          ⇠ Powrót do listy
        </button>

        <div className="surface-card" style={{ marginTop: '1rem' }}>
          <h1>{selectedAnnouncement.title}</h1>
          <p style={{ color: 'var(--color-muted)', marginTop: '0.5rem' }}>
            {new Date(selectedAnnouncement.createdAt).toLocaleDateString('pl-PL')}
          </p>
          {actionMessage && <p className="success" style={{ marginTop: '0.5rem' }}>{actionMessage}</p>}
          {actionError && <p className="error" style={{ marginTop: '0.5rem' }}>{actionError}</p>}
          <div style={{ marginTop: '2rem' }}>
            {selectedAnnouncement.content}
          </div>
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
        </div>
      </div>
    )
  }

  return (
    <div className="page">
      <header className="page-header">
        <div>
          <h1>Ogłoszenia</h1>
          <p>Aktualne informacje i komunikaty</p>
        </div>
        {canManage && (
          <button className="btn btn-primary" onClick={() => navigate('/admin/announcements/new')}>
            <FaPlus /> Nowe ogłoszenie
          </button>
        )}
      </header>

      {announcements.length === 0 ? (
        <div className="surface-card">
          <div className="empty-state">
            <FaBullhorn style={{ fontSize: '3rem', color: 'var(--color-muted)' }} />
            <h3>Brak ogłoszeń</h3>
            <p>Nie ma obecnie żadnych ogłoszeń do wyświetlenia.</p>
          </div>
        </div>
      ) : (
        <div style={{ display: 'grid', gap: '1rem' }}>
          {announcements.map(announcement => (
            <div
              key={announcement.id}
              className="surface-card"
              onClick={() => navigate(`/announcements/${announcement.id}`)}
              style={{ cursor: 'pointer', transition: 'all 0.2s' }}
            >
              <h3>{announcement.title}</h3>
              <p style={{ color: 'var(--color-muted)', fontSize: '0.9rem', marginTop: '0.5rem' }}>
                {new Date(announcement.createdAt).toLocaleDateString('pl-PL')}
              </p>
              {announcement.content && (
                <p style={{ marginTop: '1rem' }}>
                  {announcement.content.length > 200
                    ? `${announcement.content.substring(0, 200)}...`
                    : announcement.content}
                </p>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
