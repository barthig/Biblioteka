import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen, fireEvent } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'

let mockAuth = { token: null, user: null, logout: vi.fn() }

vi.mock('../../../src/context/AuthContext', () => ({
  useAuth: () => mockAuth
}))

// Import after mocks
import Navbar from '../../../src/components/common/Navbar'

describe('Navbar', () => {
  beforeEach(() => {
    mockAuth = { token: null, user: null, logout: vi.fn() }
  })

  it('renders login/register when unauthenticated', () => {
    render(
      <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
        <Navbar />
      </MemoryRouter>
    )
    expect(screen.getByRole('link', { name: /Zaloguj/i })).toBeInTheDocument()
    expect(screen.getByRole('link', { name: /Zarejestruj/i })).toBeInTheDocument()
  })

  it('renders user section and closes mobile menu after navigation click', () => {
    mockAuth = { token: 'token', user: { name: 'Jan', roles: ['ROLE_USER'] }, logout: vi.fn() }
    render(
      <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
        <Navbar />
      </MemoryRouter>
    )
    expect(screen.getByText(/Jan/)).toBeInTheDocument()
    fireEvent.click(screen.getByRole('button', { name: /Menu/i }))
    expect(screen.getByRole('button', { name: /Menu/i })).toHaveAttribute('aria-expanded', 'true')
    fireEvent.click(screen.getByRole('link', { name: /Wypo/i }))
    expect(screen.getByRole('button', { name: /Menu/i })).toHaveAttribute('aria-expanded', 'false')
  })

  it('shows staff link for admin', () => {
    mockAuth = { token: 'token', user: { name: 'Admin', roles: ['ROLE_ADMIN'] }, logout: vi.fn() }
    render(
      <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
        <Navbar />
      </MemoryRouter>
    )

    expect(screen.getByRole('link', { name: /Panel personelu/i })).toBeInTheDocument()
    expect(screen.getByRole('link', { name: /Panel personelu/i })).toHaveAttribute('href', '/staff?section=admin')
  })

  it('shows staff and reports links for librarian', () => {
    mockAuth = { token: 'token', user: { name: 'Librarian', roles: ['ROLE_LIBRARIAN'] }, logout: vi.fn() }
    render(
      <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
        <Navbar />
      </MemoryRouter>
    )

    expect(screen.getByRole('link', { name: /Panel personelu/i })).toBeInTheDocument()
    expect(screen.getByRole('link', { name: /Panel personelu/i })).toHaveAttribute('href', '/staff?section=operations')
    expect(screen.getByRole('link', { name: /Raporty/i })).toBeInTheDocument()
    expect(screen.queryByRole('link', { name: /Panel administratora/i })).not.toBeInTheDocument()
  })

  it('calls logout', () => {
    const logout = vi.fn()
    mockAuth = { token: 'token', user: { name: 'Jan', roles: ['ROLE_USER'] }, logout }
    render(
      <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
        <Navbar />
      </MemoryRouter>
    )
    fireEvent.click(screen.getByRole('button', { name: /Wyloguj/i }))
    expect(logout).toHaveBeenCalled()
  })
})
