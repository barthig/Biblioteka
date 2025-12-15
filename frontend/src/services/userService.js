import { apiFetch } from '../api'

/**
 * Service for user-related API operations
 */
export const userService = {
  /**
   * Get current user profile
   */
  async getProfile() {
    return await apiFetch('/api/me')
  },

  /**
   * Update current user profile
   */
  async updateProfile(data) {
    return await apiFetch('/api/me', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
  },

  /**
   * Change password
   */
  async changePassword(currentPassword, newPassword) {
    return await apiFetch('/api/me/password', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ currentPassword, newPassword })
    })
  },

  /**
   * Get user favorites
   */
  async getFavorites() {
    return await apiFetch('/api/favorites')
  },

  /**
   * Add book to favorites
   */
  async addFavorite(bookId) {
    return await apiFetch('/api/favorites', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ bookId })
    })
  },

  /**
   * Remove book from favorites
   */
  async removeFavorite(bookId) {
    return await apiFetch(`/api/favorites/${bookId}`, {
      method: 'DELETE'
    })
  },

  /**
   * Get all users (admin)
   */
  async getAllUsers(filters = {}) {
    const params = new URLSearchParams()
    
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== '') {
        params.set(key, value)
      }
    })
    
    const endpoint = params.toString() ? `/api/users?${params}` : '/api/users'
    return await apiFetch(endpoint)
  }
}
