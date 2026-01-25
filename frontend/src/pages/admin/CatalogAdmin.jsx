import React, { useEffect, useState } from 'react'
import { catalogService } from '../services/catalogService'
import { apiFetch } from '../api'
import { useAuth } from '../context/AuthContext'
import PageHeader from '../components/ui/PageHeader'
import StatGrid from '../components/ui/StatGrid'
import StatCard from '../components/ui/StatCard'
import FeedbackCard from '../components/ui/FeedbackCard'

const emptyState = {
  authors: [],
  categories: [],
  staffRoles: [],
  systemSettings: [],
  integrationConfigs: []
}

export default function CatalogAdmin() {
  const { user } = useAuth()
  const isAdmin = user?.roles?.includes('ROLE_ADMIN')
  const [activeTab, setActiveTab] = useState('metadata')
  const [file, setFile] = useState(null)
  const [message, setMessage] = useState(null)
  const [error, setError] = useState(null)
  const [loading, setLoading] = useState(false)
  const [data, setData] = useState(emptyState)

  const [authorForm, setAuthorForm] = useState({ name: '' })
  const [categoryForm, setCategoryForm] = useState({ name: '' })
  const [staffRoleForm, setStaffRoleForm] = useState({ name: '', roleKlucz: '', modules: '', description: '' })
  const [systemSettingForm, setSystemSettingForm] = useState({ key: '', value: '', valueType: 'string', description: '' })
  const [integrationForm, setIntegrationForm] = useState({ name: '', provider: '', enabled: true, settings: '{}' })

  useEffect(() => {
    if (isAdmin) {
      loadAllMetadata()
    }
  }, [isAdmin])

  useEffect(() => {
    if (isAdmin && activeTab === 'metadata') {
      loadAllMetadata()
    }
  }, [isAdmin, activeTab])

  if (!isAdmin) {
    return (
      <div className="page">
        <div className="surface-card">Brak uprawnień do zarządzania katalogiem.</div>
      </div>
    )
  }

  function clearMessages() {
    setError(null)
    setMessage(null)
  }

  async function loadAllMetadata() {
    setLoading(true)
    clearMessages()
    try {
      const [authors, categories, staffRoles, systemSettings, integrationConfigs] = await Promise.all([
        apiFetch('/api/authors'),
        apiFetch('/api/categories'),
        apiFetch('/api/staff-roles'),
        apiFetch('/api/system-settings'),
        apiFetch('/api/integration-configs')
      ])
      setData({
        authors: Array.isArray(authors) ? authors : authors?.data || [],
        categories: Array.isArray(categories) ? categories : categories?.data || [],
        staffRoles: Array.isArray(staffRoles) ? staffRoles : staffRoles?.data || [],
        systemSettings: Array.isArray(systemSettings) ? systemSettings : systemSettings?.data || [],
        integrationConfigs: Array.isArray(integrationConfigs) ? integrationConfigs : integrationConfigs?.data || []
      })
    } catch (err) {
      setError(err.message || 'Nie udało się pobrać metadanych katalogu')
    } finally {
      setLoading(false)
    }
  }

  async function handleImport(e) {
    e.preventDefault()
    if (!file) {
      setError('Wybierz plik do importu')
      return
    }
    setLoading(true)
    clearMessages()
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
    clearMessages()
    try {
      await catalogService.exportCatalog()
      setMessage('Rozpoczęto eksport katalogu.')
    } catch (err) {
      setError(err.message || 'Eksport nie powiódł się')
    } finally {
      setLoading(false)
    }
  }

  async function createAuthor(e) {
    e.preventDefault()
    clearMessages()
    try {
      await apiFetch('/api/authors', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: authorForm.name })
      })
      setAuthorForm({ name: '' })
      setMessage('Dodano autora')
      loadAllMetadata()
    } catch (err) {
      setError(err.message || 'Nie udało się dodać autora')
    }
  }

  async function updateAuthor(id, currentName) {
    const name = prompt('Nowa nazwa autora', currentName || '')
    if (!name) return
    clearMessages()
    try {
      await apiFetch(`/api/authors/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name })
      })
      setMessage('Zaktualizowano autora')
      loadAllMetadata()
    } catch (err) {
      setError(err.message || 'Nie udało się zaktualizować autora')
    }
  }

  async function deleteAuthor(id) {
    if (!confirm('Usunąć autora?')) return
    clearMessages()
    try {
      await apiFetch(`/api/authors/${id}`, { method: 'DELETE' })
      setMessage('Usunięto autora')
      loadAllMetadata()
    } catch (err) {
      setError(err.message || 'Nie udało się usunąć autora')
    }
  }

  async function showAuthor(id) {
    clearMessages()
    try {
      const data = await apiFetch(`/api/authors/${id}`)
      setMessage(`Autor: ${data?.name || data?.id || id}`)
    } catch (err) {
      setError(err.message || 'Nie udało się pobrać autora')
    }
  }

  async function createCategory(e) {
    e.preventDefault()
    clearMessages()
    try {
      await apiFetch('/api/categories', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: categoryForm.name })
      })
      setCategoryForm({ name: '' })
      setMessage('Dodano kategorię')
      loadAllMetadata()
    } catch (err) {
      setError(err.message || 'Nie udało się dodać kategorii')
    }
  }

  async function updateCategory(id, currentName) {
    const name = prompt('Nowa nazwa kategorii', currentName || '')
    if (!name) return
    clearMessages()
    try {
      await apiFetch(`/api/categories/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name })
      })
      setMessage('Zaktualizowano kategorie')
      loadAllMetadata()
    } catch (err) {
      setError(err.message || 'Nie udało się zaktualizować kategorii')
    }
  }

  async function deleteCategory(id) {
    if (!confirm('Usunąć kategorię?')) return
    clearMessages()
    try {
      await apiFetch(`/api/categories/${id}`, { method: 'DELETE' })
      setMessage('Usunięto kategorię')
      loadAllMetadata()
    } catch (err) {
      setError(err.message || 'Nie udało się usunąć kategorii')
    }
  }

  async function showCategory(id) {
    clearMessages()
    try {
      const data = await apiFetch(`/api/categories/${id}`)
      setMessage(`Kategoria: ${data?.name || data?.id || id}`)
    } catch (err) {
      setError(err.message || 'Nie udało się pobrać kategorii')
    }
  }

  async function createStaffRole(e) {
    e.preventDefault()
    clearMessages()
    try {
      await apiFetch('/api/staff-roles', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name: staffRoleForm.name,
          roleKlucz: staffRoleForm.roleKlucz,
          modules: staffRoleForm.modules.split(',').map(item => item.trim()).filter(Boolean),
          description: staffRoleForm.description || null
        })
      })
      setStaffRoleForm({ name: '', roleKlucz: '', modules: '', description: '' })
      setMessage('Dodano rolę')
      loadAllMetadata()
    } catch (err) {
      setError(err.message || 'Nie udało się dodać roli')
    }
  }

  async function updateStaffRole(id, current) {
    const name = prompt('Nowa nazwa roli', current?.name || '')
    if (!name) return
    const modules = prompt('Moduły (oddzielone przecinkami)', Array.isArray(current?.modules) ? current.modules.join(', ') : '')
    if (modules === null) return
    const description = prompt('Opis', current?.description || '')
    if (description === null) return
    clearMessages()
    try {
      await apiFetch(`/api/staff-roles/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name,
          modules: modules.split(',').map(item => item.trim()).filter(Boolean),
          description
        })
      })
      setMessage('Zaktualizowano rolę')
      loadAllMetadata()
    } catch (err) {
      setError(err.message || 'Nie udało się zaktualizować roli')
    }
  }

  async function deleteStaffRole(id) {
    if (!confirm('Usunąć rolę?')) return
    clearMessages()
    try {
      await apiFetch(`/api/staff-roles/${id}`, { method: 'DELETE' })
      setMessage('Usunięto rolę')
      loadAllMetadata()
    } catch (err) {
      setError(err.message || 'Nie udało się usunąć roli')
    }
  }

  async function showStaffRole(id) {
    clearMessages()
    try {
      const data = await apiFetch(`/api/staff-roles/${id}`)
      setMessage(`Rola: ${data?.name || data?.id || id}`)
    } catch (err) {
      setError(err.message || 'Nie udało się pobrać roli')
    }
  }

  async function createSystemSetting(e) {
    e.preventDefault()
    clearMessages()
    try {
      await apiFetch('/api/system-settings', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(systemSettingForm)
      })
      setSystemSettingForm({ key: '', value: '', valueType: 'string', description: '' })
      setMessage('Dodano ustawienie systemowe')
      loadAllMetadata()
    } catch (err) {
      setError(err.message || 'Nie udało się dodać ustawienia')
    }
  }

  async function updateSystemSetting(id, current) {
    const value = prompt('Nowa warto??', current?.value ?? '')
    if (value === null) return
    const description = prompt('Opis', current?.description || '')
    if (description === null) return
    clearMessages()
    try {
      await apiFetch(`/api/system-settings/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ value, description })
      })
      setMessage('Zaktualizowano ustawienie')
      loadAllMetadata()
    } catch (err) {
      setError(err.message || 'Nie udało się zaktualizować ustawienia')
    }
  }

  async function deleteSystemSetting(id) {
    if (!confirm('Usunąć ustawienie?')) return
    clearMessages()
    try {
      await apiFetch(`/api/system-settings/${id}`, { method: 'DELETE' })
      setMessage('Usunięto ustawienie')
      loadAllMetadata()
    } catch (err) {
      setError(err.message || 'Nie udało się usunąć ustawienia')
    }
  }

  async function showSystemSetting(id) {
    clearMessages()
    try {
      const data = await apiFetch(`/api/system-settings/${id}`)
      setMessage(`Ustawienie: ${data?.key || data?.id || id}`)
    } catch (err) {
      setError(err.message || 'Nie udało się pobrać ustawienia')
    }
  }

  async function createIntegrationConfig(e) {
    e.preventDefault()
    clearMessages()
    let settings
    try {
      settings = integrationForm.settings ? JSON.parse(integrationForm.settings) : {}
    } catch (err) {
      setError('Nieprawidłowy JSON ustawień')
      return
    }
    try {
      await apiFetch('/api/integration-configs', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name: integrationForm.name,
          provider: integrationForm.provider,
          enabled: integrationForm.enabled,
          settings
        })
      })
      setIntegrationForm({ name: '', provider: '', enabled: true, settings: '{}' })
      setMessage('Dodano konfigurację integracji')
      loadAllMetadata()
    } catch (err) {
      setError(err.message || 'Nie udało się dodać konfiguracji')
    }
  }

  async function updateIntegrationConfig(id, current) {
    const name = prompt('Nazwa', current?.name || '')
    if (name === null) return
    const settingsValue = prompt('Ustawienia (JSON)', JSON.stringify(current?.settings || {}))
    if (settingsValue === null) return
    let settings
    try {
      settings = settingsValue ? JSON.parse(settingsValue) : {}
    } catch (err) {
      setError('Nieprawidłowy JSON ustawień')
      return
    }
    clearMessages()
    try {
      await apiFetch(`/api/integration-configs/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name,
          enabled: current?.enabled ?? true,
          settings
        })
      })
      setMessage('Zaktualizowano konfigurację')
      loadAllMetadata()
    } catch (err) {
      setError(err.message || 'Nie udało się zaktualizować konfiguracji')
    }
  }

  async function deleteIntegrationConfig(id) {
    if (!confirm('Usunąć konfigurację?')) return
    clearMessages()
    try {
      await apiFetch(`/api/integration-configs/${id}`, { method: 'DELETE' })
      setMessage('Usunięto konfigurację')
      loadAllMetadata()
    } catch (err) {
      setError(err.message || 'Nie udało się usunąć konfiguracji')
    }
  }

  async function showIntegrationConfig(id) {
    clearMessages()
    try {
      const data = await apiFetch(`/api/integration-configs/${id}`)
      setMessage(`Integracja: ${data?.name || data?.id || id}`)
    } catch (err) {
      setError(err.message || 'Nie udało się pobrać konfiguracji')
    }
  }

  return (
    <div className="page">
      <PageHeader
        title="Katalog - administracja"
        subtitle="Zarządzanie importem i metadanymi katalogu"
        actions={activeTab === 'import' ? (
          <button className="btn btn-outline" onClick={handleExport} disabled={loading}>Eksportuj</button>
        ) : null}
      />

      <StatGrid>
        <StatCard title="Autorzy" value={data.authors.length} subtitle="Pozycje w systemie" />
        <StatCard title="Kategorie" value={data.categories.length} subtitle="Metadane katalogu" />
        <StatCard title="Integracje" value={data.integrationConfigs.length} subtitle="Źródła zewnętrzne" />
      </StatGrid>

      {message && <FeedbackCard variant="success">{message}</FeedbackCard>}
      {error && <FeedbackCard variant="error">{error}</FeedbackCard>}

      <div className="tabs">
        <button className={`tab ${activeTab === 'import' ? 'tab--active' : ''}`} onClick={() => setActiveTab('import')}>
          Import/Eksport
        </button>
        <button className={`tab ${activeTab === 'metadata' ? 'tab--active' : ''}`} onClick={() => setActiveTab('metadata')}>
          Metadane
        </button>
      </div>

      {activeTab === 'import' && (
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
        </div>
      )}

      {activeTab === 'metadata' && (
        <div className="grid grid-2">
          <div className="surface-card">
            <h3>Autorzy</h3>
            <form className="form-row" onSubmit={createAuthor}>
              <input placeholder="Nazwa autora" value={authorForm.name} onChange={e => setAuthorForm({ name: e.target.value })} />
              <button className="btn btn-primary" type="submit">Dodaj</button>
            </form>
            {loading && <p>Ładowanie...</p>}
            <ul className="list list--bordered">
              {data.authors.map(author => (
                <li key={author.id || author.name}>
                  <div className="list__title">{author.name || `Author ${author.id}`}</div>
                  <div className="list__actions">
                    <button className="btn btn-sm" type="button" onClick={() => showAuthor(author.id)}>Szczegóły</button>
                    <button className="btn btn-sm" type="button" onClick={() => updateAuthor(author.id, author.name)}>Edytuj</button>
                    <button className="btn btn-sm btn-danger" type="button" onClick={() => deleteAuthor(author.id)}>Usuń</button>
                  </div>
                </li>
              ))}
            </ul>
          </div>

          <div className="surface-card">
            <h3>Kategorie</h3>
            <form className="form-row" onSubmit={createCategory}>
              <input placeholder="Nazwa kategorii" value={categoryForm.name} onChange={e => setCategoryForm({ name: e.target.value })} />
              <button className="btn btn-primary" type="submit">Dodaj</button>
            </form>
            {loading && <p>Ładowanie...</p>}
            <ul className="list list--bordered">
              {data.categories.map(category => (
                <li key={category.id || category.name}>
                  <div className="list__title">{category.name || `Category ${category.id}`}</div>
                  <div className="list__actions">
                    <button className="btn btn-sm" type="button" onClick={() => showCategory(category.id)}>Szczegóły</button>
                    <button className="btn btn-sm" type="button" onClick={() => updateCategory(category.id, category.name)}>Edytuj</button>
                    <button className="btn btn-sm btn-danger" type="button" onClick={() => deleteCategory(category.id)}>Usuń</button>
                  </div>
                </li>
              ))}
            </ul>
          </div>

          <div className="surface-card">
            <h3>Role pracowników</h3>
            <form className="form" onSubmit={createStaffRole}>
              <div className="form-row form-row--two">
                <input placeholder="Nazwa" value={staffRoleForm.name} onChange={e => setStaffRoleForm(prev => ({ ...prev, name: e.target.value }))} />
                <input placeholder="Klucz roli" value={staffRoleForm.roleKlucz} onChange={e => setStaffRoleForm(prev => ({ ...prev, roleKlucz: e.target.value }))} />
              </div>
              <input placeholder="Moduły (oddzielone przecinkami)" value={staffRoleForm.modules} onChange={e => setStaffRoleForm(prev => ({ ...prev, modules: e.target.value }))} />
              <textarea placeholder="Opis" value={staffRoleForm.description} onChange={e => setStaffRoleForm(prev => ({ ...prev, description: e.target.value }))} />
              <button className="btn btn-primary" type="submit">Dodaj role</button>
            </form>
            <ul className="list list--bordered">
              {data.staffRoles.map(role => (
                <li key={role.id || role.roleKlucz}>
                  <div className="list__title">{role.name || role.roleKlucz}</div>
                  <div className="list__meta">{role.roleKlucz}</div>
                  <div className="list__actions">
                    <button className="btn btn-sm" type="button" onClick={() => showStaffRole(role.id)}>Szczegóły</button>
                    <button className="btn btn-sm" type="button" onClick={() => updateStaffRole(role.id, role)}>Edytuj</button>
                    <button className="btn btn-sm btn-danger" type="button" onClick={() => deleteStaffRole(role.id)}>Usuń</button>
                  </div>
                </li>
              ))}
            </ul>
          </div>

          <div className="surface-card">
            <h3>Ustawienia systemowe</h3>
            <form className="form" onSubmit={createSystemSetting}>
              <div className="form-row form-row--two">
                <input placeholder="Klucz" value={systemSettingForm.key} onChange={e => setSystemSettingForm(prev => ({ ...prev, key: e.target.value }))} />
                <input placeholder="Wartość" value={systemSettingForm.value} onChange={e => setSystemSettingForm(prev => ({ ...prev, value: e.target.value }))} />
              </div>
              <div className="form-row form-row--two">
                <input placeholder="Typ wartości" value={systemSettingForm.valueType} onChange={e => setSystemSettingForm(prev => ({ ...prev, valueType: e.target.value }))} />
                <input placeholder="Opis" value={systemSettingForm.description} onChange={e => setSystemSettingForm(prev => ({ ...prev, description: e.target.value }))} />
              </div>
              <button className="btn btn-primary" type="submit">Dodaj ustawienie</button>
            </form>
            <ul className="list list--bordered">
              {data.systemSettings.map(setting => (
                <li key={setting.id || setting.key}>
                  <div className="list__title">{setting.key || `Setting ${setting.id}`}</div>
                  <div className="list__meta">{String(setting.value ?? '')}</div>
                  <div className="list__actions">
                    <button className="btn btn-sm" type="button" onClick={() => showSystemSetting(setting.id)}>Szczegóły</button>
                    <button className="btn btn-sm" type="button" onClick={() => updateSystemSetting(setting.id, setting)}>Edytuj</button>
                    <button className="btn btn-sm btn-danger" type="button" onClick={() => deleteSystemSetting(setting.id)}>Usuń</button>
                  </div>
                </li>
              ))}
            </ul>
          </div>

          <div className="surface-card surface-card--wide">
            <h3>Konfiguracje integracji</h3>
            <form className="form" onSubmit={createIntegrationConfig}>
              <div className="form-row form-row--two">
                <input placeholder="Nazwa" value={integrationForm.name} onChange={e => setIntegrationForm(prev => ({ ...prev, name: e.target.value }))} />
                <input placeholder="Dostawca" value={integrationForm.provider} onChange={e => setIntegrationForm(prev => ({ ...prev, provider: e.target.value }))} />
              </div>
              <div className="form-field checkbox">
                <label>
                  <input type="checkbox" checked={integrationForm.enabled} onChange={e => setIntegrationForm(prev => ({ ...prev, enabled: e.target.checked }))} />
                  Aktywna
                </label>
              </div>
              <textarea placeholder="Ustawienia (JSON)" value={integrationForm.settings} onChange={e => setIntegrationForm(prev => ({ ...prev, settings: e.target.value }))} />
              <button className="btn btn-primary" type="submit">Dodaj konfiguracje</button>
            </form>
            <ul className="list list--bordered">
              {data.integrationConfigs.map(config => (
                <li key={config.id || config.name}>
                  <div className="list__title">{config.name || `Config ${config.id}`}</div>
                  <div className="list__meta">{config.provider || '-'}</div>
                  <div className="list__actions">
                    <button className="btn btn-sm" type="button" onClick={() => showIntegrationConfig(config.id)}>Szczegóły</button>
                    <button className="btn btn-sm" type="button" onClick={() => updateIntegrationConfig(config.id, config)}>Edytuj</button>
                    <button className="btn btn-sm btn-danger" type="button" onClick={() => deleteIntegrationConfig(config.id)}>Usuń</button>
                  </div>
                </li>
              ))}
            </ul>
          </div>
        </div>
      )}
    </div>
  )
}
