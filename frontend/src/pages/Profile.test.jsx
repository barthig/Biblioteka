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
  useAuth: () => ({ user: mockUser })
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
    expect(screen.getByText('Alpha')).toBeInTheDocument()
  })

  it('shows password mismatch error', async () => {
    mockUser = { id: 2 }
    apiFetch.mockResolvedValue({})
    ratingService.getMyRatings.mockResolvedValue({ data: [] })

    render(
      <MemoryRouter>
        <Profile />
      </MemoryRouter>
    )

    const newPassword = await screen.findByLabelText(/Nowe/i)
    const confirmPassword = screen.getByLabelText(/Powt/i)
    await userEvent.type(newPassword, 'password123')
    await userEvent.type(confirmPassword, 'password124')
    await userEvent.click(screen.getByRole('button', { name: /Zmie/i }))

    expect(await screen.findByText(/Nowe has/i)).toBeInTheDocument()
  })
})
