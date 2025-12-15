import React, { useState } from 'react'
import { catalogService } from '../services/catalogService'
import { useAuth } from '../context/AuthContext'

export default function CatalogAdmin() {
  const { user } = useAuth()
  const isAdmin = user?.roles?.includes('ROLE_ADMIN')
  const [file, setFile] = useState(null)
  const [message, setMessage] = useState(null)
  const [error, setError] = useState(null)
  const [loading, setLoading] = useState(false)

  if (!isAdmin) {
    return (
      <div className="page">
        <div className="surface-card">Brak uprawnień do importu/eksportu katalogu.</div>
      </div>
    )
  }

  async function handleImport(e) {
    e.preventDefault()
    if (!file) {
      setError('Wybierz plik do importu')
      return
    }
    setLoading(true)
    setError(null)
    setMessage(null)
    try {
      await catalogService.importCatalog(file)
      setMessage('Import zakończony.')
      setFile(null)
    } catch (err) {
      setError(err.message || 'Import nie powiódł się')
    } finally {
      setLoading(false)
    }
  }

  async function handleExport() {
    setLoading(true)
    setError(null)
    setMessage(null)
    try {
      await catalogService.exportCatalog()
      setMessage('Rozpoczęto eksport katalogu.')
      // real download will be handled by browser if backend sends file
    } catch (err) {
      setError(err.message || 'Eksport nie powiódł się')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="page">
      <header className="page-header">
        <div>
          <h1>Katalog - import/eksport</h1>
          <p>Zarządzanie pełnym zbiorem danych katalogowych</p>
        </div>
        <button className="btn btn-outline" onClick={handleExport} disabled={loading}>Eksportuj</button>
      </header>

      <div className="surface-card">
        <form className="form-row" onSubmit={handleImport}>
          <div className="form-field">
            <label>Plik katalogu (CSV/JSON)</label>
            <input type="file" onChange={e => setFile(e.target.files?.[0] || null)} />
          </div>
          <div className="form-actions">
            <button type="submit" className="btn btn-primary" disabled={loading}>Importuj</button>
          </div>
        </form>
        {message && <p className="success">{message}</p>}
        {error && <p className="error">{error}</p>}
      </div>
    </div>
  )
}
