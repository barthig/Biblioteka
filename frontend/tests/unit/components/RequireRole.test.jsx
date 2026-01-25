import { describe, it, expect, beforeEach, vi } from 'vitest'
import { MemoryRouter, Routes, Route } from 'react-router-dom'
import { render, screen } from '@testing-library/react'

let mockUser = null

vi.mock('../../../src/context/AuthContext', () => ({
  useAuth: () => ({ user: mockUser })
}))

// Import after mock is set up
import RequireRole from '../../../src/components/common/RequireRole'

const renderWithRoutes = (ui, initialPath = '/secure') => {
  return render(
    <MemoryRouter
      initialEntries={[initialPath]}
      future={{
        v7_startTransition: true,
        v7_relativeSplatPath: true
      }}
    >
      <Routes>
        <Route path="/secure" element={ui} />
        <Route path="/login" element={<div>Login Page</div>} />
      </Routes>
    </MemoryRouter>
  )
}

describe('RequireRole', () => {
  beforeEach(() => {
    mockUser = null
  })

  it('redirects to login when no user is present', () => {
    renderWithRoutes(
      <RequireRole allowed={['ROLE_LIBRARIAN']}>
        <div>Secret</div>
      </RequireRole>
    )

    expect(screen.getByText(/login page/i)).toBeInTheDocument()
  })

  it('shows access denied when role is missing', () => {
    mockUser = { roles: ['ROLE_USER'] }

    renderWithRoutes(
      <RequireRole allowed={['ROLE_LIBRARIAN']}>
        <div>Secret</div>
      </RequireRole>
    )

    expect(screen.getByText(/Brak dost/)).toBeInTheDocument()
  })

  it('renders children when role is allowed', () => {
    mockUser = { roles: ['ROLE_LIBRARIAN'] }

    renderWithRoutes(
      <RequireRole allowed={['ROLE_LIBRARIAN']}>
        <div>Secret Content</div>
      </RequireRole>
    )

    expect(screen.getByText(/Secret Content/)).toBeInTheDocument()
  })
})

