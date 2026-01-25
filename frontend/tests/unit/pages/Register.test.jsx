import { describe, it, expect, beforeEach, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router-dom'
import Register from '../../../src/pages/auth/Register'
import { apiFetch } from '../../../src/api'

vi.mock('../../../src/context/AuthContext', () => ({
  useAuth: () => ({ user: null })
}))

vi.mock('../../../src/api', () => ({
  apiFetch: vi.fn()
}))

describe('Register page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    // Mock window.location.href for redirect tests
    delete window.location
    window.location = { href: '' }
  })

  it('shows error when passwords do not match', async () => {
    render(
      <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
        <Register />
      </MemoryRouter>
    )

    await userEvent.type(screen.getByLabelText(/Imi/i), 'Jan Kowalski')
    await userEvent.type(screen.getByLabelText(/e-?mail/i), 'jan@example.com')
    await userEvent.type(screen.getByLabelText(/^Has/i), 'Password123')
    await userEvent.type(screen.getByLabelText(/Powt/i), 'Password124')
    await userEvent.type(screen.getByLabelText(/Kod pocztowy/i), '00-001')
    await userEvent.click(screen.getByRole('button', { name: /Utw/i }))

    expect(await screen.findByText(/Has.*identyczne/i)).toBeInTheDocument()
    expect(apiFetch).not.toHaveBeenCalled()
  })

  it('registers and verifies user then shows success message', async () => {
    apiFetch
      .mockResolvedValueOnce({ verificationToken: 'token-1' })
      .mockResolvedValueOnce({ pendingApproval: false })

    render(
      <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
        <Register />
      </MemoryRouter>
    )

    await userEvent.type(screen.getByLabelText(/Imi/i), 'Jan Kowalski')
    await userEvent.type(screen.getByLabelText(/e-?mail/i), 'jan@example.com')
    await userEvent.type(screen.getByLabelText(/^Has/i), 'Password123')
    await userEvent.type(screen.getByLabelText(/Powt/i), 'Password123')
    await userEvent.type(screen.getByLabelText(/Kod pocztowy/i), '00-001')
    await userEvent.click(screen.getByRole('button', { name: /Utw/i }))

    // Verify success message is shown
    expect(await screen.findByText(/Konto zosta.*o utworzone/i)).toBeInTheDocument()
    
    // Verify API calls
    await waitFor(() => {
      expect(apiFetch).toHaveBeenNthCalledWith(1, '/api/auth/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name: 'Jan Kowalski',
          email: 'jan@example.com',
          password: 'Password123',
          phoneNumber: undefined,
          addressLine: undefined,
          city: undefined,
          postalCode: '00-001',
          privacyConsent: true
        })
      })
      expect(apiFetch).toHaveBeenNthCalledWith(2, '/api/auth/verify/token-1')
    })
  })

  it('shows success when account awaits approval', async () => {
    apiFetch
      .mockResolvedValueOnce({ verificationToken: 'token-2' })
      .mockResolvedValueOnce({ pendingApproval: true })

    render(
      <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
        <Register />
      </MemoryRouter>
    )

    await userEvent.type(screen.getByLabelText(/Imi/i), 'Jan Kowalski')
    await userEvent.type(screen.getByLabelText(/e-?mail/i), 'jan@example.com')
    await userEvent.type(screen.getByLabelText(/^Has/i), 'Password123')
    await userEvent.type(screen.getByLabelText(/Powt/i), 'Password123')
    await userEvent.type(screen.getByLabelText(/Kod pocztowy/i), '00-001')
    await userEvent.click(screen.getByRole('button', { name: /Utw/i }))

    expect(await screen.findByText(/Oczekuje na akceptacj/i)).toBeInTheDocument()
  })
})


