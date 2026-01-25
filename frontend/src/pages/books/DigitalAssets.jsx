import React, { useEffect, useState } from 'react'
import { digitalAssetService } from '../services/digitalAssetService'
import { useAuth } from '../context/AuthContext'
import PageHeader from '../components/ui/PageHeader'
import StatGrid from '../components/ui/StatGrid'
import StatCard from '../components/ui/StatCard'
import SectionCard from '../components/ui/SectionCard'
import FeedbackCard from '../components/ui/FeedbackCard'

export default function DigitalAssets() {
  const { user } = useAuth()
  const isLibrarian = user?.roles?.includes('ROLE_LIBRARIAN') || user?.roles?.includes('ROLE_ADMIN')
  const [bookId, setBookId] = useState('')
  const [assets, setAssets] = useState([])
  const [file, setFile] = useState(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)
  const [message, setMessage] = useState(null)

  useEffect(() => {
    if (bookId) {
      loadAssets(bookId)
    } else {
      setAssets([])
    }
  }, [bookId])

  async function loadAssets(id) {
    setLoading(true)
    setError(null)
    setMessage(null)
    try {
      const data = await digitalAssetService.list(id)
      const list = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : []
      setAssets(list)
    } catch (err) {
      setError(err.message || 'Nie udało się pobrać zasobów cyfrowych')
    } finally {
      setLoading(false)
    }
  }

  async function handleUpload(e) {
    e.preventDefault()
    if (!file || !bookId) {
      setError('Wybierz plik i podaj ID książki')
      return
    }
    setLoading(true)
    setError(null)
    setMessage(null)
    try {
      await digitalAssetService.upload(bookId, file)
      setMessage('Plik został przesłany')
      setFile(null)
      await loadAssets(bookId)
    } catch (err) {
      setError(err.message || 'Nie udało się przesłać pliku')
    } finally {
      setLoading(false)
    }
  }

  async function handleDelete(assetId) {
    setError(null)
    setMessage(null)
    try {
      await digitalAssetService.remove(bookId, assetId)
      setMessage('Usunięto plik')
      setAssets(prev => prev.filter(a => a.id !== assetId))
    } catch (err) {
      setError(err.message || 'Nie udało się usunąć pliku')
    }
  }

  if (!isLibrarian) {
    return (
      <div className="page">
        <SectionCard>Brak uprawnień do zarządzania zasobami cyfrowymi.</SectionCard>
      </div>
    )
  }

  return (
    <div className="page">
      <PageHeader title="Zasoby cyfrowe" subtitle="Dodaj i zarządzaj plikami powiązanymi z książkami" />

      <StatGrid>
        <StatCard title="Pliki" value={assets.length} subtitle="Powiązane z książką" />
        <StatCard title="ID książki" value={bookId || '-'} subtitle="Aktualny kontekst" />
        <StatCard title="Status" value={loading ? 'Ładuję' : 'Gotowe'} subtitle="Operacje plików" />
      </StatGrid>

      <SectionCard>
        <form className="form-row" onSubmit={handleUpload}>
          <div className="form-field">
            <label>ID książki</label>
            <input type="number" value={bookId} onChange={e => setBookId(e.target.value)} />
          </div>
          <div className="form-field">
            <label>Plik</label>
            <input type="file" onChange={e => setFile(e.target.files?.[0] || null)} />
          </div>
          <div className="form-actions">
            <button type="submit" className="btn btn-primary" disabled={loading}>Prześlij</button>
          </div>
        </form>
      </SectionCard>

      {error && <FeedbackCard variant="error">{error}</FeedbackCard>}
      {message && <FeedbackCard variant="success">{message}</FeedbackCard>}

      <SectionCard title="Pliki">
        {loading && <p>Ładowanie...</p>}
        {!loading && assets.length === 0 && <p>Brak plików.</p>}
        {!loading && assets.length > 0 && (
          <ul className="list list--bordered">
            {assets.map(asset => (
              <li key={asset.id || asset.assetId}>
                <div className="list__title">{asset.filename || asset.name || 'Plik'}</div>
                <div className="list__meta">
                  {asset.size && <span>{asset.size} B</span>}
                  {asset.createdAt && <span>{new Date(asset.createdAt).toLocaleString('pl-PL')}</span>}
                </div>
                <div style={{ display: 'flex', gap: '0.5rem' }}>
                  <a className="btn btn-outline btn-sm" href={digitalAssetService.downloadUrl(bookId, asset.id || asset.assetId)} target="_blank" rel="noopener">
                    Pobierz
                  </a>
                  <button className="btn btn-ghost btn-sm" type="button" onClick={() => handleDelete(asset.id || asset.assetId)}>
                    Usuń
                  </button>
                </div>
              </li>
            ))}
          </ul>
        )}
      </SectionCard>
    </div>
  )
}
