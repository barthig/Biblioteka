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
  },

  /**
   * Upload user avatar
   */
  async uploadAvatar(file) {
    const formData = new FormData()
    formData.append('avatar', file)
    return await apiFetch('/api/me/avatar', {
      method: 'POST',
      body: formData
    })
  },

  /**
   * Delete user avatar
   */
  async deleteAvatar() {
    return await apiFetch('/api/me/avatar', {
      method: 'DELETE'
    })
  },

  /**
   * Update user PIN
   */
  async updatePin(currentPin, newPin) {
    return await apiFetch('/api/me/pin', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ currentPin, newPin })
    })
  },

  /**
   * Get onboarding status
   */
  async getOnboardingStatus() {
    return await apiFetch('/api/users/me/onboarding')
  },

  /**
   * Update onboarding status
   */
  async updateOnboardingStatus(step) {
    return await apiFetch('/api/users/me/onboarding', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ step })
    })
  },

  /**
   * Get user by ID (admin)
   */
  async getUser(id) {
    return await apiFetch(`/api/users/${id}`)
  },

  /**
   * Update user (admin)
   */
  async updateUser(id, data) {
    return await apiFetch(`/api/users/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
  },

  /**
   * Delete user (admin)
   */
  async deleteUser(id) {
    return await apiFetch(`/api/users/${id}`, {
      method: 'DELETE'
    })
  }
}
