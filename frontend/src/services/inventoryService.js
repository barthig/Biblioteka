import { apiFetch } from '../api'

/**
 * Service for inventory management (librarian/admin)
 */
export const inventoryService = {
  /**
   * Look up copy by barcode
   */
  async lookupByBarcode(barcode) {
    return await apiFetch(`/api/admin/copies/barcode/${encodeURIComponent(barcode)}`)
  },

  /**
   * Get copies for a book
   */
  async getBookCopies(bookId) {
    return await apiFetch(`/api/admin/books/${bookId}/copies`)
  },

  /**
   * Add a copy to a book
   */
  async addCopy(bookId, data) {
    return await apiFetch(`/api/admin/books/${bookId}/copies`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
  },

  /**
   * Update a copy
   */
  async updateCopy(bookId, copyId, data) {
    return await apiFetch(`/api/admin/books/${bookId}/copies/${copyId}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
  },

  /**
   * Delete a copy
   */
  async deleteCopy(bookId, copyId) {
    return await apiFetch(`/api/admin/books/${bookId}/copies/${copyId}`, {
      method: 'DELETE'
    })
  }
}
