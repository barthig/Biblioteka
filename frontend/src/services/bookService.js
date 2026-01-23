import { apiFetch } from '../api'

/**
 * Service for book-related API operations
 */
export const bookService = {
  /**
   * Get all books with optional filters
   */
  async getBooks(filters = {}) {
    const params = new URLSearchParams()
    
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== '') {
        params.set(key, value)
      }
    })
    
    const endpoint = params.toString() ? `/api/books?${params}` : '/api/books'
    return await apiFetch(endpoint)
  },

  /**
   * Get book by ID
   */
  async getBook(id) {
    return await apiFetch(`/api/books/${id}`)
  },

  /**
   * Get book filters/facets
   */
  async getFilters() {
    return await apiFetch('/api/books/filters')
  },

  /**
   * Get recommended books
   */
  async getRecommended() {
    return await apiFetch('/api/books/recommended')
  },

  /**
   * Get popular books
   */
  async getPopular(limit = 10) {
    return await apiFetch(`/api/books/popular?limit=${limit}`)
  },

  /**
   * Get new arrivals
   */
  async getNewArrivals(limit = 10) {
    return await apiFetch(`/api/books/new?limit=${limit}`)
  },

  /**
   * Search books
   */
  async search(query) {
    return await apiFetch(`/api/books?q=${encodeURIComponent(query)}`)
  },

  /**
   * Get book availability
   */
  async getAvailability(bookId) {
    return await apiFetch(`/api/books/${bookId}/availability`)
  }
  ,
  /**
   * Get newest books (recently added)
   */
  async getNewest(limit = 4) {
    const data = await apiFetch(`/api/books/new?limit=${encodeURIComponent(limit)}`)
    return Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : [])
  }
}
