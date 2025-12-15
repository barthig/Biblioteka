import { apiFetch } from '../api'

export const digitalAssetService = {
  async list(bookId) {
    return await apiFetch(`/api/admin/books/${bookId}/assets`)
  },

  async upload(bookId, file) {
    const formData = new FormData()
    formData.append('file', file)
    return await apiFetch(`/api/admin/books/${bookId}/assets`, {
      method: 'POST',
      body: formData
    })
  },

  downloadUrl(bookId, assetId) {
    return `/api/admin/books/${bookId}/assets/${assetId}`
  },

  async remove(bookId, assetId) {
    return await apiFetch(`/api/admin/books/${bookId}/assets/${assetId}`, {
      method: 'DELETE'
    })
  }
}
