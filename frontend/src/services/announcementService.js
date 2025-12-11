import { apiFetch } from '../api'

/**
 * Service for announcement-related API operations
 */
export const announcementService = {
  /**
   * Get all announcements
   */
  async getAnnouncements(filters = {}) {
    const params = new URLSearchParams()
    
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== '') {
        params.set(key, value)
      }
    })
    
    const endpoint = params.toString() ? `/api/announcements?${params}` : '/api/announcements'
    return await apiFetch(endpoint)
  },

  /**
   * Get announcement by ID
   */
  async getAnnouncement(id) {
    return await apiFetch(`/api/announcements/${id}`)
  },

  /**
   * Create announcement (librarian/admin)
   */
  async createAnnouncement(data) {
    return await apiFetch('/api/announcements', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
  },

  /**
   * Update announcement (librarian/admin)
   */
  async updateAnnouncement(id, data) {
    return await apiFetch(`/api/announcements/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
  },

  /**
   * Delete announcement (librarian/admin)
   */
  async deleteAnnouncement(id) {
    return await apiFetch(`/api/announcements/${id}`, {
      method: 'DELETE'
    })
  },

  /**
   * Publish announcement (librarian/admin)
   */
  async publishAnnouncement(id) {
    return await apiFetch(`/api/announcements/${id}/publish`, {
      method: 'POST'
    })
  },

  /**
   * Archive announcement (librarian/admin)
   */
  async archiveAnnouncement(id) {
    return await apiFetch(`/api/announcements/${id}/archive`, {
      method: 'POST'
    })
  }
}
