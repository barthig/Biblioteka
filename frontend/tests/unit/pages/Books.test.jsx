import { describe, it, expect, beforeEach, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router-dom'
import Books from '../../../src/pages/books/Books'
import { apiFetch } from '../../../src/api'
import { ResourceCacheProvider } from '../../../src/context/ResourceCacheContext'

vi.mock('../../../src/api', () => ({
  apiFetch: vi.fn()
}))

vi.mock('../../../src/components/books/BookItem', () => ({
  default: ({ book }) => <div data-testid="book-item">{book.title}</div>
}))

const renderPage = () => {
  return render(
    <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
      <ResourceCacheProvider>
        <Books />
      </ResourceCacheProvider>
    </MemoryRouter>
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

  it('toggles advanced filters and clears search', async () => {
    apiFetch.mockImplementation((endpoint) => {
      if (endpoint === '/api/books') {
        return Promise.resolve({ data: [{ id: 1, title: 'Alpha' }] })
      }
      if (endpoint.startsWith('/api/books?')) {
        return Promise.resolve({ data: [] })
      }
      if (endpoint === '/api/books/filters') {
        return Promise.resolve({
          authors: [{ id: 1, name: 'Author A' }],
          categories: [{ id: 2, name: 'Fiction' }],
          publishers: [],
          resourceTypes: ['BOOK'],
          years: { min: 1900, max: 2024 },
          ageGroups: [{ value: 'adult', label: 'Adult' }]
        })
      }
      return Promise.resolve({})
    })

    renderPage()
    await userEvent.click(screen.getByRole('button', { name: /Poka/i }))
    expect(await screen.findByLabelText(/Autor/i)).toBeInTheDocument()

    const searchInput = screen.getByRole('searchbox')
    await userEvent.type(searchInput, 'query')
    expect(screen.getByRole('button', { name: /Wyczy/i })).toBeInTheDocument()

    await userEvent.click(screen.getByRole('button', { name: /Wyczy/i }))
    await waitFor(() => {
      expect(apiFetch).toHaveBeenCalledWith(expect.stringContaining('/api/books?'))
    })
  })
})

