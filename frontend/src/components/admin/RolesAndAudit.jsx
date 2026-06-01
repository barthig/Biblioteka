import React from 'react'

export default function RolesAndAudit({
  roles,
  auditLogs,
  users,
  loading,
  rolesLoaded,
  loadRolesAndAudit,
  roleForm,
  setRoleForm,
  createRole,
  updateRole,
  assignForm,
  setAssignForm,
  assignRole,
  entityAuditForm,
  setEntityAuditForm,
  entityAuditLogs,
  entityAuditLoading,
  loadEntityAudit
}) {
  return (
    <div className="grid two-columns">
      <section className="surface-card" role="region" aria-labelledby="roles-title">
        <div className="section-header">
          <h2 id="roles-title">Role systemowe</h2>
          {rolesLoaded && (
            <button 
              className="btn btn-secondary" 
              onClick={loadRolesAndAudit}
              aria-label="Odśwież role systemowe"
            >
              Odśwież
            </button>
          )}
        </div>
        
        {loading && <div aria-live="polite" role="status">Ładowanie...</div>}
        
        {!loading && (
          <ul className="list" role="list">
            {roles.length === 0 && <li>Brak zdefiniowanych ról.</li>}
            {roles.map(role => (
              <li key={role.roleKey}>
                <strong>{role.name}</strong>
                <div className="support-copy">{role.roleKey}</div>
                {role.modules?.length > 0 && (
                  <div className="tag-list" role="list" aria-label="Moduły roli">
                    {role.modules.map(module => (
                      <span key={module} className="badge" role="listitem">
                        {module}
                      </span>
                    ))}
                  </div>
                )}
                <button 
                  className="btn btn-sm" 
                  type="button" 
                  onClick={() => updateRole(role)} 
                  style={{ marginTop: '0.5rem' }}
                  aria-label={`Edytuj rolę ${role.name}`}
                >
                  Edytuj rolę
                </button>
              </li>
            ))}
          </ul>
        )}

        <form className="form" onSubmit={createRole} aria-labelledby="add-role-title">
          <h3 id="add-role-title">Dodaj rolę</h3>
          <div className="form-field">
            <label htmlFor="role-name">Nazwa</label>
            <input 
              id="role-name"
              value={roleForm.name} 
              onChange={e => setRoleForm({ ...roleForm, name: e.target.value })} 
              required 
              aria-required="true"
            />
          </div>
          <div className="form-field">
            <label htmlFor="role-key">Klucz roli</label>
            <input 
              id="role-key"
              value={roleForm.roleKey} 
              onChange={e => setRoleForm({ ...roleForm, roleKey: e.target.value })} 
              placeholder="np. ROLE_REPORTER" 
              required 
              aria-required="true"
              aria-describedby="role-key-help"
            />
            <small id="role-key-help" className="support-copy">
              Używaj formatu ROLE_NAZWA_WIELKIMI_LITERAMI
            </small>
          </div>
          <div className="form-field">
            <label htmlFor="role-modules">Moduły (oddzielone przecinkami)</label>
            <input 
              id="role-modules"
              value={roleForm.modules} 
              onChange={e => setRoleForm({ ...roleForm, modules: e.target.value })} 
              placeholder="loans,acquisitions" 
              aria-describedby="role-modules-help"
            />
            <small id="role-modules-help" className="support-copy">
              Wpisz nazwy modułów oddzielone przecinkami
            </small>
          </div>
          <div className="form-field">
            <label htmlFor="role-description">Opis</label>
            <textarea 
              id="role-description"
              value={roleForm.description} 
              onChange={e => setRoleForm({ ...roleForm, description: e.target.value })} 
            />
          </div>
          <button type="submit" className="btn btn-primary">
            Dodaj rolę
          </button>
        </form>

        <form className="form" onSubmit={assignRole} aria-labelledby="assign-role-title">
          <h3 id="assign-role-title">Przypisz rolę</h3>
          <div className="form-field">
            <label htmlFor="assign-role">Rola</label>
            <select 
              id="assign-role"
              value={assignForm.roleKey} 
              onChange={e => setAssignForm({ ...assignForm, roleKey: e.target.value })}
              aria-required="true"
            >
              <option value="">Wybierz rolę</option>
              {roles.map(role => (
                <option key={role.roleKey} value={role.roleKey}>
                  {role.name || role.roleKey}
                </option>
              ))}
            </select>
          </div>
          <div className="form-field">
            <label htmlFor="assign-user">Użytkownik</label>
            <select 
              id="assign-user"
              value={assignForm.userId} 
              onChange={e => setAssignForm({ ...assignForm, userId: e.target.value })} 
              required 
              aria-required="true"
            >
              <option value="">Wybierz użytkownika</option>
              {users.map(user => (
                <option key={user.id} value={user.id}>
                  {user.name} ({user.email})
                </option>
              ))}
            </select>
          </div>
          <button type="submit" className="btn btn-primary">
            Przypisz rolę
          </button>
        </form>
      </section>

      <section className="surface-card" role="region" aria-labelledby="audit-title">
        <h2 id="audit-title">Ostatnie zdarzenia audytu</h2>
        
        {loading && <div aria-live="polite" role="status">Ładowanie...</div>}
        
        {!loading && (
          <>
            <div className="form" style={{ marginBottom: '1.5rem' }} aria-labelledby="entity-audit-title">
              <h3 id="entity-audit-title">Historia encji</h3>
              <div className="form-row form-row--two">
                <div className="form-field">
                  <label htmlFor="entity-type">Typ encji</label>
                  <input
                    id="entity-type"
                    value={entityAuditForm.entityType}
                    onChange={e => setEntityAuditForm(prev => ({ ...prev, entityType: e.target.value }))}
                    placeholder="np. announcement, loan"
                    aria-describedby="entity-type-help"
                  />
                  <small id="entity-type-help" className="support-copy">
                    announcement, loan, book, user, itp.
                  </small>
                </div>
                <div className="form-field">
                  <label htmlFor="entity-id">ID encji</label>
                  <input
                    id="entity-id"
                    value={entityAuditForm.entityId}
                    onChange={e => setEntityAuditForm(prev => ({ ...prev, entityId: e.target.value }))}
                    placeholder="np. 12"
                    type="number"
                  />
                </div>
              </div>
              <div className="form-actions">
                <button 
                  className="btn btn-secondary" 
                  type="button" 
                  onClick={loadEntityAudit} 
                  disabled={entityAuditLoading}
                  aria-busy={entityAuditLoading}
                >
                  {entityAuditLoading ? 'Ładowanie...' : 'Pobierz historię'}
                </button>
              </div>
              
              {entityAuditLogs.length > 0 && (
                <ul className="list" style={{ marginTop: '1rem' }} role="list" aria-label="Historia encji">
                  {entityAuditLogs.map(entry => (
                    <li key={entry.id || `${entry.entityType}-${entry.entityId}-${entry.createdAt || entry.timestamp}`}>
                      <div className="list-row">
                        <div>
                          <strong>{entry.action || entry.event || 'Zdarzenie'}</strong>
                          <div className="support-copy">
                            {entry.entityType || entry.entity} #{entry.entityId || entry.entity_id}
                          </div>
                        </div>
                        <time className="support-copy" dateTime={entry.createdAt || entry.timestamp}>
                          {entry.createdAt || entry.timestamp || ''}
                        </time>
                      </div>
                    </li>
                  ))}
                </ul>
              )}
            </div>
            
            <ul className="timeline" role="list" aria-label="Ostatnie zdarzenia audytu">
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
                    <time className="support-copy" dateTime={entry.timestamp || entry.createdAt}>
                      {entry.timestamp || entry.createdAt || ''}
                    </time>
                  </div>
                </li>
              ))}
            </ul>
          </>
        )}
      </section>
    </div>
  )
}
