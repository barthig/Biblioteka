import { describe, it, expect, beforeEach, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import { MemoryRouter, Routes, Route } from 'react-router-dom'
import BookDetails from './BookDetails'
import { apiFetch } from '../api'
import { ResourceCacheProvider } from '../context/ResourceCacheContext'

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

vi.mock('../context/AuthContext', () => ({
  useAuth: () => ({ token: null, isAuthenticated: false })
}))

vi.mock('../components/StarRating', () => ({
  StarRating: () => <div data-testid="star-rating" />,
  RatingDisplay: () => <div data-testid="rating-display" />
}))

const renderPage = (path = '/books/1') => {
  return render(
    <MemoryRouter initialEntries={[path]}>
      <ResourceCacheProvider>
        <Routes>
          <Route path="/books/:id" element={<BookDetails />} />
        </Routes>
      </ResourceCacheProvider>
    </MemoryRouter>
  )
}

describe('BookDetails page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders book details', async () => {
    apiFetch.mockImplementation((endpoint) => {
      if (endpoint === '/api/books/1') {
        return Promise.resolve({
          id: 1,
          title: 'Alpha',
          author: { name: 'Author A' },
          categories: [{ name: 'Fiction' }],
          copies: 2,
          totalCopies: 3
        })
      }
      if (endpoint === '/api/books/1/reviews') {
        return Promise.resolve({ summary: { average: 4.5, total: 2 }, reviews: [], userReview: null })
      }
      return Promise.resolve({})
    })

    renderPage()

    expect(await screen.findByText('Alpha')).toBeInTheDocument()
    expect(screen.getByText(/Author A/i)).toBeInTheDocument()
  })

  it('shows error when load fails', async () => {
    apiFetch.mockRejectedValue(new Error('Load failed'))
    renderPage('/books/2')
    expect(await screen.findByText(/Load failed/i)).toBeInTheDocument()
  })
})
