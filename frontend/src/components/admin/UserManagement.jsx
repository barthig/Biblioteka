import React from 'react'

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
        <div className="table-responsive">
          <table className="table" role="table" aria-label="Lista użytkowników">
            <thead>
              <tr>
                <th scope="col">Użytkownik</th>
                <th scope="col">Email</th>
                <th scope="col">Role</th>
                <th scope="col">Status</th>
                <th scope="col">Akcje</th>
              </tr>
            </thead>
            <tbody>
              {users.map(user => (
                <tr key={user.id}>
                  <td>{user.name || 'Brak nazwy'}</td>
                  <td>{user.email}</td>
                  <td>{Array.isArray(user.roles) ? user.roles.join(', ') : '-'}</td>
                  <td>
                    <span 
                      className={user.blocked ? 'badge badge-danger' : 'badge badge-success'}
                      aria-label={user.blocked ? 'Użytkownik zablokowany' : 'Użytkownik aktywny'}
                    >
                      {user.blocked ? 'Zablokowany' : 'Aktywny'}
                    </span>
                  </td>
                  <td>
                    <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap' }} role="group" aria-label="Akcje dla użytkownika">
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
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
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
        style={{ maxWidth: '600px' }}
        role="dialog"
        aria-modal="true"
        aria-labelledby="edit-user-title"
      >
        <h3 id="edit-user-title">Edycja użytkownika: {user.name}</h3>
        <form onSubmit={handleSubmit}>
          <div className="form-field">
            <label htmlFor="edit-name">Imię i nazwisko</label>
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
          <div className="form-field">
            <label htmlFor="edit-card">Numer karty bibliotecznej</label>
            <input 
              id="edit-card" 
              type="text" 
              name="cardNumber" 
              defaultValue={user.cardNumber || ''} 
            />
          </div>
          <div className="form-field">
            <label htmlFor="edit-status">Status konta</label>
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
            <legend>Role użytkownika</legend>
            <div className="checkbox-field">
              <input
                type="checkbox"
                id="role_user"
                name="roles"
                value="ROLE_USER"
                defaultChecked={user.roles?.includes('ROLE_USER')}
              />
              <label htmlFor="role_user">Użytkownik (ROLE_USER)</label>
            </div>
            <div className="checkbox-field">
              <input
                type="checkbox"
                id="role_librarian"
                name="roles"
                value="ROLE_LIBRARIAN"
                defaultChecked={user.roles?.includes('ROLE_LIBRARIAN')}
              />
              <label htmlFor="role_librarian">Bibliotekarz (ROLE_LIBRARIAN)</label>
            </div>
            <div className="checkbox-field">
              <input
                type="checkbox"
                id="role_admin"
                name="roles"
                value="ROLE_ADMIN"
                defaultChecked={user.roles?.includes('ROLE_ADMIN')}
              />
              <label htmlFor="role_admin">Administrator (ROLE_ADMIN)</label>
            </div>
          </fieldset>
          <div className="modal-actions">
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
