import { describe, it, expect, beforeEach, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter, Routes, Route } from 'react-router-dom'
import BookDetails from '../../../src/pages/books/BookDetails'
import { apiFetch } from '../../../src/api'
import { ResourceCacheProvider } from '../../../src/context/ResourceCacheContext'

vi.mock('../../../src/api', () => ({
  apiFetch: vi.fn()
}))

let mockAuth = { token: null, isAuthenticated: false }
vi.mock('../../../src/context/AuthContext', () => ({
  useAuth: () => mockAuth
}))

vi.mock('../../../src/components/StarRating', () => ({
  StarRating: () => <div data-testid="star-rating" />,
  RatingDisplay: () => <div data-testid="rating-display" />
}))

const renderPage = (path = '/books/1') => {
  return render(
    <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }} initialEntries={[path]}>
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
    mockAuth = { token: null, isAuthenticated: false }
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

  it('shows auth error when reserving without token', async () => {
    apiFetch.mockImplementation((endpoint) => {
      if (endpoint === '/api/books/1') {
        return Promise.resolve({ id: 1, title: 'Alpha', copies: 0, totalCopies: 1 })
      }
      if (endpoint === '/api/books/1/reviews') {
        return Promise.resolve({ summary: { average: null, total: 0 }, reviews: [], userReview: null })
      }
      return Promise.resolve({})
    })

    renderPage()
    await screen.findByText('Alpha')
    const reserveButton = screen.getByRole('button', { name: /kolejki/i })
    expect(reserveButton).toBeDisabled()
    expect(screen.getAllByText(/Zaloguj/i).length).toBeGreaterThan(0)
  })

  it('reserves and toggles favorite with token', async () => {
    mockAuth = { token: 'token', isAuthenticated: false }
    apiFetch.mockImplementation((endpoint) => {
      if (endpoint === '/api/books/1') {
        return Promise.resolve({ id: 1, title: 'Alpha', copies: 0, totalCopies: 1 })
      }
      if (endpoint === '/api/books/1/reviews') {
        return Promise.resolve({ summary: { average: null, total: 0 }, reviews: [], userReview: null })
      }
      if (endpoint === '/api/reservations') {
        return Promise.resolve({ data: { id: 5, reservedAt: '2025-01-01T10:00:00Z' } })
      }
      if (endpoint === '/api/favorites') {
        return Promise.resolve({ data: { id: 9 } })
      }
      return Promise.resolve({})
    })

    renderPage()
    await screen.findByText('Alpha')

    await userEvent.click(screen.getByRole('button', { name: /kolejki/i }))
    expect(apiFetch).toHaveBeenCalledWith('/api/reservations', expect.objectContaining({ method: 'POST' }))

    await userEvent.click(screen.getByRole('button', { name: /Dodaj do ulub/i }))
    expect(apiFetch).toHaveBeenCalledWith('/api/favorites', expect.objectContaining({ method: 'POST' }))
    expect(await screen.findByText(/Usu/i)).toBeInTheDocument()
  })
})


