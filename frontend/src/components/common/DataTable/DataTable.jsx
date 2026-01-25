import React, { useState, useMemo, useCallback } from 'react'
import PropTypes from 'prop-types'
import './DataTable.css'

/**
 * Universal DataTable component with sorting, pagination, filtering, and actions
 * 
 * @example
 * <DataTable
 *   columns={[
 *     { key: 'title', label: 'Tytuł', sortable: true },
 *     { key: 'author', label: 'Autor', render: (row) => row.author?.name },
 *     { key: 'status', label: 'Status', render: (row) => <StatusBadge status={row.status} /> }
 *   ]}
 *   data={books}
 *   sortable
 *   paginated
 *   pageSize={20}
 *   searchable
 *   searchPlaceholder="Szukaj..."
 *   onRowClick={(row) => navigate(`/books/${row.id}`)}
 *   actions={[
 *     { icon: 'edit', label: 'Edytuj', onClick: (row) => handleEdit(row) },
 *     { icon: 'delete', label: 'Usuń', onClick: (row) => handleDelete(row), variant: 'danger' }
 *   ]}
 *   emptyMessage="Brak danych do wyświetlenia"
 *   loading={isLoading}
 * />
 */
export default function DataTable({
  columns = [],
  data = [],
  sortable = false,
  paginated = false,
  pageSize = 20,
  searchable = false,
  searchPlaceholder = 'Szukaj...',
  searchFields = [],
  onRowClick,
  actions = [],
  emptyMessage = 'Brak danych',
  loading = false,
  className = '',
  striped = true,
  hoverable = true,
  compact = false,
  stickyHeader = false,
  stickyFirstColumn = false,
  mobileCards = true,
  selectable = false,
  selectedRows = [],
  onSelectionChange,
  rowKey = 'id',
  ariaLabel = 'Tabela danych',
  ...props
}) {
  const [sortConfig, setSortConfig] = useState({ key: null, direction: 'asc' })
  const [currentPage, setCurrentPage] = useState(1)
  const [searchQuery, setSearchQuery] = useState('')
  const [internalSelectedRows, setInternalSelectedRows] = useState([])
  
  const selected = selectable ? (onSelectionChange ? selectedRows : internalSelectedRows) : []
  const setSelected = useMemo(() => {
    return selectable ? (onSelectionChange || setInternalSelectedRows) : () => {}
  }, [selectable, onSelectionChange])

  // Filtering
  const filteredData = useMemo(() => {
    if (!searchQuery.trim()) return data
    
    const query = searchQuery.toLowerCase()
    const fields = searchFields.length > 0 
      ? searchFields 
      : columns.filter(c => !c.render).map(c => c.key)
    
    return data.filter(row => {
      return fields.some(field => {
        const value = getNestedValue(row, field)
        return value && String(value).toLowerCase().includes(query)
      })
    })
  }, [data, searchQuery, searchFields, columns])

  // Sorting
  const sortedData = useMemo(() => {
    if (!sortConfig.key) return filteredData
    
    return [...filteredData].sort((a, b) => {
      const aValue = getNestedValue(a, sortConfig.key)
      const bValue = getNestedValue(b, sortConfig.key)
      
      if (aValue === bValue) return 0
      if (aValue == null) return 1
      if (bValue == null) return -1
      
      const comparison = aValue < bValue ? -1 : 1
      return sortConfig.direction === 'asc' ? comparison : -comparison
    })
  }, [filteredData, sortConfig])

  // Pagination
  const paginatedData = useMemo(() => {
    if (!paginated) return sortedData
    
    const start = (currentPage - 1) * pageSize
    return sortedData.slice(start, start + pageSize)
  }, [sortedData, paginated, currentPage, pageSize])

  const totalPages = Math.ceil(sortedData.length / pageSize)

  // Handlers
  const handleSort = useCallback((key) => {
    if (!sortable) return
    
    setSortConfig(prev => ({
      key,
      direction: prev.key === key && prev.direction === 'asc' ? 'desc' : 'asc'
    }))
  }, [sortable])

  const handleSearch = useCallback((e) => {
    setSearchQuery(e.target.value)
    setCurrentPage(1)
  }, [])

  const handleSelectAll = useCallback((e) => {
    if (e.target.checked) {
      setSelected(paginatedData.map(row => row[rowKey]))
    } else {
      setSelected([])
    }
  }, [paginatedData, rowKey, setSelected])

  const handleSelectRow = useCallback((rowId) => {
    setSelected(prev => 
      prev.includes(rowId) 
        ? prev.filter(id => id !== rowId)
        : [...prev, rowId]
    )
  }, [setSelected])

  const getSortIcon = (key) => {
    if (sortConfig.key !== key) return '↕'
    return sortConfig.direction === 'asc' ? '↑' : '↓'
  }

  const tableClasses = [
    'data-table',
    striped && 'data-table--striped',
    hoverable && 'data-table--hoverable',
    compact && 'data-table--compact',
    stickyHeader && 'data-table--sticky-header',
    stickyFirstColumn && 'data-table--sticky-first-col',
    mobileCards && 'data-table--mobile-cards',
    className
  ].filter(Boolean).join(' ')

  if (loading) {
    return (
      <div className="data-table__loading">
        <div className="data-table__spinner" />
        <span>Ładowanie danych...</span>
      </div>
    )
  }

  return (
    <div className="data-table__wrapper" {...props}>
      {searchable && (
        <div className="data-table__search">
          <input
            type="text"
            placeholder={searchPlaceholder}
            value={searchQuery}
            onChange={handleSearch}
            className="data-table__search-input"
          />
          {searchQuery && (
            <button 
              className="data-table__search-clear"
              onClick={() => { setSearchQuery(''); setCurrentPage(1); }}
              aria-label="Wyczyść wyszukiwanie"
            >
              ×
            </button>
          )}
        </div>
      )}

      {paginatedData.length === 0 ? (
        <div className="data-table__empty" role="status">
          <p>{emptyMessage}</p>
        </div>
      ) : (
        <>
          <div className="data-table__container" role="region" aria-label={ariaLabel}>
            <table className={tableClasses} role="table" aria-label={ariaLabel}>
              <thead>
                <tr>
                  {selectable && (
                    <th className="data-table__checkbox-cell">
                      <input
                        type="checkbox"
                        checked={paginatedData.length > 0 && paginatedData.every(row => selected.includes(row[rowKey]))}
                        onChange={handleSelectAll}
                        aria-label="Zaznacz wszystkie"
                      />
                    </th>
                  )}
                  {columns.map(column => (
                    <th 
                      key={column.key}
                      className={[
                        column.sortable !== false && sortable ? 'data-table__sortable' : '',
                        column.align ? `data-table__align-${column.align}` : '',
                        column.width ? '' : ''
                      ].filter(Boolean).join(' ')}
                      style={column.width ? { width: column.width } : undefined}
                      onClick={() => column.sortable !== false && sortable && handleSort(column.key)}
                    >
                      <span>{column.label}</span>
                      {column.sortable !== false && sortable && (
                        <span className="data-table__sort-icon">{getSortIcon(column.key)}</span>
                      )}
                    </th>
                  ))}
                  {actions.length > 0 && (
                    <th className="data-table__actions-header">Akcje</th>
                  )}
                </tr>
              </thead>
              <tbody>
                {paginatedData.map((row, index) => (
                  <tr 
                    key={row[rowKey] ?? index}
                    onClick={() => onRowClick?.(row)}
                    className={[
                      onRowClick ? 'data-table__clickable-row' : '',
                      selected.includes(row[rowKey]) ? 'data-table__selected-row' : ''
                    ].filter(Boolean).join(' ')}
                  >
                    {selectable && (
                      <td className="data-table__checkbox-cell" onClick={e => e.stopPropagation()}>
                        <input
                          type="checkbox"
                          checked={selected.includes(row[rowKey])}
                          onChange={() => handleSelectRow(row[rowKey])}
                          aria-label={`Zaznacz wiersz ${index + 1}`}
                        />
                      </td>
                    )}
                    {columns.map(column => (
                      <td 
                        key={column.key}
                        className={column.align ? `data-table__align-${column.align}` : ''}
                        data-label={column.label}
                      >
                        {column.render 
                          ? column.render(row, index) 
                          : getNestedValue(row, column.key) ?? '—'}
                      </td>
                    ))}
                    {actions.length > 0 && (
                      <td className="data-table__actions-cell" onClick={e => e.stopPropagation()}>
                        <div className="data-table__actions">
                          {actions.map((action, actionIndex) => (
                            <button
                              key={actionIndex}
                              className={`data-table__action-btn data-table__action-btn--${action.variant || 'default'}`}
                              onClick={() => action.onClick(row)}
                              title={action.label}
                              disabled={action.disabled?.(row)}
                            >
                              {action.icon && <span className="data-table__action-icon">{action.icon}</span>}
                              {!action.iconOnly && <span>{action.label}</span>}
                            </button>
                          ))}
                        </div>
                      </td>
                    )}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {paginated && totalPages > 1 && (
            <div className="data-table__pagination">
              <span className="data-table__pagination-info">
                Pokazuję {((currentPage - 1) * pageSize) + 1}–{Math.min(currentPage * pageSize, sortedData.length)} z {sortedData.length}
              </span>
              <div className="data-table__pagination-controls">
                <button
                  className="data-table__pagination-btn"
                  onClick={() => setCurrentPage(1)}
                  disabled={currentPage === 1}
                  aria-label="Pierwsza strona"
                >
                  «
                </button>
                <button
                  className="data-table__pagination-btn"
                  onClick={() => setCurrentPage(p => Math.max(1, p - 1))}
                  disabled={currentPage === 1}
                  aria-label="Poprzednia strona"
                >
                  ‹
                </button>
                <span className="data-table__pagination-pages">
                  Strona {currentPage} z {totalPages}
                </span>
                <button
                  className="data-table__pagination-btn"
                  onClick={() => setCurrentPage(p => Math.min(totalPages, p + 1))}
                  disabled={currentPage === totalPages}
                  aria-label="Następna strona"
                >
                  ›
                </button>
                <button
                  className="data-table__pagination-btn"
                  onClick={() => setCurrentPage(totalPages)}
                  disabled={currentPage === totalPages}
                  aria-label="Ostatnia strona"
                >
                  »
                </button>
              </div>
            </div>
          )}
        </>
      )}
    </div>
  )
}

// Helper function to get nested object values (e.g., 'author.name')
function getNestedValue(obj, path) {
  if (!path) return undefined
  return path.split('.').reduce((acc, part) => acc?.[part], obj)
}

DataTable.propTypes = {
  columns: PropTypes.arrayOf(PropTypes.shape({
    key: PropTypes.string.isRequired,
    label: PropTypes.string.isRequired,
    sortable: PropTypes.bool,
    render: PropTypes.func,
    align: PropTypes.oneOf(['left', 'center', 'right']),
    width: PropTypes.string
  })).isRequired,
  data: PropTypes.array.isRequired,
  sortable: PropTypes.bool,
  paginated: PropTypes.bool,
  pageSize: PropTypes.number,
  searchable: PropTypes.bool,
  searchPlaceholder: PropTypes.string,
  searchFields: PropTypes.arrayOf(PropTypes.string),
  onRowClick: PropTypes.func,
  actions: PropTypes.arrayOf(PropTypes.shape({
    icon: PropTypes.node,
    label: PropTypes.string.isRequired,
    onClick: PropTypes.func.isRequired,
    variant: PropTypes.oneOf(['default', 'primary', 'danger', 'warning']),
    disabled: PropTypes.func,
    iconOnly: PropTypes.bool
  })),
  emptyMessage: PropTypes.string,
  loading: PropTypes.bool,
  className: PropTypes.string,
  striped: PropTypes.bool,
  hoverable: PropTypes.bool,
  compact: PropTypes.bool,
  stickyHeader: PropTypes.bool,
  stickyFirstColumn: PropTypes.bool,
  mobileCards: PropTypes.bool,
  selectable: PropTypes.bool,
  selectedRows: PropTypes.array,
  onSelectionChange: PropTypes.func,
  rowKey: PropTypes.string,
  ariaLabel: PropTypes.string
}
