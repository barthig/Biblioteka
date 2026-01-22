import { describe, it, expect, vi } from 'vitest'
import { render, screen, fireEvent } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import Navbar from './Navbar'

const prefetchResource = vi.fn().mockResolvedValue({})
let mockAuth = { token: null, user: null, logout: vi.fn() }

vi.mock('../context/AuthContext', () => ({
  useAuth: () => mockAuth
}))

vi.mock('../context/ResourceCacheContext', () => ({
  useResourceCache: () => ({ prefetchResource })
}))

describe('Navbar', () => {
  it('renders login/register when unauthenticated', () => {
    mockAuth = { token: null, user: null, logout: vi.fn() }
    render(
      <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
        <Navbar />
      </MemoryRouter>
    )
    expect(screen.getByRole('link', { name: /Zaloguj/i })).toBeInTheDocument()
    expect(screen.getByRole('link', { name: /Zarejestruj/i })).toBeInTheDocument()
  })

  it('renders user section and triggers prefetch', () => {
    mockAuth = { token: 'token', user: { name: 'Jan', roles: ['ROLE_USER'] }, logout: vi.fn() }
    render(
      <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
        <Navbar />
      </MemoryRouter>
    )
    expect(screen.getByText(/Jan/)).toBeInTheDocument()
    fireEvent.mouseEnter(screen.getByRole('link', { name: /Wypo/i }))
    expect(prefetchResource).toHaveBeenCalled()
  })

  it('shows admin links only for admin', () => {
    mockAuth = { token: 'token', user: { name: 'Admin', roles: ['ROLE_ADMIN'] }, logout: vi.fn() }
    render(
      <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
        <Navbar />
      </MemoryRouter>
    )

    expect(screen.getByRole('link', { name: /Panel administratora/i })).toBeInTheDocument()
    expect(screen.queryByRole('link', { name: /Katalog import/i })).not.toBeInTheDocument()
    expect(screen.queryByRole('link', { name: /Akcesje/i })).not.toBeInTheDocument()
    expect(screen.queryByRole('link', { name: /Pliki ksiazek/i })).not.toBeInTheDocument()
    expect(screen.queryByRole('link', { name: /Logi/i })).not.toBeInTheDocument()
    expect(screen.queryByRole('link', { name: /Panel bibliotekarza/i })).not.toBeInTheDocument()
  })

  it('shows librarian links only for librarian', () => {
    mockAuth = { token: 'token', user: { name: 'Librarian', roles: ['ROLE_LIBRARIAN'] }, logout: vi.fn() }
    render(
      <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
        <Navbar />
      </MemoryRouter>
    )

    expect(screen.getByRole('link', { name: /Panel bibliotekarza/i })).toBeInTheDocument()
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

