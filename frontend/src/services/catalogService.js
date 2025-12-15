import { apiFetch } from '../api'

export const catalogService = {
  async exportCatalog() {
    // Rely on browser download
    return await apiFetch('/api/admin/catalog/export')
  },

  async importCatalog(file) {
    const formData = new FormData()
    formData.append('file', file)
    return await apiFetch('/api/admin/catalog/import', {
      method: 'POST',
      body: formData
    })
  }
}
