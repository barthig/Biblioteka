import { describe, it, expect, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import { BrowserRouter } from 'react-router-dom'
import BookItem from './BookItem'

// Mock AuthContext
vi.mock('../context/AuthContext', () => ({
  useAuth: () => ({ user: null })
}))

const renderWithRouter = (component) => {
  return render(<BrowserRouter>{component}</BrowserRouter>)
}

describe('BookItem', () => {
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
    // Should display 3 available copies
    expect(screen.getByText(/3/)).toBeInTheDocument()
  })

  it('should indicate when book is unavailable', () => {
    const unavailableBook = { ...mockBook, copies: 0, availableCopies: 0 }
    renderWithRouter(<BookItem book={unavailableBook} />)
    // Component should show unavailable message
    expect(screen.getByText(/Brak wolnych egzemplarzy/i)).toBeInTheDocument()
  })
})
