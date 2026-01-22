import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import { BrowserRouter } from 'react-router-dom'
import userEvent from '@testing-library/user-event'
import SearchBar from './SearchBar'
import * as bookService from '../services/bookService'

vi.mock('../services/bookService')

const mockNavigate = vi.fn()
vi.mock('react-router-dom', async () => {
  const actual = await vi.importActual('react-router-dom')
  return { ...actual, useNavigate: () => mockNavigate }
})

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
    mockNavigate.mockClear()
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

  it('submits search with onSearch callback', async () => {
    const onSearch = vi.fn()
    renderWithRouter(<SearchBar onResults={mockOnResults} onSearch={onSearch} />)

    const input = screen.getByPlaceholderText(/Szukaj/i)
    await userEvent.type(input, 'alpha')
    await userEvent.click(screen.getByRole('button', { name: /Szukaj/i }))

    expect(onSearch).toHaveBeenCalledWith('alpha')
  })

  it('navigates on suggestion click', async () => {
    vi.mocked(bookService.bookService.search).mockResolvedValue({
      items: [{ id: 5, title: 'Alpha', author: 'Author' }],
      total: 1
    })

    renderWithRouter(<SearchBar onResults={mockOnResults} />)
    await userEvent.type(screen.getByPlaceholderText(/Szukaj/i), 'al')

    expect(await screen.findByText('Alpha')).toBeInTheDocument()
    await userEvent.click(screen.getByText('Alpha'))
    expect(mockNavigate).toHaveBeenCalledWith('/books/5')
  })

  it('hides suggestions on outside click', async () => {
    vi.mocked(bookService.bookService.search).mockResolvedValue({
      items: [{ id: 8, title: 'Beta', author: 'Author' }],
      total: 1
    })

    renderWithRouter(<SearchBar onResults={mockOnResults} />)
    await userEvent.type(screen.getByPlaceholderText(/Szukaj/i), 'be')

    expect(await screen.findByText('Beta')).toBeInTheDocument()
    fireEvent.mouseDown(document.body)
    await waitFor(() => {
      expect(screen.queryByText('Beta')).not.toBeInTheDocument()
    })
  })

  it('should display search results count', async () => {
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
      expect(screen.getByText(/Wyniki:\s*2/)).toBeInTheDocument()
    })
  })

  it('should clear search results', async () => {
    renderWithRouter(<SearchBar onResults={mockOnResults} />)
    
    const input = screen.getByPlaceholderText(/Szukaj/i)
    await userEvent.type(input, 'test')
    
    // Clear input
    await userEvent.clear(input)

    expect(mockOnResults).toHaveBeenCalledWith([])
  })
})
