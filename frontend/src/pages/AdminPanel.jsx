import React, { useEffect, useMemo, useState } from 'react'
import { apiFetch } from '../api'
import PageHeader from '../components/ui/PageHeader'
import StatGrid from '../components/ui/StatGrid'
import StatCard from '../components/ui/StatCard'
import FeedbackCard from '../components/ui/FeedbackCard'
import UserManagement from '../components/admin/UserManagement'
import SystemSettings from '../components/admin/SystemSettings'
import RolesAndAudit from '../components/admin/RolesAndAudit'
import LoanManagement from '../components/admin/LoanManagement'
import { loanService } from '../services/loanService'

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
  const [showStats, setShowStats] = useState(false)

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

  // Loans
  const [loans, setLoans] = useState([])
  const [loansLoading, setLoansLoading] = useState(false)
  const [loanFilters, setLoanFilters] = useState({ user: '', book: '', status: 'all' })
  const [editingLoan, setEditingLoan] = useState(null)
  const [loanEditForm, setLoanEditForm] = useState({
    dueAt: '',
    status: 'active',
    bookId: '',
    bookCopyId: ''
  })

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
    } else if (activeTab === 'loans') {
      loadLoans()
    }
  }, [activeTab])

  const formatDateInput = (value) => {
    if (!value) return ''
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return ''
    return date.toISOString().slice(0, 10)
  }

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

  async function loadLoans(filters = loanFilters) {
    setLoansLoading(true)
    setError(null)
    try {
      const params = {
        user: filters.user,
        book: filters.book
      }
      if (filters.status && filters.status !== 'all') {
        params.status = filters.status
      }
      const data = await loanService.getAllLoans(params)
      const items = data?.data || data?.items || data || []
      setLoans(Array.isArray(items) ? items : [])
    } catch (err) {
      setError(err.message || 'Nie udało się pobrać wypożyczeń')
    } finally {
      setLoansLoading(false)
    }
  }

  function resetLoanFilters() {
    const cleared = { user: '', book: '', status: 'all' }
    setLoanFilters(cleared)
    loadLoans(cleared)
  }

  function openLoanEdit(loan) {
    setEditingLoan(loan)
    setLoanEditForm({
      dueAt: formatDateInput(loan.dueAt),
      status: loan.returnedAt ? 'returned' : 'active',
      bookId: loan.book?.id ?? '',
      bookCopyId: loan.bookCopy?.id ?? ''
    })
  }

  async function saveLoanEdit() {
    if (!editingLoan) return
    setError(null)
    setSuccess(null)
    const payload = {}
    if (loanEditForm.dueAt) payload.dueAt = loanEditForm.dueAt
    if (loanEditForm.status) payload.status = loanEditForm.status
    if (loanEditForm.bookId) payload.bookId = parseInt(loanEditForm.bookId, 10)
    if (loanEditForm.bookCopyId) payload.bookCopyId = parseInt(loanEditForm.bookCopyId, 10)

    try {
      await loanService.updateLoan(editingLoan.id, payload)
      setSuccess('Wypożyczenie zostało zaktualizowane')
      setEditingLoan(null)
      loadLoans()
    } catch (err) {
      setError(err.message || 'Nie udało się zaktualizować wypożyczenia')
    }
  }

  async function returnLoan(loan) {
    if (!confirm('Potwierdzić zwrot wypożyczenia?')) return
    setError(null)
    setSuccess(null)
    try {
      await loanService.returnLoan(loan.id)
      setSuccess('Wypożyczenie zostało zwrócone')
      loadLoans()
    } catch (err) {
      setError(err.message || 'Nie udało się zwrócić wypożyczenia')
    }
  }

  async function extendLoan(loan) {
    setError(null)
    setSuccess(null)
    try {
      await loanService.extendLoan(loan.id)
      setSuccess('Wypożyczenie zostało przedłużone')
      loadLoans()
    } catch (err) {
      setError(err.message || 'Nie udało się przedłużyć wypożyczenia')
    }
  }

  async function deleteLoan(loan) {
    if (!confirm('Na pewno usunąć wypożyczenie?')) return
    setError(null)
    setSuccess(null)
    try {
      await loanService.deleteLoan(loan.id)
      setSuccess('Wypożyczenie zostało usunięte')
      loadLoans()
    } catch (err) {
      setError(err.message || 'Nie udało się usunąć wypożyczenia')
    }
  }

  return (
    <div className="page admin-panel">
      <PageHeader
        title="Panel administratora"
        subtitle="Zarządzaj konfiguracją systemu, uprawnieniami i użytkownikami."
      />

      <section className="surface-card">
        <div className="section-header">
          <h2>Statystyki</h2>
          <div style={{ display: 'flex', gap: '0.5rem' }}>
            <button 
              className="btn btn-secondary" 
              onClick={() => setShowStats(!showStats)}
            >
              {showStats ? 'Ukryj' : 'Pokaż'}
            </button>
            {showStats && !libraryStatsLoading && (
              <button className="btn btn-secondary" onClick={loadLibraryStats}>Odśwież</button>
            )}
          </div>
        </div>
        {showStats && (
          <>
            {libraryStatsLoading && <p>Ładowanie...</p>}
            {!libraryStatsLoading && (
              <>
                <StatGrid>
                  <StatCard title="Książki" value={libraryStats?.booksCount ?? '—'} subtitle="W katalogu" />
                  <StatCard title="Czytelnicy" value={libraryStats?.usersCount ?? '—'} subtitle="Konta aktywne" />
                  <StatCard title="Wypożyczenia" value={libraryStats?.loansCount ?? '—'} subtitle="Aktywne" />
                  <StatCard title="Rezerwacje" value={libraryStats?.reservationsQueue ?? '—'} subtitle="W kolejce" />
                  <StatCard title="Transakcje dziś" value={libraryStats?.transactionsToday ?? '—'} subtitle="Nowe wypożyczenia" />
                  <StatCard title="Aktywni dziś" value={libraryStats?.activeUsers ?? '—'} subtitle="Szacunek" />
                </StatGrid>
                <StatGrid style={{ marginTop: '1rem' }}>
                  <StatCard title="Użytkownicy" value={users.length} subtitle="W systemie" />
                  <StatCard title="Role" value={roles.length} subtitle="Uprawnienia" />
                  <StatCard title="Audyt" value={auditLogs.length} subtitle="Ostatnie wpisy" />
                </StatGrid>
              </>
            )}
          </>
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
        <button 
          className={`tab ${activeTab === 'loans' ? 'tab--active' : ''}`} 
          onClick={() => setActiveTab('loans')}
          role="tab"
          aria-selected={activeTab === 'loans'}
          aria-controls="loans-panel"
        >
          Wypożyczenia
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
          />
        )}
      </div>

      <div id="loans-panel" role="tabpanel" hidden={activeTab !== 'loans'}>
        {activeTab === 'loans' && (
          <LoanManagement
            loans={loans}
            loading={loansLoading}
            filters={loanFilters}
            setFilters={setLoanFilters}
            onSearch={() => loadLoans()}
            onReset={resetLoanFilters}
            onEdit={openLoanEdit}
            onReturn={returnLoan}
            onExtend={extendLoan}
            onDelete={deleteLoan}
            editingLoan={editingLoan}
            editForm={loanEditForm}
            setEditForm={setLoanEditForm}
            onSaveEdit={saveLoanEdit}
            onCloseEdit={() => setEditingLoan(null)}
          />
        )}
      </div>
    </div>
  )
}
