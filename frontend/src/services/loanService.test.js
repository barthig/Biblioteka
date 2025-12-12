import { describe, it, expect, beforeEach, vi } from 'vitest'
import { loanService } from './loanService'
import * as api from '../api'

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

describe('loanService', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('getMyLoans', () => {
    it('should fetch current user loans', async () => {
      const mockLoans = [
        { id: 1, bookTitle: 'Book 1', dueDate: '2025-12-31' },
        { id: 2, bookTitle: 'Book 2', dueDate: '2025-12-25' }
      ]
      api.apiFetch.mockResolvedValue(mockLoans)

      const result = await loanService.getMyLoans()

      expect(api.apiFetch).toHaveBeenCalledWith('/api/loans/my')
      expect(result).toEqual(mockLoans)
    })
  })

  describe('getAllLoans', () => {
    it('should fetch all loans with filters', async () => {
      const mockResponse = {
        items: [{ id: 1, bookTitle: 'Book 1' }],
        total: 1,
        page: 1
      }
      api.apiFetch.mockResolvedValue(mockResponse)

      const filters = { page: 1, status: 'active' }
      const result = await loanService.getAllLoans(filters)

      expect(api.apiFetch).toHaveBeenCalledWith('/api/loans?page=1&status=active')
      expect(result).toEqual(mockResponse)
    })
  })

  describe('createLoan', () => {
    it('should create loan with bookId only', async () => {
      const mockLoan = { id: 1, bookId: 123, userId: 456, status: 'active' }
      api.apiFetch.mockResolvedValue(mockLoan)

      const result = await loanService.createLoan(123)

      expect(api.apiFetch).toHaveBeenCalledWith('/api/loans', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ bookId: 123, userId: null })
      })
      expect(result).toEqual(mockLoan)
    })

    it('should create loan with bookId and userId', async () => {
      const mockLoan = { id: 1, bookId: 123, userId: 789, status: 'active' }
      api.apiFetch.mockResolvedValue(mockLoan)

      const result = await loanService.createLoan(123, 789)

      expect(api.apiFetch).toHaveBeenCalledWith('/api/loans', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ bookId: 123, userId: 789 })
      })
      expect(result).toEqual(mockLoan)
    })
  })

  describe('returnLoan', () => {
    it('should return a loan', async () => {
      const mockResponse = { id: 1, status: 'returned', returnedAt: '2025-12-12' }
      api.apiFetch.mockResolvedValue(mockResponse)

      const result = await loanService.returnLoan(1)

      expect(api.apiFetch).toHaveBeenCalledWith('/api/loans/1/return', {
        method: 'POST'
      })
      expect(result).toEqual(mockResponse)
    })
  })

  describe('extendLoan', () => {
    it('should extend a loan', async () => {
      const mockResponse = { id: 1, dueDate: '2026-01-12', renewalCount: 1 }
      api.apiFetch.mockResolvedValue(mockResponse)

      const result = await loanService.extendLoan(1)

      expect(api.apiFetch).toHaveBeenCalledWith('/api/loans/1/extend', {
        method: 'POST'
      })
      expect(result).toEqual(mockResponse)
    })
  })
})
