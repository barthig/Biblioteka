import { apiFetch } from '../api'

/**
 * Service for loan-related API operations
 */
export const loanService = {
  /**
   * Get current user's loans
   */
  async getMyLoans() {
    return await apiFetch('/api/loans/my')
  },

  /**
   * Get all loans (librarian/admin)
   */
  async getAllLoans(filters = {}) {
    const params = new URLSearchParams()
    
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== '') {
        params.set(key, value)
      }
    })
    
    const endpoint = params.toString() ? `/api/loans?${params}` : '/api/loans'
    return await apiFetch(endpoint)
  },

  /**
   * Create a new loan
   */
  async createLoan(bookId, userId = null) {
    return await apiFetch('/api/loans', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ bookId, userId })
    })
  },

  /**
   * Return a loan
   */
  async returnLoan(loanId) {
    return await apiFetch(`/api/loans/${loanId}/return`, {
      method: 'POST'
    })
  },

  /**
   * Extend loan (renew)
   */
  async extendLoan(loanId) {
    return await apiFetch(`/api/loans/${loanId}/extend`, {
      method: 'POST'
    })
  },

  /**
   * Get loan statistics
   */
  async getStatistics() {
    return await apiFetch('/api/loans/statistics')
  }
}
