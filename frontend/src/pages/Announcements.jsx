import React, { useEffect, useState, useRef } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { apiFetch } from '../api'
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
  const loadingRef = useRef(false)

  const isAdmin = user?.roles?.includes('ROLE_ADMIN')
  const isLibrarian = user?.roles?.includes('ROLE_LIBRARIAN')
  const canManage = isAdmin || isLibrarian

  useEffect(() => {
    let mounted = true

    async function fetchData() {
      if (!mounted || loadingRef.current) return
      
      loadingRef.current = true
      setLoading(true)
      setError(null)

      try {
        if (id) {
          const data = await apiFetch(`/api/announcements/${id}`)
          if (mounted) {
            setSelectedAnnouncement(data)
          }
        } else {
          const data = await apiFetch(`/api/announcements?page=${currentPage}&limit=10`)
          if (mounted) {
            setAnnouncements(data.data || data.items || data || [])
            setTotalPages(data.meta?.totalPages || data.totalPages || 1)
          }
        }
      } catch (err) {
        console.error('Błąd ładowania ogłoszeń:', err)
        if (mounted) {
          setError(err.message || 'Nie udało się załadować ogłoszeń')
          setAnnouncements([])
        }
      } finally {
        if (mounted) {
          setLoading(false)
          loadingRef.current = false
        }
      }
    }

    fetchData()

    return () => {
      mounted = false
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
          ← Powrót do listy
        </button>

        <div className="surface-card" style={{ marginTop: '1rem' }}>
          <h1>{selectedAnnouncement.title}</h1>
          <p style={{ color: 'var(--color-muted)', marginTop: '0.5rem' }}>
            {new Date(selectedAnnouncement.createdAt).toLocaleDateString('pl-PL')}
          </p>
          <div style={{ marginTop: '2rem' }}>
            {selectedAnnouncement.content}
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
