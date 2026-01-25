import { describe, it, expect, beforeEach, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router-dom'
import Login from '../../../src/pages/auth/Login'
import { apiFetch } from '../../../src/api'

const mockLogin = vi.fn()
const mockNavigate = vi.fn()

vi.mock('../../../src/context/AuthContext', () => ({
  useAuth: () => ({ user: null, login: mockLogin })
}))

vi.mock('react-router-dom', async () => {
  const actual = await vi.importActual('react-router-dom')
  return { ...actual, useNavigate: () => mockNavigate }
})

vi.mock('../../../src/api', () => ({
  apiFetch: vi.fn()
}))

describe('Login page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('submits credentials and navigates on success', async () => {
    apiFetch.mockResolvedValue({ token: 'token-123', refreshToken: 'refresh-123' })

    render(
      <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }} initialEntries={[{ pathname: '/login', state: { from: { pathname: '/books' } } }]}>
        <Login />
      </MemoryRouter>
    )

    await userEvent.type(screen.getByLabelText(/e-?mail/i), 'admin@biblioteka.pl')
    await userEvent.type(screen.getByLabelText(/Has/i), 'password123')
    await userEvent.click(screen.getByRole('button', { name: /Zaloguj/i }))

    await waitFor(() => {
      expect(apiFetch).toHaveBeenCalledWith('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: 'admin@biblioteka.pl', password: 'password123' })
      })
      expect(mockLogin).toHaveBeenCalledWith('token-123', 'refresh-123')
      expect(mockNavigate).toHaveBeenCalledWith('/books', { replace: true })
    })
  })

  it('shows an error when login fails', async () => {
    apiFetch.mockRejectedValue(new Error('Invalid credentials'))

    render(
      <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
        <Login />
      </MemoryRouter>
    )

    await userEvent.type(screen.getByLabelText(/e-?mail/i), 'admin@biblioteka.pl')
    await userEvent.type(screen.getByLabelText(/Has/i), 'wrong')
    await userEvent.click(screen.getByRole('button', { name: /Zaloguj/i }))

    expect(await screen.findByText(/Invalid credentials/i)).toBeInTheDocument()
  })
})


