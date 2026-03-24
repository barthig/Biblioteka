import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router-dom'

let mockUser = null

vi.mock('../../../src/context/AuthContext', () => ({
  useAuth: () => ({ user: mockUser })
}))

vi.mock('../../../src/pages/admin/AdminPanel', () => ({
  default: () => <div>Admin Section</div>
}))

vi.mock('../../../src/pages/admin/LibrarianPanel', () => ({
  default: () => <div>Operations Section</div>
}))

import StaffPanel from '../../../src/pages/admin/StaffPanel'

function renderStaffPanel(initialEntry = '/staff') {
  return render(
    <MemoryRouter initialEntries={[initialEntry]} future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
      <StaffPanel />
    </MemoryRouter>
  )
}

describe('StaffPanel', () => {
  beforeEach(() => {
    mockUser = null
  })

  it('shows only operations section for librarian', async () => {
    mockUser = { id: 1, roles: ['ROLE_LIBRARIAN'] }

    renderStaffPanel('/staff?section=admin')

    expect(screen.getByRole('heading', { name: /Panel personelu/i })).toBeInTheDocument()
    expect(screen.getByRole('tab', { name: /Obsługa biblioteki/i })).toBeInTheDocument()
    expect(screen.queryByRole('tab', { name: /Administracja/i })).not.toBeInTheDocument()
    expect(screen.getByText('Operations Section')).toBeInTheDocument()
    expect(screen.queryByText('Admin Section')).not.toBeInTheDocument()
  })

  it('defaults to admin section on admin route for administrator', () => {
    mockUser = { id: 2, roles: ['ROLE_ADMIN'] }

    renderStaffPanel('/admin')

    expect(screen.getByText('Admin Section')).toBeInTheDocument()
    expect(screen.queryByText('Operations Section')).not.toBeInTheDocument()
  })

  it('allows admin to switch between sections', async () => {
    mockUser = { id: 3, roles: ['ROLE_ADMIN'] }

    renderStaffPanel('/staff?section=operations')

    expect(screen.getByText('Operations Section')).toBeInTheDocument()
    await userEvent.click(screen.getByRole('tab', { name: /Administracja/i }))
    expect(screen.getByText('Admin Section')).toBeInTheDocument()
  })

  it('shows forbidden feedback for non-staff user', () => {
    mockUser = { id: 4, roles: ['ROLE_USER'] }

    renderStaffPanel('/staff')

    expect(screen.getByText(/Brak uprawnień do panelu personelu/i)).toBeInTheDocument()
  })
})
