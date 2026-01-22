import React, { useState } from 'react'

export default function UserManagement({ 
  users, 
  loading, 
  userSearchQuery, 
  setUserSearchQuery, 
  searchUsers, 
  loadUsers,
  setEditingUser, 
  updateUserPermissions, 
  toggleUserBlock, 
  deleteUser,
  editingUser,
  updateUserData
}) {
  const [expandedUserId, setExpandedUserId] = useState(null)
  
  return (
    <div className="surface-card" role="region" aria-labelledby="users-title">
      <div className="section-header">
        <h2 id="users-title">Użytkownicy</h2>
        {!loading && (
          <button 
            className="btn btn-secondary" 
            onClick={loadUsers}
            aria-label="Odśwież listę użytkowników"
          >
            Odśwież
          </button>
        )}
      </div>
      
      <div className="form-field">
        <label htmlFor="user-search">Wyszukaj</label>
        <input
          id="user-search"
          value={userSearchQuery}
          onChange={(e) => {
            const value = e.target.value
            setUserSearchQuery(value)
            searchUsers(value)
          }}
          placeholder="Szukaj po imieniu, emailu lub karcie"
          aria-describedby="user-search-help"
        />
        <small id="user-search-help" className="support-copy">
          Wpisz minimum 2 znaki aby wyszukać
        </small>
      </div>

      {loading && <div aria-live="polite" role="status">Ładowanie...</div>}
      {!loading && users.length === 0 && <p>Brak użytkowników.</p>}

      {!loading && users.length > 0 && (
        <div className="users-list-compact">
          {users.map(user => {
            const isExpanded = expandedUserId === user.id
            return (
              <div key={user.id} className="user-card-compact">
                <div 
                  className="user-card-header"
                  onClick={() => setExpandedUserId(isExpanded ? null : user.id)}
                  style={{ cursor: 'pointer' }}
                >
                  <h3>{user.name || 'Brak nazwy'}</h3>
                  <span className="expand-icon">{isExpanded ? '▼' : '▶'}</span>
                </div>
                
                {isExpanded && (
                  <div className="user-card-details">
                    <div className="user-info-field">
                      <span className="label">Email</span>
                      <span className="value">{user.email}</span>
                    </div>
                    
                    <div className="user-info-row">
                      <div className="user-info-field">
                        <span className="label">Role</span>
                        <span className="value">{Array.isArray(user.roles) ? user.roles.join(', ') : '-'}</span>
                      </div>
                      <div className="user-info-field">
                        <span className="label">Status</span>
                        <span 
                          className={user.blocked ? 'badge badge-danger' : 'badge badge-success'}
                          aria-label={user.blocked ? 'Użytkownik zablokowany' : 'Użytkownik aktywny'}
                        >
                          {user.blocked ? 'Zablokowany' : 'Aktywny'}
                        </span>
                      </div>
                    </div>
                    
                    <div className="user-card-actions">
                      <button 
                        className="btn btn-sm" 
                        onClick={() => setEditingUser(user)}
                        aria-label={`Edytuj użytkownika ${user.name}`}
                      >
                        Edytuj
                      </button>
                      <button 
                        className="btn btn-sm btn-secondary" 
                        onClick={() => updateUserPermissions(user.id, user.roles)}
                        aria-label={`Zmień uprawnienia użytkownika ${user.name}`}
                      >
                        Uprawnienia
                      </button>
                      <button
                        className={`btn btn-sm ${user.blocked ? 'btn-primary' : 'btn-danger'}`}
                        onClick={() => toggleUserBlock(user.id, user.blocked)}
                        aria-label={user.blocked ? `Odblokuj użytkownika ${user.name}` : `Zablokuj użytkownika ${user.name}`}
                      >
                        {user.blocked ? 'Odblokuj' : 'Zablokuj'}
                      </button>
                      <button 
                        className="btn btn-sm btn-danger" 
                        onClick={() => deleteUser(user.id)}
                        aria-label={`Usuń użytkownika ${user.name}`}
                      >
                        Usuń
                      </button>
                    </div>
                  </div>
                )}
              </div>
            )
          })}
        </div>
      )}

      {editingUser && (
        <UserEditModal 
          user={editingUser}
          onClose={() => setEditingUser(null)}
          onSave={(updates) => updateUserData(editingUser.id, updates)}
        />
      )}
    </div>
  )
}

