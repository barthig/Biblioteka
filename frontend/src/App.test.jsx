import { describe, it, expect, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import App from './App'

vi.mock('./components/Navbar', () => ({
  default: () => <div>Navbar</div>
}))

vi.mock('./context/AuthContext', () => ({
  AuthProvider: ({ children }) => <div data-testid="auth-provider">{children}</div>
}))

vi.mock('./context/ResourceCacheContext', () => ({
  ResourceCacheProvider: ({ children }) => <div data-testid="cache-provider">{children}</div>
}))

vi.mock('./components/RequireRole', () => ({
  default: ({ children }) => <div>{children}</div>
}))

vi.mock('./pages/Dashboard', () => ({ default: () => <div>Dashboard Page</div> }))
vi.mock('./pages/Books', () => ({ default: () => <div>Books Page</div> }))
vi.mock('./pages/BookDetails', () => ({ default: () => <div>Book Details</div> }))
vi.mock('./pages/Recommended', () => ({ default: () => <div>Recommended Page</div> }))
vi.mock('./pages/SemanticSearchPage', () => ({ default: () => <div>Semantic Search</div> }))
vi.mock('./pages/Announcements', () => ({ default: () => <div>Announcements Page</div> }))
vi.mock('./pages/MyLoans', () => ({ default: () => <div>My Loans</div> }))
vi.mock('./pages/Reservations', () => ({ default: () => <div>Reservations</div> }))
vi.mock('./pages/Favorites', () => ({ default: () => <div>Favorites</div> }))
vi.mock('./pages/Notifications', () => ({ default: () => <div>Notifications</div> }))
vi.mock('./pages/Login', () => ({ default: () => <div>Login Page</div> }))
vi.mock('./pages/Register', () => ({ default: () => <div>Register Page</div> }))
vi.mock('./pages/Profile', () => ({ default: () => <div>Profile Page</div> }))
vi.mock('./pages/AdminPanel', () => ({ default: () => <div>Admin Panel</div> }))
vi.mock('./pages/LibrarianPanel', () => ({ default: () => <div>Librarian Panel</div> }))
vi.mock('./pages/UserDetails', () => ({ default: () => <div>User Details</div> }))
vi.mock('./pages/Reports', () => ({ default: () => <div>Reports</div> }))
vi.mock('./pages/DigitalAssets', () => ({ default: () => <div>Digital Assets</div> }))
vi.mock('./pages/CatalogAdmin', () => ({ default: () => <div>Catalog Admin</div> }))
vi.mock('./pages/Acquisitions', () => ({ default: () => <div>Acquisitions</div> }))
vi.mock('./pages/SystemLogs', () => ({ default: () => <div>System Logs</div> }))

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

