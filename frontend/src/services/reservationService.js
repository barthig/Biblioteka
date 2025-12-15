import { apiFetch } from '../api'

/**
 * Service for reservation-related API operations
 */
export const reservationService = {
  /**
   * Get current user's reservations
   */
  async getMyReservations() {
    return await apiFetch('/api/reservations')
  },

  /**
   * Get all reservations (librarian/admin)
   */
  async getAllReservations(filters = {}) {
    const params = new URLSearchParams()
    
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== '') {
        params.set(key, value)
      }
    })
    
    const endpoint = params.toString() ? `/api/reservations?${params}` : '/api/reservations'
    return await apiFetch(endpoint)
  },

  /**
   * Create a new reservation
   */
  async createReservation(bookId) {
    return await apiFetch('/api/reservations', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ bookId })
    })
  },

  /**
   * Cancel a reservation
   */
  async cancelReservation(reservationId) {
    return await apiFetch(`/api/reservations/${reservationId}`, {
      method: 'DELETE'
    })
  },

  /**
   * Fulfill reservation (convert to loan)
   */
  async fulfillReservation(reservationId) {
    return await apiFetch(`/api/reservations/${reservationId}/fulfill`, {
      method: 'POST'
    })
  }
}
