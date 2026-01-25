import { describe, it, expect, beforeEach, vi } from 'vitest'
import { bookService } from '../../../src/services/bookService'
import * as api from '../../../src/services/api'

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

describe('bookService', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('getBooks', () => {
    it('should fetch books with filters', async () => {
      const mockBooks = {
        items: [
          { id: 1, title: 'Test Book 1', author: { name: 'Author 1' } },
          { id: 2, title: 'Test Book 2', author: { name: 'Author 2' } }
        ],
        total: 2,
        page: 1,
        limit: 20
      }

      api.apiFetch.mockResolvedValue(mockBooks)

      const filters = { page: 1, limit: 20, categoryId: 5 }
      const result = await bookService.getBooks(filters)

      expect(api.apiFetch).toHaveBeenCalledWith('/api/books?page=1&limit=20&categoryId=5')
      expect(result).toEqual(mockBooks)
    })

    it('should handle empty filters', async () => {
      const mockBooks = { items: [], total: 0, page: 1, limit: 20 }
      api.apiFetch.mockResolvedValue(mockBooks)

      await bookService.getBooks()

      expect(api.apiFetch).toHaveBeenCalledWith('/api/books')
    })
  })

  describe('getBook', () => {
    it('should fetch single book by id', async () => {
      const mockBook = { id: 1, title: 'Test Book', isbn: '1234567890' }
      api.apiFetch.mockResolvedValue(mockBook)

      const result = await bookService.getBook(1)

      expect(api.apiFetch).toHaveBeenCalledWith('/api/books/1')
      expect(result).toEqual(mockBook)
    })
  })

  describe('search', () => {
    it('should search books with query', async () => {
      const mockResults = {
        items: [{ id: 1, title: 'Search Result' }],
        total: 1
      }
      api.apiFetch.mockResolvedValue(mockResults)

      const result = await bookService.search('test query')

      expect(api.apiFetch).toHaveBeenCalledWith('/api/books?q=test%20query')
      expect(result).toEqual(mockResults)
    })

    it('should encode special characters in query', async () => {
      api.apiFetch.mockResolvedValue({ items: [], total: 0 })

      await bookService.search('test & query')

      expect(api.apiFetch).toHaveBeenCalledWith('/api/books?q=test%20%26%20query')
    })
  })

  describe('getAvailability', () => {
    it('should fetch book availability', async () => {
      const mockAvailability = {
        totalCopies: 5,
        availableCopies: 3,
        onLoan: 2
      }
      api.apiFetch.mockResolvedValue(mockAvailability)

      const result = await bookService.getAvailability(1)

      expect(api.apiFetch).toHaveBeenCalledWith('/api/books/1/availability')
      expect(result).toEqual(mockAvailability)
    })
  })

  describe('getFilters', () => {
    it('should fetch available filters', async () => {
      const mockFilters = {
        categories: [{ id: 1, name: 'Fiction' }],
        authors: [{ id: 1, name: 'Author 1' }]
      }
      api.apiFetch.mockResolvedValue(mockFilters)

      const result = await bookService.getFilters()

      expect(api.apiFetch).toHaveBeenCalledWith('/api/books/filters')
      expect(result).toEqual(mockFilters)
    })
  })
})

