import { apiFetch } from '../api'

export const ratingService = {
  async getMyRatings() {
    return await apiFetch('/api/users/me/ratings')
  },

  async deleteRating(bookId, ratingId) {
    return await apiFetch(`/api/books/${bookId}/ratings/${ratingId}`, {
      method: 'DELETE'
    })
  }
}
