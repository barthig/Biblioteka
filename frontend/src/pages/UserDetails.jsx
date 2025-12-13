import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { apiFetch } from '../api';
import './UserDetails.css';

const UserDetails = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const { token } = useAuth();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [userDetails, setUserDetails] = useState(null);
  const [editMode, setEditMode] = useState(false);
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phoneNumber: '',
    addressLine: '',
    city: '',
    postalCode: '',
    pesel: '',
    cardNumber: '',
    roles: []
  });

  useEffect(() => {
    fetchUserDetails();
  }, [id]);

  const fetchUserDetails = async () => {
    try {
      setLoading(true);
      const response = await apiFetch(`/api/users/${id}/details`);
      setUserDetails(response);
      setFormData({
        name: response.user.name || '',
        email: response.user.email || '',
        phoneNumber: response.user.phoneNumber || '',
        addressLine: response.user.addressLine || '',
        city: response.user.city || '',
        postalCode: response.user.postalCode || '',
        pesel: response.user.pesel || '',
        cardNumber: response.user.cardNumber || '',
        roles: response.user.roles || ['ROLE_USER']
      });
      setError('');
    } catch (err) {
      setError(err.message || 'Błąd podczas ładowania danych użytkownika');
      console.error('Error fetching user details:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleRoleChange = (role) => {
    setFormData(prev => {
      const currentRoles = prev.roles || [];
      const hasRole = currentRoles.includes(role);
      
      if (hasRole) {
        // Usuń rolę
        return { ...prev, roles: currentRoles.filter(r => r !== role) };
      } else {
        // Dodaj rolę
        return { ...prev, roles: [...currentRoles, role] };
      }
    });
  };

  const handleSaveUser = async () => {
    try {
      await apiFetch(`/api/users/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
      });
      setEditMode(false);
      fetchUserDetails();
      alert('Dane użytkownika zostały zaktualizowane');
    } catch (err) {
      alert(err.message || 'Błąd podczas zapisywania danych');
      console.error('Error updating user:', err);
    }
  };

  const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('pl-PL');
  };

  const formatCurrency = (amount) => {
    return `${parseFloat(amount).toFixed(2)} PLN`;
  };

  if (loading) {
    return <div className="user-details-loading">Ładowanie...</div>;
  }

  if (error) {
    return (
      <div className="user-details-error">
        <h2>Błąd</h2>
        <p>{error}</p>
        <button onClick={() => navigate('/admin/users')}>Powrót do listy użytkowników</button>
      </div>
    );
  }

  if (!userDetails) {
    return <div>Nie znaleziono użytkownika</div>;
  }

  const { user, activeLoans, loanHistory, activeFines, paidFines, statistics } = userDetails;

  return (
    <div className="user-details-container">
      <div className="user-details-header">
        <h1>Szczegóły użytkownika</h1>
        <button className="btn-back" onClick={() => navigate('/admin/users')}>
          ← Powrót do listy
        </button>
      </div>

      {/* Podstawowe informacje */}
      <div className="user-info-card">
        <div className="card-header">
          <h2>Dane osobowe</h2>
          {!editMode ? (
            <button className="btn-edit" onClick={() => setEditMode(true)}>Edytuj</button>
          ) : (
            <div className="edit-actions">
              <button className="btn-save" onClick={handleSaveUser}>Zapisz</button>
              <button className="btn-cancel" onClick={() => {
                setEditMode(false);
                setFormData({
                  name: user.name || '',
                  email: user.email || '',
                  phoneNumber: user.phoneNumber || '',
                  addressLine: user.addressLine || '',
                  city: user.city || '',
                  postalCode: user.postalCode || '',
                  pesel: user.pesel || '',
                  cardNumber: user.cardNumber || '',
                  roles: user.roles || ['ROLE_USER']
                });
              }}>Anuluj</button>
            </div>
          )}
        </div>

        <div className="user-info-grid">
          <div className="info-item">
            <label>Imię i nazwisko:</label>
            {editMode ? (
              <input
                type="text"
                name="name"
                value={formData.name}
                onChange={handleInputChange}
              />
            ) : (
              <span>{user.name}</span>
            )}
          </div>

          <div className="info-item">
            <label>Email:</label>
            {editMode ? (
              <input
                type="email"
                name="email"
                value={formData.email}
                onChange={handleInputChange}
              />
            ) : (
              <span>{user.email}</span>
            )}
          </div>

          <div className="info-item">
            <label>Telefon:</label>
            {editMode ? (
              <input
                type="text"
                name="phoneNumber"
                value={formData.phoneNumber}
                onChange={handleInputChange}
                maxLength={30}
              />
            ) : (
              <span>{user.phoneNumber || '-'}</span>
            )}
          </div>

          <div className="info-item">
            <label>PESEL:</label>
            {editMode ? (
              <input
                type="text"
                name="pesel"
                value={formData.pesel}
                onChange={handleInputChange}
                maxLength={11}
                pattern="[0-9]{11}"
              />
            ) : (
              <span>{user.pesel || '-'}</span>
            )}
          </div>

          <div className="info-item">
            <label>Numer karty:</label>
            {editMode ? (
              <input
                type="text"
                name="cardNumber"
                value={formData.cardNumber}
                onChange={handleInputChange}
                maxLength={20}
              />
            ) : (
              <span>{user.cardNumber || '-'}</span>
            )}
          </div>

          <div className="info-item">
            <label>Adres:</label>
            {editMode ? (
              <input
                type="text"
                name="addressLine"
                value={formData.addressLine}
                onChange={handleInputChange}
                maxLength={255}
              />
            ) : (
              <span>{user.addressLine || '-'}</span>
            )}
          </div>

          <div className="info-item">
            <label>Miasto:</label>
            {editMode ? (
              <input
                type="text"
                name="city"
                value={formData.city}
                onChange={handleInputChange}
                maxLength={120}
              />
            ) : (
              <span>{user.city || '-'}</span>
            )}
          </div>

          <div className="info-item">
            <label>Kod pocztowy:</label>
            {editMode ? (
              <input
                type="text"
                name="postalCode"
                value={formData.postalCode}
                onChange={handleInputChange}
                maxLength={12}
                pattern="[0-9]{2}-[0-9]{3}"
              />
            ) : (
              <span>{user.postalCode || '-'}</span>
            )}
          </div>

          <div className="info-item">
            <label>Role:</label>
            {editMode ? (
              <div className="roles-checkboxes">
                <label className="role-checkbox">
                  <input
                    type="checkbox"
                    checked={formData.roles?.includes('ROLE_USER')}
                    onChange={() => handleRoleChange('ROLE_USER')}
                  />
                  <span>Użytkownik</span>
                </label>
                <label className="role-checkbox">
                  <input
                    type="checkbox"
                    checked={formData.roles?.includes('ROLE_LIBRARIAN')}
                    onChange={() => handleRoleChange('ROLE_LIBRARIAN')}
                  />
                  <span>Bibliotekarz</span>
                </label>
                <label className="role-checkbox">
                  <input
                    type="checkbox"
                    checked={formData.roles?.includes('ROLE_ADMIN')}
                    onChange={() => handleRoleChange('ROLE_ADMIN')}
                  />
                  <span>Administrator</span>
                </label>
              </div>
            ) : (
              <span>{user.roles?.join(', ') || 'ROLE_USER'}</span>
            )}
          </div>

          <div className="info-item">
            <label>Status:</label>
            <span className={user.blocked ? 'status-blocked' : 'status-active'}>
              {user.blocked ? 'Zablokowany' : 'Aktywny'}
            </span>
          </div>
        </div>
      </div>

      {/* Statystyki */}
      <div className="statistics-grid">
        <div className="stat-card">
          <h3>Łącznie wypożyczeń</h3>
          <div className="stat-value">{statistics.totalLoans}</div>
        </div>
        <div className="stat-card">
          <h3>Aktywne wypożyczenia</h3>
          <div className="stat-value">{statistics.activeLoansCount}</div>
        </div>
        <div className="stat-card">
          <h3>Aktywne kary</h3>
          <div className="stat-value">{statistics.activeFinesCount}</div>
        </div>
        <div className="stat-card">
          <h3>Suma kar</h3>
          <div className="stat-value">{formatCurrency(statistics.totalFineAmount || 0)}</div>
        </div>
      </div>

      {/* Aktywne wypożyczenia */}
      <div className="loans-card">
        <h2>Aktywne wypożyczenia ({activeLoans.length})</h2>
        {activeLoans.length === 0 ? (
          <p className="no-data">Brak aktywnych wypożyczeń</p>
        ) : (
          <table className="data-table">
            <thead>
              <tr>
                <th>Książka</th>
                <th>Data wypożyczenia</th>
                <th>Termin zwrotu</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              {activeLoans.map(loan => (
                <tr key={loan.id} className={new Date(loan.dueAt) < new Date() ? 'overdue' : ''}>
                  <td>{loan.bookCopy?.book?.title || 'Brak tytułu'}</td>
                  <td>{formatDate(loan.borrowedAt)}</td>
                  <td>{formatDate(loan.dueAt)}</td>
                  <td>
                    {new Date(loan.dueAt) < new Date() ? (
                      <span className="status-overdue">Przetrzymanie</span>
                    ) : (
                      <span className="status-active">Aktywne</span>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      {/* Aktywne kary */}
      {activeFines.length > 0 && (
        <div className="fines-card">
          <h2>Aktywne kary ({activeFines.length})</h2>
          <table className="data-table">
            <thead>
              <tr>
                <th>Powód</th>
                <th>Kwota</th>
                <th>Data wystawienia</th>
              </tr>
            </thead>
            <tbody>
              {activeFines.map(fine => (
                <tr key={fine.id}>
                  <td>{fine.reason}</td>
                  <td>{formatCurrency(fine.amount)}</td>
                  <td>{formatDate(fine.createdAt)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Historia wypożyczeń */}
      <div className="history-card">
        <h2>Historia wypożyczeń (ostatnie {loanHistory.length})</h2>
        {loanHistory.length === 0 ? (
          <p className="no-data">Brak historii wypożyczeń</p>
        ) : (
          <table className="data-table">
            <thead>
              <tr>
                <th>Książka</th>
                <th>Wypożyczono</th>
                <th>Termin zwrotu</th>
                <th>Zwrócono</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              {loanHistory.map(loan => (
                <tr key={loan.id}>
                  <td>{loan.bookCopy?.book?.title || 'Brak tytułu'}</td>
                  <td>{formatDate(loan.borrowedAt)}</td>
                  <td>{formatDate(loan.dueAt)}</td>
                  <td>{formatDate(loan.returnedAt)}</td>
                  <td>
                    {loan.returnedAt ? (
                      loan.returnedAt > loan.dueAt ? (
                        <span className="status-late">Zwrócono z opóźnieniem</span>
                      ) : (
                        <span className="status-returned">Zwrócono</span>
                      )
                    ) : (
                      <span className="status-active">Aktywne</span>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      {/* Opłacone kary */}
      {paidFines.length > 0 && (
        <div className="paid-fines-card">
          <h2>Opłacone kary ({paidFines.length})</h2>
          <table className="data-table">
            <thead>
              <tr>
                <th>Powód</th>
                <th>Kwota</th>
                <th>Wystawiono</th>
                <th>Opłacono</th>
              </tr>
            </thead>
            <tbody>
              {paidFines.map(fine => (
                <tr key={fine.id}>
                  <td>{fine.reason}</td>
                  <td>{formatCurrency(fine.amount)}</td>
                  <td>{formatDate(fine.createdAt)}</td>
                  <td>{formatDate(fine.paidAt)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
};

export default UserDetails;
