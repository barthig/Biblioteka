import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { BrowserRouter } from 'react-router-dom'
import BookItem from './BookItem'
import { apiFetch } from '../api'

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

let mockUser = null
vi.mock('../context/AuthContext', () => ({
  useAuth: () => ({ user: mockUser })
}))

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

describe('BookItem', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    mockUser = null
  })

  const mockBook = {
    id: 1,
    title: 'Test Book Title',
    author: { name: 'Test Author' },
    isbn: '978-1234567890',
    publicationYear: 2024,
    categories: [{ id: 1, name: 'Fiction' }],
    copies: 3,
    totalCopies: 5,
    publisher: 'Test Publisher'
  }

  it('should render book title', () => {
    renderWithRouter(<BookItem book={mockBook} />)
    expect(screen.getByText('Test Book Title')).toBeInTheDocument()
  })

  it('should render author name', () => {
    renderWithRouter(<BookItem book={mockBook} />)
    expect(screen.getByText(/Test Author/i)).toBeInTheDocument()
  })

  it('should show availability count', () => {
    renderWithRouter(<BookItem book={mockBook} />)
    // Should display "Dostępne 3/5"
    expect(screen.getByText(/Dostępne 3\/5/)).toBeInTheDocument()
  })

  it('should indicate when book is unavailable', () => {
    const unavailableBook = { ...mockBook, copies: 0, availableCopies: 0 }
    renderWithRouter(<BookItem book={unavailableBook} />)
    // Component should show unavailable message
    expect(screen.getByText(/Brak wolnych egzemplarzy/i)).toBeInTheDocument()
  })

  it('prompts login when borrowing without user', async () => {
    renderWithRouter(<BookItem book={mockBook} />)
    expect(screen.getByRole('link', { name: /Zaloguj/i })).toBeInTheDocument()
  })

  it('borrows, reserves, and toggles favorite when logged in', async () => {
    mockUser = { id: 10 }
    apiFetch.mockResolvedValue({})
    const { rerender } = renderWithRouter(<BookItem book={{ ...mockBook, copies: 1 }} />)

    await userEvent.click(screen.getByRole('button', { name: /Wypo/i }))
    expect(apiFetch).toHaveBeenCalledWith('/api/loans', expect.objectContaining({ method: 'POST' }))

    rerender(
      <BrowserRouter
        future={{
          v7_startTransition: true,
          v7_relativeSplatPath: true
        }}
      >
        <BookItem book={{ ...mockBook, copies: 0 }} />
      </BrowserRouter>
    )

    await userEvent.click(screen.getByRole('button', { name: /kolejki/i }))
    expect(apiFetch).toHaveBeenCalledWith('/api/reservations', expect.objectContaining({ method: 'POST' }))

    await userEvent.click(screen.getByRole('button', { name: /Dodaj do ulub/i }))
    expect(apiFetch).toHaveBeenCalledWith('/api/favorites', expect.objectContaining({ method: 'POST' }))
  })
})
