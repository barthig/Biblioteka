import React, { useEffect, useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { announcementService } from '../services/announcementService'
import AnnouncementCard from '../components/AnnouncementCard'
import LoadingSpinner from '../components/LoadingSpinner'
import ErrorMessage from '../components/ErrorMessage'
import EmptyState from '../components/EmptyState'
import Pagination from '../components/Pagination'
import { FaBullhorn, FaPlus } from 'react-icons/fa'
import { useAuth } from '../context/AuthContext'

export default function Announcements() {
  const { id } = useParams()
  const navigate = useNavigate()
  const { user } = useAuth()
  const [announcements, setAnnouncements] = useState([])
  const [selectedAnnouncement, setSelectedAnnouncement] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [currentPage, setCurrentPage] = useState(1)
  const [totalPages, setTotalPages] = useState(1)
  const [filters, setFilters] = useState({
    type: '',
    showArchived: false
  })

  const isAdmin = user?.roles?.includes('ROLE_ADMIN')
  const isLibrarian = user?.roles?.includes('ROLE_LIBRARIAN')
  const canManage = isAdmin || isLibrarian

  useEffect(() => {
    if (id) {
      loadSingleAnnouncement(id)
    } else {
      loadAnnouncements()
    }
  }, [id, currentPage, filters])

  async function loadAnnouncements() {
    setLoading(true)
    setError(null)

    try {
      const data = await announcementService.getAnnouncements({
        page: currentPage,
        limit: 10,
        type: filters.type || undefined,
        includeArchived: filters.showArchived
      })

      setAnnouncements(data.items || data || [])
      setTotalPages(data.totalPages || 1)
    } catch (err) {
      setError(err.message || 'Nie udało się załadować ogłoszeń')
    } finally {
      setLoading(false)
    }
  }

  async function loadSingleAnnouncement(announcementId) {
    setLoading(true)
    setError(null)

    try {
      const data = await announcementService.getAnnouncement(announcementId)
      setSelectedAnnouncement(data)
    } catch (err) {
      setError(err.message || 'Nie udało się załadować ogłoszenia')
    } finally {
      setLoading(false)
    }
  }

  function handleAnnouncementClick(announcement) {
    navigate(`/announcements/${announcement.id}`)
  }

  function handleBack() {
    navigate('/announcements')
    setSelectedAnnouncement(null)
  }

  if (loading) {
    return <LoadingSpinner message="Ładowanie ogłoszeń..." />
  }

  if (selectedAnnouncement) {
    return (
      <div className="announcement-detail">
        <button onClick={handleBack} className="btn btn-secondary mb-3">
          ← Powrót do listy
        </button>

        {error && <ErrorMessage error={error} onDismiss={() => setError(null)} />}

        <div className="card">
          <div className="announcement-type-badge announcement-type-badge-large" data-type={selectedAnnouncement.type}>
            {selectedAnnouncement.type === 'info' && '📢'}
            {selectedAnnouncement.type === 'warning' && '⚠️'}
            {selectedAnnouncement.type === 'success' && '✅'}
            {selectedAnnouncement.type === 'error' && '❌'}
            {' '}
            {selectedAnnouncement.type?.toUpperCase() || 'INFO'}
          </div>

          {selectedAnnouncement.isPinned && (
            <div className="announcement-pinned">📌 Przypięte</div>
          )}

          <h1 className="announcement-detail-title">{selectedAnnouncement.title}</h1>

          <div className="announcement-meta">
            <span>Autor: {selectedAnnouncement.author?.name || 'System'}</span>
            <span>•</span>
            <span>{new Date(selectedAnnouncement.publishedAt || selectedAnnouncement.createdAt).toLocaleDateString('pl-PL')}</span>
          </div>

          <div className="announcement-detail-content">
            {selectedAnnouncement.content}
          </div>

          {canManage && (
            <div className="announcement-actions mt-3">
              <button className="btn btn-primary" onClick={() => navigate(`/admin/announcements/${selectedAnnouncement.id}/edit`)}>
                Edytuj
              </button>
              {selectedAnnouncement.status === 'draft' && (
                <button className="btn btn-success" onClick={() => handlePublish(selectedAnnouncement.id)}>
                  Opublikuj
                </button>
              )}
              {selectedAnnouncement.status === 'published' && (
                <button className="btn btn-warning" onClick={() => handleArchive(selectedAnnouncement.id)}>
                  Archiwizuj
                </button>
              )}
            </div>
          )}
        </div>
      </div>
    )
  }

  return (
    <div className="announcements-page">
      <div className="page-header">
        <h1><FaBullhorn /> Ogłoszenia</h1>
        {canManage && (
          <button className="btn btn-primary" onClick={() => navigate('/admin/announcements/new')}>
            <FaPlus /> Nowe ogłoszenie
          </button>
        )}
      </div>

      {error && <ErrorMessage error={error} onDismiss={() => setError(null)} />}

      <div className="filters-bar">
        <select
          value={filters.type}
          onChange={(e) => setFilters({ ...filters, type: e.target.value })}
          className="filter-select"
        >
          <option value="">Wszystkie typy</option>
          <option value="info">Informacja</option>
          <option value="warning">Ostrzeżenie</option>
          <option value="success">Sukces</option>
          <option value="error">Błąd</option>
        </select>

        {canManage && (
          <label className="filter-label">
            <input
              type="checkbox"
              checked={filters.showArchived}
              onChange={(e) => setFilters({ ...filters, showArchived: e.target.checked })}
            />
            {' '}Pokaż zarchiwizowane
          </label>
        )}
      </div>

      {announcements.length === 0 ? (
        <EmptyState
          icon={FaBullhorn}
          title="Brak ogłoszeń"
          message="Nie ma obecnie żadnych ogłoszeń do wyświetlenia."
          action={canManage ? (
            <button className="btn btn-primary" onClick={() => navigate('/admin/announcements/new')}>
              <FaPlus /> Dodaj pierwsze ogłoszenie
            </button>
          ) : null}
        />
      ) : (
        <>
          <div className="announcements-grid">
            {announcements.map(announcement => (
              <AnnouncementCard
                key={announcement.id}
                announcement={announcement}
                onClick={() => handleAnnouncementClick(announcement)}
              />
            ))}
          </div>

          {totalPages > 1 && (
            <Pagination
              currentPage={currentPage}
              totalPages={totalPages}
              onPageChange={setCurrentPage}
            />
          )}
        </>
      )}
    </div>
  )

  async function handlePublish(announcementId) {
    try {
      await announcementService.publishAnnouncement(announcementId)
      loadAnnouncements()
    } catch (err) {
      setError(err.message || 'Nie udało się opublikować ogłoszenia')
    }
  }

  async function handleArchive(announcementId) {
    try {
      await announcementService.archiveAnnouncement(announcementId)
      loadAnnouncements()
    } catch (err) {
      setError(err.message || 'Nie udało się zarchiwizować ogłoszenia')
    }
  }
}
