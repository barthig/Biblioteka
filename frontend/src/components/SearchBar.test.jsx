import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import { BrowserRouter } from 'react-router-dom'
import userEvent from '@testing-library/user-event'
import SearchBar from './SearchBar'
import * as bookService from '../services/bookService'

vi.mock('../services/bookService')

const renderWithRouter = (component) => {
  return render(
    <BrowserRouter
      future={{
        v7_startTransition: true,
        v7_relativeSplatPath: true
      }}
    >
      {component}
    </BrowserRouter>
  )
}

describe('SearchBar', () => {
  const mockOnResults = vi.fn()

  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should render search input', () => {
    renderWithRouter(<SearchBar onResults={mockOnResults} />)
    expect(screen.getByPlaceholderText(/Szukaj/i)).toBeInTheDocument()
  })

  it('should call search when typing query', async () => {
    const mockResults = {
      items: [{ id: 1, title: 'Found Book' }],
      total: 1
    }
    vi.mocked(bookService.bookService.search).mockResolvedValue(mockResults)

    renderWithRouter(<SearchBar onResults={mockOnResults} />)
    
    const input = screen.getByPlaceholderText(/Szukaj/i)
    await userEvent.type(input, 'test query')

    await waitFor(() => {
      expect(bookService.bookService.search).toHaveBeenCalledWith('test query')
    })
  })

  it('should debounce search requests', async () => {
    vi.mocked(bookService.bookService.search).mockResolvedValue({ items: [], total: 0 })

    renderWithRouter(<SearchBar onResults={mockOnResults} />)
    
    const input = screen.getByPlaceholderText(/Szukaj/i)
    
    // Type multiple characters quickly
    await userEvent.type(input, 'abc')

    // Should only call once after debounce
    await waitFor(() => {
      expect(bookService.bookService.search).toHaveBeenCalledTimes(1)
    }, { timeout: 1000 })
  })

  it('should show loading state while searching', async () => {
    let resolveSearch
    const searchPromise = new Promise(resolve => { resolveSearch = resolve })
    vi.mocked(bookService.bookService.search).mockReturnValue(searchPromise)

    renderWithRouter(<SearchBar onResults={mockOnResults} />)

    const input = screen.getByPlaceholderText(/Szukaj/i)
    await userEvent.type(input, 'query')

    await waitFor(() => {
      expect(screen.getByText(/Wyszukiwanie/i)).toBeInTheDocument()
    })

    resolveSearch({ items: [{ id: 1, title: 'Book', author: 'Author' }], total: 1 })

    await waitFor(() => {
      expect(screen.getByText('Book')).toBeInTheDocument()
    })
  })

  it('should not search with empty query', async () => {
    renderWithRouter(<SearchBar onResults={mockOnResults} />)

    const input = screen.getByPlaceholderText(/Szukaj/i)
    await userEvent.type(input, '   ')

    await waitFor(() => {
      expect(bookService.bookService.search).not.toHaveBeenCalled()
    })
  })

  it.skip('should display search results count', async () => {
    const mockResults = {
      items: [
        { id: 1, title: 'Book 1' },
        { id: 2, title: 'Book 2' }
      ],
      total: 2
    }
    vi.mocked(bookService.bookService.search).mockResolvedValue(mockResults)

    renderWithRouter(<SearchBar onResults={mockOnResults} />)
    
    const input = screen.getByPlaceholderText(/Szukaj/i)
    await userEvent.type(input, 'test')

    await waitFor(() => {
      expect(screen.getByText(/2/)).toBeInTheDocument()
    })
  })

  it.skip('should clear search results', async () => {
    renderWithRouter(<SearchBar onResults={mockOnResults} />)
    
    const input = screen.getByPlaceholderText(/Szukaj/i)
    await userEvent.type(input, 'test')
    
    // Clear input
    await userEvent.clear(input)

    expect(mockOnResults).toHaveBeenCalledWith([])
  })
})
