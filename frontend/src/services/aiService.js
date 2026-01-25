import { apiFetch } from '../api'

/**
 * Service for AI/ML features (admin)
 */
export const aiService = {
  /**
   * Get embedding statistics
   */
  async getEmbeddingsStats() {
    return await apiFetch('/api/admin/books/embeddings/stats')
  },

  /**
   * Trigger reindexing of embeddings
   */
  async reindexEmbeddings() {
    return await apiFetch('/api/admin/books/embeddings/reindex', {
      method: 'POST'
    })
  },

  /**
   * Get vector database statistics
   */
  async getVectorStats() {
    return await apiFetch('/admin/vectors/stats')
  },

  /**
   * Reindex all vectors
   */
  async reindexAllVectors() {
    return await apiFetch('/admin/vectors/reindex-all', {
      method: 'POST'
    })
  },

  /**
   * Get semantic search recommendations
   */
  async getSemanticRecommendations(query, options = {}) {
    return await apiFetch('/api/recommend', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ query, ...options })
    })
  }
}
