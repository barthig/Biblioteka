import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router-dom'
import Profile from './Profile'
import { apiFetch } from '../api'
import { ratingService } from '../services/ratingService'

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

vi.mock('../services/ratingService', () => ({
  ratingService: {
    getMyRatings: vi.fn(),
    deleteRating: vi.fn()
  }
}))

let mockUser = null
vi.mock('../context/AuthContext', () => ({
  useAuth: () => ({
    user: mockUser,
    refreshSession: vi.fn(),
    logoutAll: vi.fn(),
    fetchAuthProfile: vi.fn()
  })
}))

describe('Profile page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('asks user to log in when not authenticated', () => {
    mockUser = null
    render(
      <MemoryRouter>
        <Profile />
      </MemoryRouter>
    )
    expect(screen.getByText(/zaloguj/i)).toBeInTheDocument()
  })

  it('loads profile data and ratings', async () => {
    mockUser = { id: 1 }
    apiFetch.mockResolvedValue({
      name: 'Jan Kowalski',
      email: 'jan@example.com'
    })
    ratingService.getMyRatings.mockResolvedValue({
      data: [{ id: 1, rating: 4, book: { id: 10, title: 'Alpha' } }]
    })

    render(
      <MemoryRouter>
        <Profile />
      </MemoryRouter>
    )

    expect(await screen.findByText(/Moje konto/i)).toBeInTheDocument()
    await userEvent.click(screen.getByRole('button', { name: /Twoje oceny/i }))
    expect(screen.getByText('Alpha')).toBeInTheDocument()
  })

  it('shows password mismatch error', async () => {
    mockUser = { id: 2 }
    apiFetch.mockResolvedValue({})
    ratingService.getMyRatings.mockResolvedValue({ data: [] })

    const { container } = render(
      <MemoryRouter>
        <Profile />
      </MemoryRouter>
    )

    await screen.findByText(/Moje konto/i)
    const currentPassword = container.querySelector('#password-current')
    const newPassword = container.querySelector('#password-new')
    const confirmPassword = container.querySelector('#password-confirm')
    await userEvent.type(currentPassword, 'oldpassword')
    await userEvent.type(newPassword, 'password123')
    await userEvent.type(confirmPassword, 'password124')
    await userEvent.click(screen.getByRole('button', { name: /Zmie.*has/i }))

    expect(await screen.findByText(/Nowe has/i, { selector: '.error' })).toBeInTheDocument()
  })

  it('loads fees tab with empty state', async () => {
    mockUser = { id: 3 }
    apiFetch
      .mockResolvedValueOnce({
        name: 'Jan Kowalski',
        email: 'jan@example.com',
        cardNumber: '123456'
      })
      .mockResolvedValueOnce({ data: [] })
    ratingService.getMyRatings.mockResolvedValue({ data: [] })

    render(
      <MemoryRouter>
        <Profile />
      </MemoryRouter>
    )

    await screen.findByText(/Moje konto/i)
    await userEvent.click(screen.getByRole('button', { name: /Oplaty i platnosci/i }))

    expect(apiFetch).toHaveBeenCalledWith('/api/me/fees')
    expect(await screen.findByText(/Brak aktywnych oplat do uregulowania/i)).toBeInTheDocument()
  })

  it('shows error when fee payment fails', async () => {
    mockUser = { id: 4 }
    apiFetch
      .mockResolvedValueOnce({
        name: 'Jan Kowalski',
        email: 'jan@example.com',
        cardNumber: '123456'
      })
      .mockResolvedValueOnce({ data: [{ id: 9, amount: 12.5, currency: 'PLN', reason: 'Kara' }] })
      .mockRejectedValueOnce(new Error('Pay failed'))
    ratingService.getMyRatings.mockResolvedValue({ data: [] })

    render(
      <MemoryRouter>
        <Profile />
      </MemoryRouter>
    )

    await screen.findByText(/Moje konto/i)
    await userEvent.click(screen.getByRole('button', { name: /Oplaty i platnosci/i }))
    await screen.findByText(/Kara/i)

    await userEvent.click(screen.getByRole('button', { name: /Ureguluj online/i }))
    expect(await screen.findByText(/Pay failed/i)).toBeInTheDocument()
  })
})
