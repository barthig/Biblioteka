import React, { useMemo } from 'react'

export default function LoanManagement({
  loans,
  loading,
  filters,
  setFilters,
  onSearch,
  onReset,
  onEdit,
  onReturn,
  onExtend,
  onDelete,
  editingLoan,
  editForm,
  setEditForm,
  onSaveEdit,
  onCloseEdit
}) {
  const today = useMemo(() => new Date(), [])

  const formatDate = (value) => {
    if (!value) return '-'
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return '-'
    return date.toLocaleDateString('pl-PL')
  }

  const getStatusLabel = (loan) => {
    if (loan?.returnedAt) return 'Zwrócone'
    const due = loan?.dueAt ? new Date(loan.dueAt) : null
    if (due && due < today) return 'Przeterminowane'
    return 'Aktywne'
  }

  return (
    <div className="surface-card" role="region" aria-labelledby="admin-loans-title">
      <div className="section-header">
        <h2 id="admin-loans-title">Wypożyczenia użytkowników</h2>
        <button className="btn btn-secondary" onClick={onSearch} disabled={loading}>
          Odśwież
        </button>
      </div>

      <form
        className="form"
        onSubmit={(event) => {
          event.preventDefault()
          onSearch()
        }}
      >
        <div className="form-row form-row--two">
          <div className="form-field">
            <label htmlFor="loan-user-filter">Użytkownik</label>
            <input
              id="loan-user-filter"
              value={filters.user}
              onChange={(event) => setFilters(prev => ({ ...prev, user: event.target.value }))}
              placeholder="Imię lub e-mail"
            />
          </div>
          <div className="form-field">
            <label htmlFor="loan-book-filter">Książka</label>
            <input
              id="loan-book-filter"
              value={filters.book}
              onChange={(event) => setFilters(prev => ({ ...prev, book: event.target.value }))}
              placeholder="Tytuł książki"
            />
          </div>
        </div>
        <div className="form-row form-row--two">
          <div className="form-field">
            <label htmlFor="loan-status-filter">Status</label>
            <select
              id="loan-status-filter"
              value={filters.status}
              onChange={(event) => setFilters(prev => ({ ...prev, status: event.target.value }))}
            >
              <option value="all">Wszystkie</option>
              <option value="active">Aktywne</option>
              <option value="returned">Zwrócone</option>
              <option value="overdue">Przeterminowane</option>
            </select>
          </div>
          <div className="form-field" style={{ alignSelf: 'flex-end' }}>
            <div className="form-actions">
              <button type="submit" className="btn btn-primary" disabled={loading}>
                Filtruj
              </button>
              <button type="button" className="btn btn-ghost" onClick={onReset} disabled={loading}>
                Wyczyść
              </button>
            </div>
          </div>
        </div>
      </form>

      {loading && <p>Ładowanie...</p>}
      {!loading && loans.length === 0 && <p>Brak wypożyczeń.</p>}

      {!loading && loans.length > 0 && (
        <div className="table-responsive">
          <table className="table" role="table" aria-label="Lista wypożyczeń">
            <thead>
              <tr>
                <th scope="col">Użytkownik</th>
                <th scope="col">Książka</th>
                <th scope="col">Wypożyczono</th>
                <th scope="col">Termin zwrotu</th>
                <th scope="col">Status</th>
                <th scope="col">Akcje</th>
              </tr>
            </thead>
            <tbody>
              {loans.map(loan => (
                <tr key={loan.id}>
                  <td>
                    <div>{loan.user?.name || 'Brak danych'}</div>
                    <div className="support-copy">{loan.user?.email || ''}</div>
                  </td>
                  <td>
                    <div>{loan.book?.title || 'Brak danych'}</div>
                    {loan.bookCopy?.inventoryCode && (
                      <div className="support-copy">Egz.: {loan.bookCopy.inventoryCode}</div>
                    )}
                  </td>
                  <td>{formatDate(loan.borrowedAt)}</td>
                  <td>{formatDate(loan.dueAt)}</td>
                  <td>{getStatusLabel(loan)}</td>
                  <td>
                    <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap' }} role="group" aria-label="Akcje wypożyczenia">
                      <button className="btn btn-sm" type="button" onClick={() => onEdit(loan)}>
                        Edytuj
                      </button>
                      <button
                        className="btn btn-sm btn-secondary"
                        type="button"
                        onClick={() => onExtend(loan)}
                        disabled={!!loan.returnedAt}
                      >
                        Przedłuż
                      </button>
                      <button
                        className="btn btn-sm btn-secondary"
                        type="button"
                        onClick={() => onReturn(loan)}
                        disabled={!!loan.returnedAt}
                      >
                        Zwrot
                      </button>
                      <button className="btn btn-sm btn-danger" type="button" onClick={() => onDelete(loan)}>
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

      {editingLoan && (
        <div className="modal-overlay" onClick={onCloseEdit}>
          <div className="modal-content" onClick={event => event.stopPropagation()} style={{ maxWidth: '640px' }}>
            <h3>Edycja wypożyczenia #{editingLoan.id}</h3>
            <form
              onSubmit={(event) => {
                event.preventDefault()
                onSaveEdit()
              }}
            >
              <div className="form-row form-row--two">
                <div className="form-field">
                  <label htmlFor="loan-edit-dueAt">Termin zwrotu</label>
                  <input
                    id="loan-edit-dueAt"
                    type="date"
                    value={editForm.dueAt}
                    onChange={(event) => setEditForm(prev => ({ ...prev, dueAt: event.target.value }))}
                  />
                </div>
                <div className="form-field">
                  <label htmlFor="loan-edit-status">Status</label>
                  <select
                    id="loan-edit-status"
                    value={editForm.status}
                    onChange={(event) => setEditForm(prev => ({ ...prev, status: event.target.value }))}
                    disabled={!!editingLoan.returnedAt}
                  >
                    <option value="active">Aktywne</option>
                    <option value="returned">Zwrócone</option>
                  </select>
                </div>
              </div>
              <div className="form-row form-row--two">
                <div className="form-field">
                  <label htmlFor="loan-edit-book">ID książki (opcjonalnie)</label>
                  <input
                    id="loan-edit-book"
                    type="number"
                    value={editForm.bookId}
                    onChange={(event) => setEditForm(prev => ({ ...prev, bookId: event.target.value }))}
                  />
                </div>
                <div className="form-field">
                  <label htmlFor="loan-edit-copy">ID egzemplarza (opcjonalnie)</label>
                  <input
                    id="loan-edit-copy"
                    type="number"
                    value={editForm.bookCopyId}
                    onChange={(event) => setEditForm(prev => ({ ...prev, bookCopyId: event.target.value }))}
                  />
                </div>
              </div>
              <div className="form-actions">
                <button type="button" className="btn btn-secondary" onClick={onCloseEdit}>
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
  )
}