function UserEditModal({ user, onClose, onSave }) {
  const handleSubmit = (e) => {
    e.preventDefault()
    const formData = new FormData(e.target)
    const updates = {
      name: formData.get('name'),
      email: formData.get('email'),
      cardNumber: formData.get('cardNumber'),
      accountStatus: formData.get('accountStatus'),
      roles: Array.from(formData.getAll('roles'))
    }
    onSave(updates)
  }

  return (
    <div className="modal-overlay" onClick={onClose} role="presentation">
      <div 
        className="modal-content" 
        onClick={e => e.stopPropagation()} 
        role="dialog"
        aria-modal="true"
        aria-labelledby="edit-user-title"
      >
        <h3 id="edit-user-title">Edycja użytkownika</h3>
        <form onSubmit={handleSubmit}>
          <div className="user-info-compact">
            <div className="user-info-row">
              <div className="form-field">
                <label htmlFor="edit-name">Użytkownik</label>
                <input 
                  id="edit-name" 
                  type="text" 
                  name="name" 
                  defaultValue={user.name} 
                  required 
                  aria-required="true"
                />
              </div>
              <div className="form-field">
                <label htmlFor="edit-card">Karta</label>
                <input 
                  id="edit-card" 
                  type="text" 
                  name="cardNumber" 
                  defaultValue={user.cardNumber || ''} 
                />
              </div>
            </div>
            <div className="form-field">
              <label htmlFor="edit-email">Email</label>
              <input 
                id="edit-email" 
                type="email" 
                name="email" 
                defaultValue={user.email} 
                required 
                aria-required="true"
              />
            </div>
            <div className="user-info-row">
              <div className="form-field">
                <label htmlFor="edit-status">Status</label>
                <select 
                  id="edit-status" 
                  name="accountStatus" 
                  defaultValue={user.accountStatus || 'Aktywne'}
                >
                  <option value="Aktywne">Aktywne</option>
                  <option value="Zawieszone">Zawieszone</option>
                  <option value="Wygasłe">Wygasłe</option>
                </select>
              </div>
              <fieldset className="form-field">
                <legend>Role</legend>
                <div className="roles-compact">
                  <div className="checkbox-field">
                    <input
                      type="checkbox"
                      id="role_user"
                      name="roles"
                      value="ROLE_USER"
                      defaultChecked={user.roles?.includes('ROLE_USER')}
                    />
                    <label htmlFor="role_user">User</label>
                  </div>
                  <div className="checkbox-field">
                    <input
                      type="checkbox"
                      id="role_librarian"
                      name="roles"
                      value="ROLE_LIBRARIAN"
                      defaultChecked={user.roles?.includes('ROLE_LIBRARIAN')}
                    />
                    <label htmlFor="role_librarian">Librarian</label>
                  </div>
                  <div className="checkbox-field">
                    <input
                      type="checkbox"
                      id="role_admin"
                      name="roles"
                      value="ROLE_ADMIN"
                      defaultChecked={user.roles?.includes('ROLE_ADMIN')}
                    />
                    <label htmlFor="role_admin">Admin</label>
                  </div>
                </div>
              </fieldset>
            </div>
          </div>
          <div className="modal-actions" style={{ marginTop: '1rem' }}>
            <button type="button" className="btn btn-secondary" onClick={onClose}>
              Anuluj
            </button>
            <button type="submit" className="btn btn-primary">
              Zapisz zmiany
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
