import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import Dashboard from './Dashboard'
import { apiFetch } from '../api'

const mockNavigate = vi.fn()
const getCachedResource = vi.fn().mockReturnValue(null)
const setCachedResource = vi.fn()
const prefetchResource = vi.fn().mockResolvedValue({})
let mockAuth = { token: null, user: null }

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

vi.mock('react-router-dom', async () => {
  const actual = await vi.importActual('react-router-dom')
  return { ...actual, useNavigate: () => mockNavigate }
})

vi.mock('../context/AuthContext', () => ({
  useAuth: () => mockAuth
}))

vi.mock('../context/ResourceCacheContext', () => ({
  useResourceCache: () => ({ getCachedResource, setCachedResource, prefetchResource })
}))

vi.mock('../components/OnboardingModal', () => ({
  default: () => <div data-testid="onboarding-modal" />
}))

vi.mock('../components/UserRecommendations', () => ({
  default: () => <div data-testid="user-recommendations" />
}))

describe('Dashboard page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders landing page when unauthenticated', async () => {
    mockAuth = { token: null, user: null }
    render(
      <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
        <Dashboard />
      </MemoryRouter>
    )
    expect(await screen.findByText(/Znajd/i)).toBeInTheDocument()
    expect(screen.getByRole('link', { name: /Zobacz/i })).toBeInTheDocument()
  })

  it('renders user dashboard when authenticated', async () => {
    mockAuth = { token: 'token', user: { id: 1, name: 'Jan', roles: [], onboardingCompleted: true } }
    apiFetch.mockImplementation((endpoint) => {
      if (endpoint === '/api/dashboard') {
        return Promise.resolve({
          activeLoans: 2,
          activeReservations: 1,
          favoritesCount: 3
        })
      }
      if (endpoint === '/api/alerts') {
        return Promise.resolve([{ type: 'due_soon', title: 'Alert title', message: 'Alert message', action: { link: '/my-loans', label: 'Go' } }])
      }
      if (endpoint === '/api/library/hours') {
        return Promise.resolve({ Monday: '10-18' })
      }
      return Promise.resolve({})
    })

    render(
      <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
        <Dashboard />
      </MemoryRouter>
    )

    expect(await screen.findByText(/Witaj/i)).toBeInTheDocument()
    expect(screen.getByText('Alert title')).toBeInTheDocument()
    expect(screen.getByTestId('user-recommendations')).toBeInTheDocument()
  })

  it('renders admin dashboard', async () => {
    mockAuth = { token: 'token', user: { id: 2, name: 'Admin', roles: ['ROLE_ADMIN'] } }
    apiFetch.mockImplementation((endpoint) => {
      if (endpoint === '/api/dashboard') {
        return Promise.resolve({ activeUsers: 1, serverLoad: 10, transactionsToday: 2 })
      }
      if (endpoint === '/api/alerts') {
        return Promise.resolve([])
      }
      if (endpoint === '/api/library/hours') {
        return Promise.resolve(null)
      }
      return Promise.resolve({})
    })

    render(
      <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
        <Dashboard />
      </MemoryRouter>
    )

    expect(await screen.findByText(/Panel Administratora/i)).toBeInTheDocument()
  })
})

