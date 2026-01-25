import { describe, it, expect, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter, Routes, Route } from 'react-router-dom'
import UserDetails from '../../../src/pages/user/UserDetails'
import { apiFetch } from '../../../src/api'

vi.mock('../../../src/api', () => ({
  apiFetch: vi.fn()
}))

vi.mock('../../../src/context/AuthContext', () => ({
  useAuth: () => ({ token: 'token' })
}))

describe('UserDetails page', () => {
  it('loads and edits user details', async () => {
    const data = {
      user: {
        name: 'Jan Kowalski',
        email: 'jan@example.com',
        roles: ['ROLE_USER'],
        phoneNumber: '',
        addressLine: '',
        city: '',
        postalCode: '',
        pesel: '',
        cardNumber: '',
        blocked: false
      },
      activeLoans: [],
      loanHistory: [],
      activeFines: [],
      paidFines: [],
      statistics: { totalLoans: 1, activeLoansCount: 1, activeFinesCount: 0, totalFineAmount: 0 }
    }
    apiFetch.mockResolvedValueOnce(data)
    apiFetch.mockResolvedValueOnce({})
    apiFetch.mockResolvedValueOnce(data)
    window.alert = vi.fn()

    const { container } = render(
      <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }} initialEntries={['/users/1/details']}>
        <Routes>
          <Route path="/users/:id/details" element={<UserDetails />} />
        </Routes>
      </MemoryRouter>
    )

    expect(await screen.findByText(/Jan Kowalski/)).toBeInTheDocument()
    await userEvent.click(screen.getByRole('button', { name: /Edytuj/i }))
    const nameInput = container.querySelector('input[name="name"]')
    await userEvent.clear(nameInput)
    await userEvent.type(nameInput, 'Jan Nowak')
    await userEvent.click(screen.getByRole('button', { name: /Zapisz/i }))
    expect(apiFetch).toHaveBeenCalledWith('/api/users/1', expect.objectContaining({ method: 'PUT' }))
  })
})


