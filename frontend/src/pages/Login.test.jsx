import { describe, it, expect, beforeEach, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router-dom'
import Login from './Login'
import { apiFetch } from '../api'

const mockLogin = vi.fn()
const mockNavigate = vi.fn()

vi.mock('../context/AuthContext', () => ({
  useAuth: () => ({ user: null, login: mockLogin })
}))

vi.mock('react-router-dom', async () => {
  const actual = await vi.importActual('react-router-dom')
  return { ...actual, useNavigate: () => mockNavigate }
})

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

describe('Login page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('submits credentials and navigates on success', async () => {
    apiFetch.mockResolvedValue({ token: 'token-123' })

    render(
      <MemoryRouter initialEntries={[{ pathname: '/login', state: { from: { pathname: '/books' } } }]}>
        <Login />
      </MemoryRouter>
    )

    await userEvent.type(screen.getByLabelText(/Adres email/i), 'admin@biblioteka.pl')
    await userEvent.type(screen.getByLabelText(/Has/i), 'password123')
    await userEvent.click(screen.getByRole('button', { name: /Zaloguj/i }))

    await waitFor(() => {
      expect(apiFetch).toHaveBeenCalledWith('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: 'admin@biblioteka.pl', password: 'password123' })
      })
      expect(mockLogin).toHaveBeenCalledWith('token-123')
      expect(mockNavigate).toHaveBeenCalledWith('/books', { replace: true })
    })
  })

  it('shows an error when login fails', async () => {
    apiFetch.mockRejectedValue(new Error('Invalid credentials'))

    render(
      <MemoryRouter>
        <Login />
      </MemoryRouter>
    )

    await userEvent.type(screen.getByLabelText(/Adres email/i), 'admin@biblioteka.pl')
    await userEvent.type(screen.getByLabelText(/Has/i), 'wrong')
    await userEvent.click(screen.getByRole('button', { name: /Zaloguj/i }))

    expect(await screen.findByText(/Invalid credentials/i)).toBeInTheDocument()
  })
})
