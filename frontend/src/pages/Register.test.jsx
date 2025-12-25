import { describe, it, expect, beforeEach, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router-dom'
import Register from './Register'
import { apiFetch } from '../api'

const mockLogin = vi.fn()

vi.mock('../context/AuthContext', () => ({
  useAuth: () => ({ user: null, login: mockLogin })
}))

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

describe('Register page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('shows error when passwords do not match', async () => {
    render(
      <MemoryRouter>
        <Register />
      </MemoryRouter>
    )

    await userEvent.type(screen.getByLabelText(/Imi/i), 'Jan Kowalski')
    await userEvent.type(screen.getByLabelText(/Email/i), 'jan@example.com')
    await userEvent.type(screen.getByLabelText(/^Has/i), 'password123')
    await userEvent.type(screen.getByLabelText(/Powt/i), 'password124')
    await userEvent.click(screen.getByRole('button', { name: /Utw/i }))

    expect(await screen.findByText(/Hasla musza byc identyczne/i)).toBeInTheDocument()
    expect(apiFetch).not.toHaveBeenCalled()
  })

  it('registers, verifies, and logs in user', async () => {
    apiFetch
      .mockResolvedValueOnce({ verificationToken: 'token-1' })
      .mockResolvedValueOnce({ pendingApproval: false })
      .mockResolvedValueOnce({ token: 'token-123', refreshToken: 'refresh-123' })

    render(
      <MemoryRouter>
        <Register />
      </MemoryRouter>
    )

    await userEvent.type(screen.getByLabelText(/Imi/i), 'Jan Kowalski')
    await userEvent.type(screen.getByLabelText(/Email/i), 'jan@example.com')
    await userEvent.type(screen.getByLabelText(/^Has/i), 'password123')
    await userEvent.type(screen.getByLabelText(/Powt/i), 'password123')
    await userEvent.click(screen.getByRole('button', { name: /Utw/i }))

    await waitFor(() => {
      expect(apiFetch).toHaveBeenNthCalledWith(1, '/api/auth/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name: 'Jan Kowalski',
          email: 'jan@example.com',
          password: 'password123',
          phoneNumber: undefined,
          addressLine: undefined,
          city: undefined,
          postalCode: undefined,
          privacyConsent: true
        })
      })
      expect(apiFetch).toHaveBeenNthCalledWith(2, '/api/auth/verify/token-1')
      expect(apiFetch).toHaveBeenNthCalledWith(3, '/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: 'jan@example.com', password: 'password123' })
      })
      expect(mockLogin).toHaveBeenCalledWith('token-123', 'refresh-123')
    })
  })

  it('shows success when account awaits approval', async () => {
    apiFetch
      .mockResolvedValueOnce({ verificationToken: 'token-2' })
      .mockResolvedValueOnce({ pendingApproval: true })

    render(
      <MemoryRouter>
        <Register />
      </MemoryRouter>
    )

    await userEvent.type(screen.getByLabelText(/Imi/i), 'Jan Kowalski')
    await userEvent.type(screen.getByLabelText(/Email/i), 'jan@example.com')
    await userEvent.type(screen.getByLabelText(/^Has/i), 'password123')
    await userEvent.type(screen.getByLabelText(/Powt/i), 'password123')
    await userEvent.click(screen.getByRole('button', { name: /Utw/i }))

    expect(await screen.findByText(/Oczekuje na akceptacje/i)).toBeInTheDocument()
    expect(mockLogin).not.toHaveBeenCalled()
  })
})
