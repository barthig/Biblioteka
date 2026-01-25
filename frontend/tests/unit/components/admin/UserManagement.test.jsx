import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import UserManagement from '../../../../src/components/admin/UserManagement'

describe('UserManagement', () => {
  const baseProps = {
    users: [],
    loading: false,
    userSearchQuery: '',
    setUserSearchQuery: vi.fn(),
    searchUsers: vi.fn(),
    loadUsers: vi.fn(),
    setEditingUser: vi.fn(),
    updateUserPermissions: vi.fn(),
    toggleUserBlock: vi.fn(),
    deleteUser: vi.fn(),
    editingUser: null,
    updateUserData: vi.fn()
  }

  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('triggers search on input change', async () => {
    render(<UserManagement {...baseProps} />)
    const input = screen.getByLabelText(/Wyszukaj/i)
    await userEvent.type(input, 'ja')

    expect(baseProps.setUserSearchQuery).toHaveBeenCalled()
    expect(baseProps.searchUsers).toHaveBeenCalled()
  })

  it('calls action handlers for user row', async () => {
    const props = {
      ...baseProps,
      users: [{ id: 1, name: 'Jan', email: 'jan@example.com', roles: ['ROLE_USER'], blocked: false }]
    }
    render(<UserManagement {...props} />)

    // First expand the user card by clicking on the header
    await userEvent.click(screen.getByText('Jan'))

    // Now the action buttons should be visible
    await userEvent.click(screen.getByRole('button', { name: /Edytuj/i }))
    await userEvent.click(screen.getByRole('button', { name: /Uprawnienia/i }))
    await userEvent.click(screen.getByRole('button', { name: /Zablokuj/i }))
    await userEvent.click(screen.getByRole('button', { name: /Usu/i }))

    expect(props.setEditingUser).toHaveBeenCalled()
    expect(props.updateUserPermissions).toHaveBeenCalled()
    expect(props.toggleUserBlock).toHaveBeenCalled()
    expect(props.deleteUser).toHaveBeenCalled()
  })

  it('submits edit modal', async () => {
    const props = {
      ...baseProps,
      users: [{ id: 2, name: 'Anna', email: 'anna@example.com', roles: ['ROLE_USER'], blocked: false }],
      editingUser: { id: 2, name: 'Anna', email: 'anna@example.com', roles: ['ROLE_USER'] }
    }
    render(<UserManagement {...props} />)

    await userEvent.click(screen.getByRole('button', { name: /Zapisz zmiany/i }))
    expect(props.updateUserData).toHaveBeenCalled()
  })
})

