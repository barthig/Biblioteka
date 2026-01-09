import React, { useEffect, useMemo, useState } from 'react'
import { apiFetch } from '../api'
import PageHeader from '../components/ui/PageHeader'
import StatGrid from '../components/ui/StatGrid'
import StatCard from '../components/ui/StatCard'
import FeedbackCard from '../components/ui/FeedbackCard'
import UserManagement from '../components/admin/UserManagement'
import SystemSettings from '../components/admin/SystemSettings'
import RolesAndAudit from '../components/admin/RolesAndAudit'

const defaultIntegration = {
  name: '',
  provider: '',
  endpoint: '',
  apiKey: '',
  enabled: true
}
const defaultRole = { name: '', roleKey: '', modules: '', description: '' }

export default function AdminPanel() {
  const [activeTab, setActiveTab] = useState('users')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)
  const [success, setSuccess] = useState(null)
  const [libraryStats, setLibraryStats] = useState(null)
  const [libraryStatsLoading, setLibraryStatsLoading] = useState(false)

  // User management
  const [users, setUsers] = useState([])
  const [userSearchQuery, setUserSearchQuery] = useState('')
  const [editingUser, setEditingUser] = useState(null)

  // System + roles
  const [settings, setSettings] = useState([])
  const [integrations, setIntegrations] = useState([])
  const [roles, setRoles] = useState([])
  const [auditLogs, setAuditLogs] = useState([])
  const [entityAuditForm, setEntityAuditForm] = useState({ entityType: '', entityId: '' })
  const [entityAuditLogs, setEntityAuditLogs] = useState([])
  const [entityAuditLoading, setEntityAuditLoading] = useState(false)

  const [integrationForm, setIntegrationForm] = useState(defaultIntegration)
  const [roleForm, setRoleForm] = useState(defaultRole)
  const [assignForm, setAssignForm] = useState({ roleKey: '', userId: '' })

  const systemLoaded = useMemo(() => settings.length > 0 || integrations.length > 0, [settings, integrations])
  const rolesLoaded = useMemo(() => roles.length > 0, [roles])

  useEffect(() => {
    loadLibraryStats()
  }, [])

  useEffect(() => {
    if (activeTab === 'users') {
      loadUsers()
    } else if (activeTab === 'system') {
      loadSystem()
    } else if (activeTab === 'roles') {
      loadRolesAndAudit()
    }
  }, [activeTab])

  async function loadLibraryStats() {
    setLibraryStatsLoading(true)
    setError(null)
    try {
      const data = await apiFetch('/api/dashboard')
      if (data && typeof data === 'object' && !Array.isArray(data)) {
        setLibraryStats(data)
      } else {
        setLibraryStats(null)
      }
    } catch (err) {
      setLibraryStats(null)
      setError(err.message || 'Nie udało się pobrać statystyk biblioteki')
    } finally {
      setLibraryStatsLoading(false)
    }
  }

  async function loadUsers() {
    setLoading(true)
    setError(null)
    try {
      const data = await apiFetch('/api/users')
      setUsers(Array.isArray(data) ? data : [])
    } catch (err) {
      setError(err.message || 'Nie udało się pobrać użytkowników')
    } finally {
      setLoading(false)
    }
  }

  async function searchUsers(query) {
    if (!query || query.length < 2) {
      loadUsers()
      return
    }
    try {
      const data = await apiFetch(`/api/users/search?q=${encodeURIComponent(query)}`)
      setUsers(Array.isArray(data) ? data : [])
    } catch (err) {
      setError(err.message || 'Nie udało się wyszukać użytkowników')
    }
  }

  async function updateUserData(userId, updates) {
    setError(null)
    setSuccess(null)
    try {
      await apiFetch(`/api/admin/users/${userId}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(updates)
      })
      setSuccess('Dane użytkownika zostały zaktualizowane')
      setEditingUser(null)
      loadUsers()
    } catch (err) {
      setError(err.message || 'Nie udało się zaktualizować użytkownika')
    }
  }

  async function updateUserPermissions(userId, currentRoles) {
    const input = prompt('Role (oddzielone przecinkami)', Array.isArray(currentRoles) ? currentRoles.join(', ') : '')
    if (input === null) return

    const rolesList = input.split(',').map(role => role.trim()).filter(Boolean)
    if (rolesList.length === 0) {
      setError('Lista ról jest wymagana')
      return
    }

    setError(null)
    setSuccess(null)
    try {
      await apiFetch(`/api/users/${userId}/permissions`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ roles: rolesList })
      })
      setSuccess('Uprawnienia zostały zaktualizowane')
      loadUsers()
    } catch (err) {
      setError(err.message || 'Nie udało się zaktualizować uprawnień')
    }
  }

  async function toggleUserBlock(userId, currentBlocked) {
    if (!confirm(currentBlocked ? 'Odblokować tego użytkownika?' : 'Zablokować tego użytkownika?')) return
    setError(null)
    setSuccess(null)
    try {
      if (currentBlocked) {
        await apiFetch(`/api/users/${userId}/block`, { method: 'DELETE' })
      } else {
        await apiFetch(`/api/users/${userId}/block`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ reason: 'manual' })
        })
      }
      setSuccess(currentBlocked ? 'Użytkownik został odblokowany' : 'Użytkownik został zablokowany')
      loadUsers()
    } catch (err) {
      setError(err.message || 'Nie udało się zmienić statusu użytkownika')
    }
  }

  async function deleteUser(userId) {
    if (!confirm('Na pewno usunąć to konto? Tej operacji nie można cofnąć.')) return
    setError(null)
    setSuccess(null)
    try {
      await apiFetch(`/api/admin/users/${userId}`, { method: 'DELETE' })
      setSuccess('Konto użytkownika zostało usunięte')
      loadUsers()
    } catch (err) {
      setError(err.message || 'Nie udało się usunąć użytkownika')
    }
  }

  async function loadSystem() {
    setLoading(true)
    setError(null)
    try {
      const [settingsRes, integrationsRes] = await Promise.all([
        apiFetch('/api/admin/system/settings'),
        apiFetch('/api/admin/system/integrations')
      ])
      setSettings(settingsRes?.settings || (Array.isArray(settingsRes) ? settingsRes : []))
      setIntegrations(integrationsRes?.integrations || (Array.isArray(integrationsRes) ? integrationsRes : []))
    } catch (err) {
      setError(err.message || 'Nie udało się pobrać danych systemu')
    } finally {
      setLoading(false)
    }
  }

  async function loadRolesAndAudit() {
    setLoading(true)
    setError(null)
    try {
      const [rolesRes, auditRes, usersRes] = await Promise.all([
        apiFetch('/api/admin/system/roles'),
        apiFetch('/api/audit-logs?limit=25'),
        apiFetch('/api/users')
      ])
      setRoles(rolesRes?.roles || (Array.isArray(rolesRes) ? rolesRes : []))
      const entries = auditRes?.data || auditRes?.items || []
      setAuditLogs(Array.isArray(entries) ? entries : [])
      setUsers(Array.isArray(usersRes) ? usersRes : [])
    } catch (err) {
      setError(err.message || 'Nie udało się pobrać audytu lub ról')
    } finally {
      setLoading(false)
    }
  }

  async function updateSetting(key, value) {
    if (value === null || value === undefined) return
    setError(null)
    setSuccess(null)
    try {
      await apiFetch(`/api/admin/system/settings/${encodeURIComponent(key)}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ value })
      })
      setSuccess('Zapisano ustawienie systemowe')
      loadSystem()
    } catch (err) {
      setError(err.message || 'Nie udało się zaktualizować ustawienia')
    }
  }

  async function createIntegration(e) {
    e.preventDefault()
    setError(null)
    setSuccess(null)
    try {
      await apiFetch('/api/admin/system/integrations', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name: integrationForm.name,
          provider: integrationForm.provider,
          enabled: integrationForm.enabled,
          settings: {
            endpoint: integrationForm.endpoint,
            apiKey: integrationForm.apiKey || undefined
          }
        })
      })
      setSuccess('Integracja została dodana')
      setIntegrationForm(defaultIntegration)
      loadSystem()
    } catch (err) {
      setError(err.message || 'Nie udało się dodać integracji')
    }
  }

  async function toggleIntegration(id, enabled) {
    setError(null)
    setSuccess(null)
    try {
      await apiFetch(`/api/admin/system/integrations/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ enabled })
      })
      loadSystem()
    } catch (err) {
      setError(err.message || 'Nie udało się zaktualizować integracji')
    }
  }

  async function testIntegration(id) {
    setError(null)
    setSuccess(null)
    try {
      const result = await apiFetch(`/api/admin/system/integrations/${id}/test`, { method: 'POST' })
      setSuccess(result?.status ? `Test: ${result.status}` : 'Test wykonany')
      loadSystem()
    } catch (err) {
      setError(err.message || 'Nie udało się przetestować integracji')
    }
  }

  async function createRole(e) {
    e.preventDefault()
    setError(null)
    setSuccess(null)
    try {
      await apiFetch('/api/admin/system/roles', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name: roleForm.name,
          roleKey: roleForm.roleKey,
          modules: roleForm.modules ? roleForm.modules.split(',').map(m => m.trim()).filter(Boolean) : [],
          description: roleForm.description || undefined
        })
      })
      setRoleForm(defaultRole)
      setSuccess('Nowa rola została dodana')
      loadRolesAndAudit()
    } catch (err) {
      setError(err.message || 'Nie udało się utworzyć roli')
    }
  }

  async function updateRole(role) {
    const modulesValue = prompt('Moduły (oddzielone przecinkami)', Array.isArray(role.modules) ? role.modules.join(', ') : '')
    if (modulesValue === null) return
    const descriptionValue = prompt('Opis', role.description || '')
    if (descriptionValue === null) return

    setError(null)
    setSuccess(null)
    try {
      await apiFetch(`/api/admin/system/roles/${role.roleKey}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          modules: modulesValue.split(',').map(item => item.trim()).filter(Boolean),
          description: descriptionValue
        })
      })
      setSuccess('Rola została zaktualizowana')
      loadRolesAndAudit()
    } catch (err) {
      setError(err.message || 'Nie udało się zaktualizować roli')
    }
  }

  async function loadEntityAudit() {
    if (!entityAuditForm.entityType || !entityAuditForm.entityId) {
      setError('Podaj typ encji i ID')
      return
    }
    setEntityAuditLoading(true)
    setError(null)
    try {
      const data = await apiFetch(`/api/audit-logs/entity/${encodeURIComponent(entityAuditForm.entityType)}/${encodeURIComponent(entityAuditForm.entityId)}`)
      const entries = data?.data || data?.items || data || []
      setEntityAuditLogs(Array.isArray(entries) ? entries : [])
    } catch (err) {
      setError(err.message || 'Nie udało się pobrać historii encji')
    } finally {
      setEntityAuditLoading(false)
    }
  }

  async function assignRole(e) {
    e.preventDefault()
    setError(null)
    setSuccess(null)
    if (!assignForm.roleKey || !assignForm.userId) {
      setError('Podaj rolę i użytkownika')
      return
    }
    try {
      await apiFetch(`/api/admin/system/roles/${assignForm.roleKey}/assign`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ userId: parseInt(assignForm.userId, 10) })
      })
      setAssignForm({ roleKey: '', userId: '' })
      setSuccess('Rola została przypisana użytkownikowi')
    } catch (err) {
      setError(err.message || 'Nie udało się przypisać roli')
    }
  }

  return (
    <div className="page">
      <PageHeader
        title="Panel administratora"
        subtitle="Zarządzaj konfiguracją systemu, uprawnieniami i użytkownikami."
      />

      <StatGrid>
        <StatCard title="Użytkownicy" value={users.length} subtitle="W systemie" />
        <StatCard title="Role" value={roles.length} subtitle="Uprawnienia" />
        <StatCard title="Audyt" value={auditLogs.length} subtitle="Ostatnie wpisy" />
      </StatGrid>

      <section className="surface-card">
        <div className="section-header">
          <h2>Statystyki biblioteki</h2>
          {!libraryStatsLoading && <button className="btn btn-secondary" onClick={loadLibraryStats}>Odśwież</button>}
        </div>
        {libraryStatsLoading && <p>Ładowanie...</p>}
        {!libraryStatsLoading && (
          <StatGrid>
            <StatCard title="Książki" value={libraryStats?.booksCount ?? '—'} subtitle="W katalogu" />
            <StatCard title="Czytelnicy" value={libraryStats?.usersCount ?? '—'} subtitle="Konta aktywne" />
            <StatCard title="Wypożyczenia" value={libraryStats?.loansCount ?? '—'} subtitle="Aktywne" />
            <StatCard title="Rezerwacje" value={libraryStats?.reservationsQueue ?? '—'} subtitle="W kolejce" />
            <StatCard title="Transakcje dziś" value={libraryStats?.transactionsToday ?? '—'} subtitle="Nowe wypożyczenia" />
            <StatCard title="Aktywni dziś" value={libraryStats?.activeUsers ?? '—'} subtitle="Szacunek" />
          </StatGrid>
        )}
      </section>

      {error && <FeedbackCard variant="error">{error}</FeedbackCard>}
      {success && <FeedbackCard variant="success">{success}</FeedbackCard>}

      <div className="tabs" role="tablist" aria-label="Admin Panel Tabs">
        <button 
          className={`tab ${activeTab === 'users' ? 'tab--active' : ''}`} 
          onClick={() => setActiveTab('users')}
          role="tab"
          aria-selected={activeTab === 'users'}
          aria-controls="users-panel"
        >
          Zarządzanie użytkownikami
        </button>
        <button 
          className={`tab ${activeTab === 'system' ? 'tab--active' : ''}`} 
          onClick={() => setActiveTab('system')}
          role="tab"
          aria-selected={activeTab === 'system'}
          aria-controls="system-panel"
        >
          System i integracje
        </button>
        <button 
          className={`tab ${activeTab === 'roles' ? 'tab--active' : ''}`} 
          onClick={() => setActiveTab('roles')}
          role="tab"
          aria-selected={activeTab === 'roles'}
          aria-controls="roles-panel"
        >
          Audyt i role
        </button>
      </div>

      <div id="users-panel" role="tabpanel" hidden={activeTab !== 'users'}>
        {activeTab === 'users' && (
          <UserManagement
            users={users}
            loading={loading}
            userSearchQuery={userSearchQuery}
            setUserSearchQuery={setUserSearchQuery}
            searchUsers={searchUsers}
            loadUsers={loadUsers}
            setEditingUser={setEditingUser}
            updateUserPermissions={updateUserPermissions}
            toggleUserBlock={toggleUserBlock}
            deleteUser={deleteUser}
            editingUser={editingUser}
            updateUserData={updateUserData}
          />
        )}
      </div>

      <div id="system-panel" role="tabpanel" hidden={activeTab !== 'system'}>
        {activeTab === 'system' && (
          <SystemSettings
            settings={settings}
            integrations={integrations}
            loading={loading}
            systemLoaded={systemLoaded}
            loadSystem={loadSystem}
            updateSetting={updateSetting}
            integrationForm={integrationForm}
            setIntegrationForm={setIntegrationForm}
            createIntegration={createIntegration}
            toggleIntegration={toggleIntegration}
            testIntegration={testIntegration}
          />
        )}
      </div>

      <div id="roles-panel" role="tabpanel" hidden={activeTab !== 'roles'}>
        {activeTab === 'roles' && (
          <RolesAndAudit
            roles={roles}
            auditLogs={auditLogs}
            users={users}
            loading={loading}
            rolesLoaded={rolesLoaded}
            loadRolesAndAudit={loadRolesAndAudit}
            roleForm={roleForm}
            setRoleForm={setRoleForm}
            createRole={createRole}
            updateRole={updateRole}
            assignForm={assignForm}
            setAssignForm={setAssignForm}
            assignRole={assignRole}
            entityAuditForm={entityAuditForm}
            setEntityAuditForm={setEntityAuditForm}
            entityAuditLogs={entityAuditLogs}
            entityAuditLoading={entityAuditLoading}
            loadEntityAudit={loadEntityAudit}
            defaultRole={defaultRole}
          
      </div>
    </div>
  )
}
          </div>
          <div className="form-field">
            <label>Wyszukaj</label>
            <input
              value={userSearchQuery}
              onChange={(e) => {
                const value = e.target.value
                setUserSearchQuery(value)
                searchUsers(value)
              }}
              placeholder="Szukaj po imieniu, emailu lub karcie"
            />
          </div>

          {loading && <p>Ładowanie...</p>}
          {!loading && users.length === 0 && <p>Brak użytkowników.</p>}

          {!loading && users.length > 0 && (
            <div className="table-responsive">
              <table className="table">
                <thead>
                  <tr>
                    <th>Użytkownik</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Akcje</th>
                  </tr>
                </thead>
                <tbody>
                  {users.map(user => (
                    <tr key={user.id}>
                      <td>{user.name || 'Brak nazwy'}</td>
                      <td>{user.email}</td>
                      <td>{Array.isArray(user.roles) ? user.roles.join(', ') : '-'}</td>
                      <td>{user.blocked ? 'Zablokowany' : 'Aktywny'}</td>
                      <td>
                        <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap' }}>
                          <button className="btn btn-sm" onClick={() => setEditingUser(user)}>
                            Edytuj
                          </button>
                          <button className="btn btn-sm btn-secondary" onClick={() => updateUserPermissions(user.id, user.roles)}>
                            Uprawnienia
                          </button>
                          <button
                            className={`btn btn-sm ${user.blocked ? 'btn-primary' : 'btn-danger'}`}
                            onClick={() => toggleUserBlock(user.id, user.blocked)}
                          >
                            {user.blocked ? 'Odblokuj' : 'Zablokuj'}
                          </button>
                          <button className="btn btn-sm btn-danger" onClick={() => deleteUser(user.id)}>
                            Usuń
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}

          {editingUser && (
            <div className="modal-overlay" onClick={() => setEditingUser(null)}>
              <div className="modal-content" onClick={e => e.stopPropagation()} style={{ maxWidth: '600px' }}>
                <h3>Edycja użytkownika: {editingUser.name}</h3>
                <form onSubmit={(e) => {
                  e.preventDefault()
                  const formData = new FormData(e.target)
                  const updates = {
                    name: formData.get('name'),
                    email: formData.get('email'),
                    cardNumber: formData.get('cardNumber'),
                    accountStatus: formData.get('accountStatus'),
                    roles: Array.from(formData.getAll('roles'))
                  }
                  updateUserData(editingUser.id, updates)
                }}>
                  <div className="form-field">
                    <label>Imię i nazwisko</label>
                    <input type="text" name="name" defaultValue={editingUser.name} required />
                  </div>
                  <div className="form-field">
                    <label>Email</label>
                    <input type="email" name="email" defaultValue={editingUser.email} required />
                  </div>
                  <div className="form-field">
                    <label>Numer karty bibliotecznej</label>
                    <input type="text" name="cardNumber" defaultValue={editingUser.cardNumber || ''} />
                  </div>
                  <div className="form-field">
                    <label>Status konta</label>
                    <select name="accountStatus" defaultValue={editingUser.accountStatus || 'Aktywne'}>
                      <option value="Aktywne">Aktywne</option>
                      <option value="Zawieszone">Zawieszone</option>
                      <option value="Wygasłe">Wygasłe</option>
                    </select>
                  </div>
                  <div className="form-field">
                    <label>Role użytkownika</label>
                    <div className="checkbox-field">
                      <input
                        type="checkbox"
                        id="role_user"
                        name="roles"
                        value="ROLE_USER"
                        defaultChecked={editingUser.roles?.includes('ROLE_USER')}
                      />
                      <label htmlFor="role_user">Użytkownik (ROLE_USER)</label>
                    </div>
                    <div className="checkbox-field">
                      <input
                        type="checkbox"
                        id="role_librarian"
                        name="roles"
                        value="ROLE_LIBRARIAN"
                        defaultChecked={editingUser.roles?.includes('ROLE_LIBRARIAN')}
                      />
                      <label htmlFor="role_librarian">Bibliotekarz (ROLE_LIBRARIAN)</label>
                    </div>
                    <div className="checkbox-field">
                      <input
                        type="checkbox"
                        id="role_admin"
                        name="roles"
                        value="ROLE_ADMIN"
                        defaultChecked={editingUser.roles?.includes('ROLE_ADMIN')}
                      />
                      <label htmlFor="role_admin">Administrator (ROLE_ADMIN)</label>
                    </div>
                  </div>
                  <div className="modal-actions">
                    <button type="button" className="btn btn-secondary" onClick={() => setEditingUser(null)}>
                      Anuluj
                    </button>
                    <button type="submit" className="btn btn-primary">
                      Zapisz zmiany
                    </button>
                  </div>
                </form>
              </div>
            </div>
          )}
        </div>
      )}

      {activeTab === 'system' && (
        <div className="grid two-columns">
          <section className="surface-card">
            <div className="section-header">
              <h2>Ustawienia systemowe</h2>
              {!loading && systemLoaded && <button className="btn btn-secondary" onClick={loadSystem}>Odśwież</button>}
            </div>
            {loading && <p>Ładowanie...</p>}
            {!loading && (
              <ul className="list">
                {settings.length === 0 && <li>Brak ustawień do wyświetlenia.</li>}
                {settings.map(item => (
                  <li key={item.key}>
                    <div className="list-row">
                      <div>
                        <strong>{item.key}</strong>
                        {item.description && <div className="support-copy">{item.description}</div>}
                        <div className="support-copy">{String(item.value ?? '')}</div>
                      </div>
                      <button
                        className="btn btn-sm"
                        onClick={() => updateSetting(item.key, prompt('Nowa wartość', item.value ?? '') || item.value)}
                      >
                        Edytuj
                      </button>
                    </div>
                  </li>
                ))}
              </ul>
            )}
          </section>

          <section className="surface-card">
            <div className="section-header">
              <h2>Integracje</h2>
              {!loading && <button className="btn btn-secondary" onClick={loadSystem}>Przeładuj</button>}
            </div>
            {loading && <p>Ładowanie...</p>}
            {!loading && (
              <ul className="list">
                {integrations.length === 0 && <li>Brak zdefiniowanych integracji.</li>}
                {integrations.map(item => (
                  <li key={item.id}>
                    <div className="list-row">
                      <div>
                        <strong>{item.name || 'Integracja'}</strong>
                        <div className="support-copy">
                          {item.provider || 'typ nieznany'} - {item.settings?.endpoint || 'brak adresu'}
                        </div>
                        {item.lastStatus && <div className="support-copy">Status: {item.lastStatus}</div>}
                      </div>
                      <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
                        <button className="btn btn-sm btn-secondary" type="button" onClick={() => testIntegration(item.id)}>
                          Testuj
                        </button>
                        <label className="switch">
                          <input
                            type="checkbox"
                            checked={!!item.enabled}
                            onChange={e => toggleIntegration(item.id, e.target.checked)}
                          />
                          <span className="switch-slider" />
                        </label>
                      </div>
                    </div>
                  </li>
                ))}
              </ul>
            )}

            <form className="form" onSubmit={createIntegration}>
              <h3>Dodaj integrację</h3>
              <div className="form-field">
                <label>Nazwa</label>
                <input value={integrationForm.name} onChange={e => setIntegrationForm({ ...integrationForm, name: e.target.value })} required />
              </div>
              <div className="form-field">
                <label>Dostawca</label>
                <input value={integrationForm.provider} onChange={e => setIntegrationForm({ ...integrationForm, provider: e.target.value })} required />
              </div>
              <div className="form-field">
                <label>Endpoint</label>
                <input value={integrationForm.endpoint} onChange={e => setIntegrationForm({ ...integrationForm, endpoint: e.target.value })} required />
              </div>
              <div className="form-field">
                <label>API key (opcjonalnie)</label>
                <input value={integrationForm.apiKey} onChange={e => setIntegrationForm({ ...integrationForm, apiKey: e.target.value })} />
              </div>
              <div className="form-field checkbox">
                <label>
                  <input
                    type="checkbox"
                    checked={integrationForm.enabled}
                    onChange={e => setIntegrationForm({ ...integrationForm, enabled: e.target.checked })}
                  />
                  Aktywna
                </label>
              </div>
              <button type="submit" className="btn btn-primary">Zapisz integrację</button>
            </form>
          </section>
        </div>
      )}

      {activeTab === 'roles' && (
        <div className="grid two-columns">
          <section className="surface-card">
            <div className="section-header">
              <h2>Role systemowe</h2>
              {rolesLoaded && <button className="btn btn-secondary" onClick={loadRolesAndAudit}>Odśwież</button>}
            </div>
            {loading && <p>Ładowanie...</p>}
            {!loading && (
              <ul className="list">
                {roles.length === 0 && <li>Brak zdefiniowanych ról.</li>}
                {roles.map(role => (
                  <li key={role.roleKey}>
                    <strong>{role.name}</strong>
                    <div className="support-copy">{role.roleKey}</div>
                    {role.modules?.length > 0 && <div className="tag-list">{role.modules.map(module => <span key={module} className="badge">{module}</span>)}</div>}
                    <button className="btn btn-sm" type="button" onClick={() => updateRole(role)} style={{ marginTop: '0.5rem' }}>
                      Edytuj rolę
                    </button>
                  </li>
                ))}
              </ul>
            )}

            <form className="form" onSubmit={createRole}>
              <h3>Dodaj rolę</h3>
              <div className="form-field">
                <label>Nazwa</label>
                <input value={roleForm.name} onChange={e => setRoleForm({ ...roleForm, name: e.target.value })} required />
              </div>
              <div className="form-field">
                <label>Klucz roli</label>
                <input value={roleForm.roleKey} onChange={e => setRoleForm({ ...roleForm, roleKey: e.target.value })} placeholder="np. ROLE_REPORTER" required />
              </div>
              <div className="form-field">
                <label>Moduły (oddzielone przecinkami)</label>
                <input value={roleForm.modules} onChange={e => setRoleForm({ ...roleForm, modules: e.target.value })} placeholder="loans,acquisitions" />
              </div>
              <div className="form-field">
                <label>Opis</label>
                <textarea value={roleForm.description} onChange={e => setRoleForm({ ...roleForm, description: e.target.value })} />
              </div>
              <button type="submit" className="btn btn-primary">Dodaj rolę</button>
            </form>

            <form className="form" onSubmit={assignRole}>
              <h3>Przypisz rolę</h3>
              <div className="form-field">
                <label>Rola</label>
                <select value={assignForm.roleKey} onChange={e => setAssignForm({ ...assignForm, roleKey: e.target.value })}>
                  <option value="">Wybierz rolę</option>
                  {roles.map(role => (
                    <option key={role.roleKey} value={role.roleKey}>{role.name || role.roleKey}</option>
                  ))}
                </select>
              </div>
              <div className="form-field">
                <label>Użytkownik</label>
                <select value={assignForm.userId} onChange={e => setAssignForm({ ...assignForm, userId: e.target.value })} required>
                  <option value="">Wybierz użytkownika</option>
                  {users.map(user => (
                    <option key={user.id} value={user.id}>{user.name} ({user.email})</option>
                  ))}
                </select>
              </div>
              <button type="submit" className="btn btn-primary">Przypisz rolę</button>
            </form>
          </section>

          <section className="surface-card">
            <h2>Ostatnie zdarzenia audytu</h2>
            {loading && <p>Ładowanie...</p>}
            {!loading && (
              <>
                <div className="form" style={{ marginBottom: '1.5rem' }}>
                  <h3>Historia encji</h3>
                  <div className="form-row form-row--two">
                    <div className="form-field">
                      <label>Typ encji</label>
                      <input
                        value={entityAuditForm.entityType}
                        onChange={e => setEntityAuditForm(prev => ({ ...prev, entityType: e.target.value }))}
                        placeholder="np. announcement, loan"
                      />
                    </div>
                    <div className="form-field">
                      <label>ID encji</label>
                      <input
                        value={entityAuditForm.entityId}
                        onChange={e => setEntityAuditForm(prev => ({ ...prev, entityId: e.target.value }))}
                        placeholder="np. 12"
                      />
                    </div>
                  </div>
                  <div className="form-actions">
                    <button className="btn btn-secondary" type="button" onClick={loadEntityAudit} disabled={entityAuditLoading}>
                      {entityAuditLoading ? 'Ładowanie...' : 'Pobierz historię'}
                    </button>
                  </div>
                  {entityAuditLogs.length > 0 && (
                    <ul className="list" style={{ marginTop: '1rem' }}>
                      {entityAuditLogs.map(entry => (
                        <li key={entry.id || `${entry.entityType}-${entry.entityId}-${entry.createdAt || entry.timestamp}`}>
                          <div className="list-row">
                            <div>
                              <strong>{entry.action || entry.event || 'Zdarzenie'}</strong>
                              <div className="support-copy">{entry.entityType || entry.entity} #{entry.entityId || entry.entity_id}</div>
                            </div>
                            <span className="support-copy">{entry.createdAt || entry.timestamp || ''}</span>
                          </div>
                        </li>
                      ))}
                    </ul>
                  )}
                </div>
                <ul className="timeline">
                  {auditLogs.length === 0 && <li>Brak wpisów audytowych.</li>}
                  {auditLogs.map(entry => (
                    <li key={entry.id || `${entry.entity}-${entry.timestamp}`}>
                      <div className="list-row">
                        <div>
                          <strong>{entry.action || entry.event || 'Zdarzenie'}</strong>
                          <div className="support-copy">
                            {entry.entityType || entry.entity} #{entry.entityId || entry.entity_id} - {entry.userEmail || entry.user || 'nieznany użytkownik'}
                          </div>
                        </div>
                        <span className="support-copy">{entry.timestamp || entry.createdAt || ''}</span>
                      </div>
                    </li>
                  ))}
                </ul>
              </>
            )}
          </section>
        </div>
      )}
    </div>
  )
}
