import { apiFetch } from '../api'

/**
 * Service for backup management (admin)
 */
export const backupService = {
  /**
   * List all backups
   */
  async listBackups() {
    return await apiFetch('/api/admin/backups')
  },

  /**
   * Create a new backup
   */
  async createBackup(options = {}) {
    return await apiFetch('/api/admin/backups', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(options)
    })
  },

  /**
   * Download a backup
   */
  getDownloadUrl(backupId) {
    return `/api/admin/backups/${backupId}`
  },

  /**
   * Delete a backup
   */
  async deleteBackup(backupId) {
    return await apiFetch(`/api/admin/backups/${backupId}`, {
      method: 'DELETE'
    })
  },

  /**
   * Restore from backup
   */
  async restoreBackup(backupId) {
    return await apiFetch(`/api/admin/backups/${backupId}/restore`, {
      method: 'POST'
    })
  }
}
