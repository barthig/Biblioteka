import { describe, it, expect, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'

vi.mock('../../src/components/common/Navbar', () => ({
  default: () => <div>Navbar</div>
}))

vi.mock('../../src/context/AuthContext', () => ({
  AuthProvider: ({ children }) => <div data-testid="auth-provider">{children}</div>,
  useAuth: () => ({ user: null, token: null })
}))

vi.mock('../../src/context/ResourceCacheContext', () => ({
  ResourceCacheProvider: ({ children }) => <div data-testid="cache-provider">{children}</div>
}))

vi.mock('../../src/components/common/RequireRole', () => ({
  default: ({ children }) => <div>{children}</div>
}))

vi.mock('../../src/pages/dashboard/Dashboard', () => ({ default: () => <div>Dashboard Page</div> }))
vi.mock('../../src/pages/books/Books', () => ({ default: () => <div>Books Page</div> }))
vi.mock('../../src/pages/books/BookDetails', () => ({ default: () => <div>Book Details</div> }))
vi.mock('../../src/pages/dashboard/Recommended', () => ({ default: () => <div>Recommended Page</div> }))
vi.mock('../../src/pages/books/SemanticSearchPage', () => ({ default: () => <div>Semantic Search</div> }))
vi.mock('../../src/pages/books/Announcements', () => ({ default: () => <div>Announcements Page</div> }))
vi.mock('../../src/pages/loans/MyLoans', () => ({ default: () => <div>My Loans</div> }))
vi.mock('../../src/pages/loans/Reservations', () => ({ default: () => <div>Reservations</div> }))
vi.mock('../../src/pages/user/Favorites', () => ({ default: () => <div>Favorites</div> }))
vi.mock('../../src/pages/user/Notifications', () => ({ default: () => <div>Notifications</div> }))
vi.mock('../../src/pages/auth/Login', () => ({ default: () => <div>Login Page</div> }))
vi.mock('../../src/pages/auth/Register', () => ({ default: () => <div>Register Page</div> }))
vi.mock('../../src/pages/user/Profile', () => ({ default: () => <div>Profile Page</div> }))
vi.mock('../../src/pages/admin/AdminPanel', () => ({ default: () => <div>Admin Panel</div> }))
vi.mock('../../src/pages/admin/LibrarianPanel', () => ({ default: () => <div>Librarian Panel</div> }))
vi.mock('../../src/pages/user/UserDetails', () => ({ default: () => <div>User Details</div> }))
vi.mock('../../src/pages/admin/Reports', () => ({ default: () => <div>Reports</div> }))
vi.mock('../../src/pages/books/DigitalAssets', () => ({ default: () => <div>Digital Assets</div> }))
vi.mock('../../src/pages/admin/CatalogAdmin', () => ({ default: () => <div>Catalog Admin</div> }))
vi.mock('../../src/pages/books/Acquisitions', () => ({ default: () => <div>Acquisitions</div> }))
vi.mock('../../src/pages/admin/SystemLogs', () => ({ default: () => <div>System Logs</div> }))

import App from '../../src/App'

describe('App routing', () => {
  it('renders dashboard route', () => {
    render(
      <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }} initialEntries={['/']}>
        <App />
      </MemoryRouter>
    )
    expect(screen.getByText('Navbar')).toBeInTheDocument()
    expect(screen.getByText('Dashboard Page')).toBeInTheDocument()
  })

  it('renders books route', () => {
    render(
      <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }} initialEntries={['/books']}>
        <App />
      </MemoryRouter>
    )
    expect(screen.getByText('Books Page')).toBeInTheDocument()
  })
})


