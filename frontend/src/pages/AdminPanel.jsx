import React, { useEffect, useMemo, useState } from 'react'
import { apiFetch } from '../api'
import PageHeader from '../components/ui/PageHeader'
import StatGrid from '../components/ui/StatGrid'
import StatCard from '../components/ui/StatCard'
import FeedbackCard from '../components/ui/FeedbackCard'

const defaultIntegration = { name: '', type: 'HTTP', endpoint: '', enabled: true }
const defaultRole = { name: '', roleKey: '', modules: '', description: '' }
const defaultStaff = { name: '', email: '', password: '', roleKey: 'ROLE_LIBRARIAN' }

export default function AdminPanel() {
  const [activeTab, setActiveTab] = useState('users')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)
  const [success, setSuccess] = useState(null)

  // User management
  const [users, setUsers] = useState([])
  const [userSearchQuery, setUserSearchQuery] = useState('')
  const [selectedUser, setSelectedUser] = useState(null)
  const [editingUser, setEditingUser] = useState(null)

  const [settings, setSettings] = useState([])
  const [integrations, setIntegrations] = useState([])
  const [backups, setBackups] = useState([])
  const [roles, setRoles] = useState([])
  const [auditLogs, setAuditLogs] = useState([])
  const [entityAuditForm, setEntityAuditForm] = useState({ entityType: '', entityId: '' })
  const [entityAuditLogs, setEntityAuditLogs] = useState([])
  const [entityAuditLoading, setEntityAuditLoading] = useState(false)

  const [integrationForm, setIntegrationForm] = useState(defaultIntegration)
  const [roleForm, setRoleForm] = useState(defaultRole)
  const [assignForm, setAssignForm] = useState({ roleKey: '', userId: '' })
  const [staffForm, setStaffForm] = useState(defaultStaff)
  const [testLoginForm, setTestLoginForm] = useState({ email: '', password: '' })
  const [testLoginResult, setTestLoginResult] = useState(null)

  const systemLoaded = useMemo(() => settings.length > 0 || integrations.length > 0 || backups.length > 0, [settings, integrations, backups])
  const rolesLoaded = useMemo(() => roles.length > 0, [roles])

  useEffect(() => {
    if (activeTab === 'users') {
      loadUsers()
    } else if (activeTab === 'system') {
      loadSystem()
    } else if (activeTab === 'roles') {
      loadRolesAndAudit()
    } else if (activeTab === 'staff') {
      loadRoles()
    }
  }, [activeTab])

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
    const input = prompt('Roles (comma separated)', Array.isArray(currentRoles) ? currentRoles.join(', ') : '')
    if (input === null) return

    const roles = input.split(',').map(role => role.trim()).filter(Boolean)
    if (roles.length === 0) {
      setError('Role list is required')
      return
    }

    setError(null)
    setSuccess(null)
    try {
      await apiFetch(`/api/users/${userId}/permissions`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ roles })
      })
      setSuccess('Permissions updated')
      loadUsers()
    } catch (err) {
      setError(err.message || 'Nie udalo sie zaktualizowac uprawnien')
    }
  }

  async function toggleUserBlock(userId, currentBlocked) {
    if (!confirm(currentBlocked ? 'Odblokowac tego uzytkownika?' : 'Zablokowac tego uzytkownika?')) return
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
      setSuccess(currentBlocked ? 'Uzytkownik zostal odblokowany' : 'Uzytkownik zostal zablokowany')
      loadUsers()
    } catch (err) {
      setError(err.message || 'Nie udalo sie zmienic statusu uzytkownika')
    }
  }

  async function deleteUser(userId) {
    if (!confirm('Na pewno usunąć to konto? Tej operacji nie można cofnąć!')) return
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
      const [settingsRes, integrationsRes, backupsRes] = await Promise.all([
        apiFetch('/api/admin/system/settings'),
        apiFetch('/api/admin/system/integrations'),
        apiFetch('/api/admin/system/backups')
      ])
      setSettings(Array.isArray(settingsRes) ? settingsRes : [])
      setIntegrations(Array.isArray(integrationsRes) ? integrationsRes : [])
      setBackups(Array.isArray(backupsRes) ? backupsRes : backupsRes?.backups || [])
    } catch (err) {
      setError(err.message || 'Nie udało się pobrać danych systemu')
    } finally {
      setLoading(false)
    }
  }

  async function loadRoles() {
    setError(null)
    try {
      const data = await apiFetch('/api/admin/system/roles')
      const normalized = data?.roles || (Array.isArray(data) ? data : [])
      setRoles(normalized)
    } catch (err) {
      setError(err.message || 'Nie udało się pobrać ról')
    }
  }

  async function loadRolesAndAudit() {
    setLoading(true)
    setError(null)
    try {
      const [rolesRes, auditRes] = await Promise.all([
        apiFetch('/api/admin/system/roles'),
        apiFetch('/api/audit-logs?limit=25')
      ])
      setRoles(rolesRes?.roles || (Array.isArray(rolesRes) ? rolesRes : []))
      const entries = auditRes?.data || auditRes?.items || []
      setAuditLogs(Array.isArray(entries) ? entries : [])
    } catch (err) {
      setError(err.message || 'Nie udało się pobrać audytu lub ról')
    } finally {
      setLoading(false)
    }
  }

  async function updateSetting(key, value) {
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
          type: integrationForm.type,
          endpoint: integrationForm.endpoint,
          enabled: integrationForm.enabled
        })
      })
      setSuccess('Integracja została dodana')
      setIntegrationForm(defaultIntegration)
      loadSystem()
    } catch (err) {
      setError(err.message || 'Nie udalo sie przetestowac integracji')
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
      setError(err.message || 'Nie udalo sie przetestowac integracji')
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
      setError(err.message || 'Nie udalo sie przetestowac integracji')
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
      setSuccess('Nowa rola zostala dodana')
      loadRolesAndAudit()
    } catch (err) {
      setError(err.message || 'Nie udalo sie utworzyc roli')
    }
  }

  async function updateRole(role) {
    const modulesValue = prompt('Modules (comma separated)', Array.isArray(role.modules) ? role.modules.join(', ') : '')
    if (modulesValue === null) return
    const descriptionValue = prompt('Description', role.description || '')
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
      setSuccess('Rola zostala zaktualizowana')
      loadRolesAndAudit()
    } catch (err) {
      setError(err.message || 'Nie udalo sie zaktualizowac roli')
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
      setError(err.message || 'Nie udalo sie pobrac historii encji')
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

  async function createStaff(e) {
    e.preventDefault()
    setError(null)
    setSuccess(null)
    try {
      await apiFetch('/api/users', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name: staffForm.name,
          email: staffForm.email,
          password: staffForm.password,
          roles: [staffForm.roleKey],
          verified: true
        })
      })
      setStaffForm(defaultStaff)
      setSuccess('Konto pracownika zostało utworzone')
    } catch (err) {
      setError(err.message || 'Nie udało się utworzyć konta pracownika')
    }
  }

  async function createBackup() {
    setError(null)
    setSuccess(null)
    try {
      await apiFetch('/api/admin/system/backups', { method: 'POST' })
      setSuccess('Wykonano kopię bezpieczeństwa bazy')
      loadSystem()
    } catch (err) {
      setError(err.message || 'Nie udało się utworzyć kopii')
    }
  }

  async function runTestLogin(e) {
    e.preventDefault()
    setError(null)
    setSuccess(null)
    setTestLoginResult(null)
    try {
      const result = await apiFetch('/api/test-login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: testLoginForm.email, password: testLoginForm.password })
      })
      setTestLoginResult(result)
      setSuccess('Test login wykonany')
    } catch (err) {
      setError(err.message || 'Nie udalo sie wykonac testu logowania')
    }
  }

  return (
    <div className="page">
      <PageHeader
        title="Panel administratora"
        subtitle="Zarządzaj konfiguracją systemu, uprawnieniami i personelem."
      />

      <StatGrid>
        <StatCard title="Użytkownicy" value={users.length} subtitle="W systemie" />
        <StatCard title="Role" value={roles.length} subtitle="Uprawnienia" />
        <StatCard title="Audyt" value={auditLogs.length} subtitle="Ostatnie wpisy" />
      </StatGrid>

      {error && <FeedbackCard variant="error">{error}</FeedbackCard>}
      {success && <FeedbackCard variant="success">{success}</FeedbackCard>}

      <div className="tabs">
        <button className={`tab ${activeTab === 'users' ? 'tab--active' : ''}`} onClick={() => setActiveTab('users')}>
          Zarządzanie użytkownikami
        </button>
        <button className={`tab ${activeTab === 'system' ? 'tab--active' : ''}`} onClick={() => setActiveTab('system')}>
          System i integracje
        </button>
        <button className={`tab ${activeTab === 'roles' ? 'tab--active' : ''}`} onClick={() => setActiveTab('roles')}>
          Audyt
        </button>
        <button className={`tab $

          {editingAnnouncement && announcementForm && (
            <div className="modal-overlay" onClick={() => { setEditingAnnouncement(null); setAnnouncementForm(null) }}>
              <div className="modal-content" onClick={e => e.stopPropagation()} style={{ maxWidth: '720px' }}>
                <h3>Edycja ogloszenia</h3>
                <form onSubmit={(e) => {
                  e.preventDefault()
                  updateAnnouncement(editingAnnouncement.id, {
                    title: announcementForm.title,
                    content: announcementForm.content,
                    type: announcementForm.type,
                    status: announcementForm.status,
                    isPinned: announcementForm.isPinned,
                    showOnHomepage: announcementForm.showOnHomepage,
                    eventAt: announcementForm.eventAt ? new Date(announcementForm.eventAt).toISOString() : null
                  })
                }}>
                  <div className="form-field">
                    <label>Tytul</label>
                    <input
                      value={announcementForm.title}
                      onChange={(e) => setAnnouncementForm(prev => ({ ...prev, title: e.target.value }))}
                      required
                    />
                  </div>
                  <div className="form-field">
                    <label>Tresc</label>
                    <textarea
                      value={announcementForm.content}
                      onChange={(e) => setAnnouncementForm(prev => ({ ...prev, content: e.target.value }))}
                      rows={5}
                      required
                    />
                  </div>
                  <div className="form-row form-row--two">
                    <div className="form-field">
                      <label>Typ</label>
                      <select value={announcementForm.type} onChange={(e) => setAnnouncementForm(prev => ({ ...prev, type: e.target.value }))}>
                        <option value="info">info</option>
                        <option value="warning">warning</option>
                        <option value="urgent">urgent</option>
                        <option value="maintenance">maintenance</option>
                        <option value="policy">policy</option>
                        <option value="event">event</option>
                      </select>
                    </div>
                    <div className="form-field">
                      <label>Status</label>
                      <select value={announcementForm.status} onChange={(e) => setAnnouncementForm(prev => ({ ...prev, status: e.target.value }))}>
                        <option value="draft">draft</option>
                        <option value="published">published</option>
                        <option value="archived">archived</option>
                      </select>
                    </div>
                  </div>
                  <div className="form-row form-row--two">
                    <div className="form-field">
                      <label>Data i godzina wydarzenia</label>
                      <input
                        type="datetime-local"
                        value={announcementForm.eventAt}
                        onChange={(e) => setAnnouncementForm(prev => ({ ...prev, eventAt: e.target.value }))}
                      />
                    </div>
                    <div className="form-field checkbox">
                      <label>
                        <input
                          type="checkbox"
                          checked={announcementForm.showOnHomepage}
                          onChange={(e) => setAnnouncementForm(prev => ({ ...prev, showOnHomepage: e.target.checked }))}
                        />
                        Widoczne na stronie glownej
                      </label>
                      <label>
                        <input
                          type="checkbox"
                          checked={announcementForm.isPinned}
                          onChange={(e) => setAnnouncementForm(prev => ({ ...prev, isPinned: e.target.checked }))}
                        />
                        Przypnij ogloszenie
                      </label>
                    </div>
                  </div>
                  <div className="modal-actions">
                    <button type="button" className="btn btn-secondary" onClick={() => { setEditingAnnouncement(null); setAnnouncementForm(null) }}>
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
{activeTab === 'staff' ? 'tab--active' : ''}`} onClick={() => setActiveTab('staff')}>
          Konta pracowników
        </button>
      </div>

      {activeTab === 'users' && (
        <div className="surface-card">
          <div className="section-header">
            <h2>Zarządzanie użytkownikami</h2>
            <div className="form-field" style={{ maxWidth: '400px', margin: 0 }}>
              <input
                type="text"
                value={userSearchQuery}
                onChange={(e) => {
                  setUserSearchQuery(e.target.value)
                  searchUsers(e.target.value)
                }}
                placeholder="Szukaj po imieniu, emailu, PESEL lub numerze karty..."
              />
            </div>
          </div>

          {loading && <p>Ładowanie użytkowników...</p>}
          {!loading && users.length === 0 && <p>Brak użytkowników do wyświetlenia</p>}
          {!loading && users.length > 0 && (
            <div className="table-container">
              <table className="data-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Imię i nazwisko</th>
                    <th>Email</th>
                    <th>Numer karty</th>
                    <th>PESEL</th>
                    <th>Status konta</th>
                    <th>Role</th>
                    <th>Akcje</th>
                  </tr>
                </thead>
                <tbody>
                  {users.map(user => (
                    <tr key={user.id}>
                      <td>{user.id}</td>
                      <td>{user.name}</td>
                      <td>{user.email}</td>
                      <td>{user.cardNumber || '-'}</td>
                      <td>{user.pesel ? user.pesel.substring(0, 6) + '*****' : '-'}</td>
                      <td>
                        <span className={`status-pill ${user.blocked ? 'is-danger' : user.accountStatus === 'Aktywne' ? '' : 'is-warning'}`}>
                          {user.blocked ? 'Zablokowane' : (user.accountStatus || 'Aktywne')}
                        </span>
                      </td>
                      <td>
                        {user.roles?.map(role => (
                          <span key={role} className="badge badge-info" style={{ marginRight: '0.25rem' }}>
                            {role.replace('ROLE_', '')}
                          </span>
                        ))}
                      </td>
                      <td>
                        <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap' }}>
                          <button
                            className="btn btn-sm btn-primary"
                            onClick={() => window.location.href = `/users/${user.id}/details`}
                          >
                            Szczegóły
                          </button>
                          <button
                            className="btn btn-sm btn-secondary"
                            onClick={() => updateUserPermissions(user.id, user.roles)}
                          >
                            Uprawnienia
                          </button>
                          <button
                            className={`btn btn-sm ${user.blocked ? 'btn-primary' : 'btn-danger'}`}
                            onClick={() => toggleUserBlock(user.id, user.blocked)}
                          >
                            {user.blocked ? 'Odblokuj' : 'Zablokuj'}
                          </button>
                          <button
                            className="btn btn-sm btn-danger"
                            onClick={() => deleteUser(user.id)}
                          >
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
                        <div className="support-copy">{String(item.value ?? '')}</div>
                      </div>
                      <button className="btn btn-sm" onClick={() => updateSetting(item.key, prompt('Nowa wartość', item.value ?? '') || item.value)}>
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
                        <div className="support-copy">{item.type || 'typ nieznany'} - {item.endpoint || 'brak adresu'}</div>
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
                <label>Typ</label>
                <input value={integrationForm.type} onChange={e => setIntegrationForm({ ...integrationForm, type: e.target.value })} required />
              </div>
              <div className="form-field">
                <label>Endpoint</label>
                <input value={integrationForm.endpoint} onChange={e => setIntegrationForm({ ...integrationForm, endpoint: e.target.value })} required />
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

          <section className="surface-card">
            <div className="section-header">
              <h2>Test logowania (dev)</h2>
            </div>
            <form className="form" onSubmit={runTestLogin}>
              <div className="form-field">
                <label>Email</label>
                <input value={testLoginForm.email} onChange={e => setTestLoginForm(prev => ({ ...prev, email: e.target.value }))} />
              </div>
              <div className="form-field">
                <label>Haslo</label>
                <input type="password" value={testLoginForm.password} onChange={e => setTestLoginForm(prev => ({ ...prev, password: e.target.value }))} />
              </div>
              <button type="submit" className="btn btn-primary">Testuj</button>
            </form>
            {testLoginResult && (
              <pre style={{ whiteSpace: 'pre-wrap' }}>{JSON.stringify(testLoginResult, null, 2)}</pre>
            )}
          </section>

          <section className="surface-card">
            <div className="section-header">
              <h2>Kopie bezpieczeństwa</h2>
              <button className="btn btn-secondary" onClick={createBackup}>Utwórz kopię</button>
            </div>
            {loading && <p>Ładowanie...</p>}
            {!loading && (
              <ul className="list">
                {backups.length === 0 && <li>Brak zapisanych kopii.</li>}
                {backups.map(backup => (
                  <li key={backup.id || backup.name}>
                    <strong>{backup.label || backup.name || 'Backup'}</strong>
                    <div className="support-copy">{backup.createdAt || backup.created_at || 'czas nieznany'}</div>
                  </li>
                ))}
              </ul>
            )}
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
                      Edytuj role
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
                <label>ID użytkownika</label>
                <input type="number" value={assignForm.userId} onChange={e => setAssignForm({ ...assignForm, userId: e.target.value })} required />
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
                    {entityAuditLoading ? 'Ladowanie...' : 'Pobierz historie'}
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
                          {entry.entityType || entry.entity} #{entry.entityId || entry.entity_id} • {entry.userEmail || entry.user || 'nieznany użytkownik'}
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

      {activeTab === 'staff' && (
        <div className="grid two-columns">
          <section className="surface-card">
            <h2>Tworzenie konta pracownika</h2>
            <form className="form" onSubmit={createStaff}>
              <div className="form-field">
                <label>Imię i nazwisko</label>
                <input value={staffForm.name} onChange={e => setStaffForm({ ...staffForm, name: e.target.value })} required />
              </div>
              <div className="form-field">
                <label>Email</label>
                <input type="email" value={staffForm.email} onChange={e => setStaffForm({ ...staffForm, email: e.target.value })} required />
              </div>
              <div className="form-field">
                <label>Hasło tymczasowe</label>
                <input type="password" value={staffForm.password} onChange={e => setStaffForm({ ...staffForm, password: e.target.value })} required />
              </div>
              <div className="form-field">
                <label>Rola</label>
                <select value={staffForm.roleKey} onChange={e => setStaffForm({ ...staffForm, roleKey: e.target.value })}>
                  <option value="ROLE_LIBRARIAN">Bibliotekarz</option>
                  <option value="ROLE_ADMIN">Administrator</option>
                </select>
              </div>
              <button type="submit" className="btn btn-primary">Utwórz konto</button>
            </form>
          </section>

          <section className="surface-card">
            <h2>Wskazówki operacyjne</h2>
            <ul className="list">
              <li>Dodaj konta bibliotekarzy lub administratorów i przypisz im potrzebne role modułowe.</li>
              <li>Regularnie weryfikuj kopie bezpieczeństwa oraz wyniki audytu akcji administracyjnych.</li>
              <li>Utrzymuj integracje (system płatności, LDAP, poczta) w stanie aktywnym i testuj po każdej zmianie.</li>
            </ul>
          </section>
        </div>
      )}
    </div>
  )
}
