import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import LoanManagement from '../../../../src/LoanManagement'

describe('LoanManagement', () => {
  const baseProps = {
    loans: [],
    loading: false,
    filters: { user: '', book: '', status: 'all' },
    setFilters: vi.fn(),
    onSearch: vi.fn(),
    onReset: vi.fn(),
    onEdit: vi.fn(),
    onReturn: vi.fn(),
    onExtend: vi.fn(),
    onDelete: vi.fn(),
    editingLoan: null,
    editForm: { dueAt: '', status: 'active', bookId: '', bookCopyId: '' },
    setEditForm: vi.fn(),
    onSaveEdit: vi.fn(),
    onCloseEdit: vi.fn()
  }

  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('triggers search and reset actions', async () => {
    render(<LoanManagement {...baseProps} />)
    await userEvent.click(screen.getByRole('button', { name: /Od/i }))
    await userEvent.click(screen.getByRole('button', { name: /Wycz/i }))

    expect(baseProps.onSearch).toHaveBeenCalled()
    expect(baseProps.onReset).toHaveBeenCalled()
  })

  it('calls action handlers for loan row', async () => {
    const props = {
      ...baseProps,
      loans: [
        { id: 1, user: { name: 'Jan' }, book: { title: 'Book' }, borrowedAt: '2025-01-01', dueAt: '2025-01-10' }
      ]
    }
    render(<LoanManagement {...props} />)

    await userEvent.click(screen.getByRole('button', { name: /Edytuj/i }))
    await userEvent.click(screen.getByRole('button', { name: /Przed/i }))
    await userEvent.click(screen.getByRole('button', { name: /Zwrot/i }))
    await userEvent.click(screen.getByRole('button', { name: /Usu/i }))

    expect(props.onEdit).toHaveBeenCalled()
    expect(props.onExtend).toHaveBeenCalled()
    expect(props.onReturn).toHaveBeenCalled()
    expect(props.onDelete).toHaveBeenCalled()
  })

  it('submits edit modal', async () => {
    const props = {
      ...baseProps,
      editingLoan: { id: 2, returnedAt: null },
      editForm: { dueAt: '2025-01-01', status: 'active', bookId: '', bookCopyId: '' }
    }
    render(<LoanManagement {...props} />)

    await userEvent.click(screen.getByRole('button', { name: /Zapisz/i }))
    expect(props.onSaveEdit).toHaveBeenCalled()
  })
})

