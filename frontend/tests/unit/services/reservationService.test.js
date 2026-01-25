import { describe, it, expect, vi, beforeEach } from 'vitest'
import { reservationService } from '../../../src/services/reservationService'
import { apiFetch } from '../../../src/api'

vi.mock('../../../src/api', () => ({
  apiFetch: vi.fn()
}))

describe('reservationService', () => {
  beforeEach(() => {
    vi.mocked(apiFetch).mockReset()
    vi.mocked(apiFetch).mockResolvedValue({})
  })

  it('fetches personal and all reservations with filters', async () => {
    await reservationService.getMyReservations()
    await reservationService.getAllReservations({ status: 'open', query: '' })

    expect(apiFetch).toHaveBeenNthCalledWith(1, '/api/reservations')
    expect(apiFetch).toHaveBeenNthCalledWith(2, '/api/reservations?status=open')
  })

  it('creates and cancels reservations', async () => {
    await reservationService.createReservation(12)
    await reservationService.cancelReservation(5)

    expect(apiFetch).toHaveBeenNthCalledWith(1, '/api/reservations', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ bookId: 12 })
    })
    expect(apiFetch).toHaveBeenNthCalledWith(2, '/api/reservations/5', {
      method: 'DELETE'
    })
  })

  it('fulfills reservations', async () => {
    await reservationService.fulfillReservation(9)

    expect(apiFetch).toHaveBeenCalledWith('/api/reservations/9/fulfill', { method: 'POST' })
  })
})

