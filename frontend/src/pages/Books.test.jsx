import { describe, it, expect, beforeEach, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import Books from './Books'
import { apiFetch } from '../api'
import { ResourceCacheProvider } from '../context/ResourceCacheContext'

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

vi.mock('../components/BookItem', () => ({
  default: ({ book }) => <div data-testid="book-item">{book.title}</div>
}))

const renderPage = () => {
  return render(
    <ResourceCacheProvider>
      <Books />
    </ResourceCacheProvider>
  )
}

describe('Books page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders fetched books', async () => {
    apiFetch.mockImplementation((endpoint) => {
      if (endpoint === '/api/books' || endpoint.startsWith('/api/books?')) {
        return Promise.resolve({ data: [{ id: 1, title: 'Alpha' }, { id: 2, title: 'Beta' }] })
      }
      if (endpoint === '/api/books/filters') {
        return Promise.resolve({ authors: [], categories: [], publishers: [], resourceTypes: [], years: {}, ageGroups: [] })
      }
      return Promise.resolve({})
    })

    renderPage()

    const items = await screen.findAllByTestId('book-item')
    expect(items).toHaveLength(2)
    expect(screen.getByText('Alpha')).toBeInTheDocument()
    expect(screen.getByText('Beta')).toBeInTheDocument()
  })

  it('shows empty state when no books', async () => {
    apiFetch.mockImplementation((endpoint) => {
      if (endpoint === '/api/books' || endpoint.startsWith('/api/books?')) {
        return Promise.resolve({ data: [] })
      }
      if (endpoint === '/api/books/filters') {
        return Promise.resolve({ authors: [], categories: [], publishers: [], resourceTypes: [], years: {}, ageGroups: [] })
      }
      return Promise.resolve({})
    })

    renderPage()

    await waitFor(() => {
      expect(screen.getByText(/Brak/)).toBeInTheDocument()
    })
  })

  it('shows error when loading fails', async () => {
    apiFetch.mockImplementation((endpoint) => {
      if (endpoint === '/api/books' || endpoint.startsWith('/api/books?')) {
        return Promise.reject(new Error('Load failed'))
      }
      if (endpoint === '/api/books/filters') {
        return Promise.resolve({ authors: [], categories: [], publishers: [], resourceTypes: [], years: {}, ageGroups: [] })
      }
      return Promise.resolve({})
    })

    renderPage()

    expect(await screen.findByText(/Load failed/i)).toBeInTheDocument()
  })
})
