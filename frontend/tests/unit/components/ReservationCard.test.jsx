import { describe, it, expect, vi } from 'vitest'
import { render, screen, fireEvent } from '@testing-library/react'
import ReservationCard from '../../../src/components/loans/ReservationCard'

describe('ReservationCard', () => {
  it('renders pending reservation and triggers actions', () => {
    const onCancel = vi.fn()
    const onFulfill = vi.fn()
    const reservation = {
      id: 5,
      status: 'pending',
      reservedAt: '2025-01-01T10:00:00Z',
      expiresAt: '2025-01-05T10:00:00Z',
      book: { title: 'Alpha', author: 'Author A' }
    }

    render(<ReservationCard reservation={reservation} onCancel={onCancel} onFulfill={onFulfill} />)
    expect(screen.getByText('Alpha')).toBeInTheDocument()

    fireEvent.click(screen.getByRole('button', { name: /Odbierz/i }))
    fireEvent.click(screen.getByRole('button', { name: /Anuluj/i }))
    expect(onFulfill).toHaveBeenCalledWith(5)
    expect(onCancel).toHaveBeenCalledWith(5)
  })
})

