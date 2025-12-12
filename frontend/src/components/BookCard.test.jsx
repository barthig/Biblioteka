import { describe, it, expect } from 'vitest'
import { render, screen } from '@testing-library/react'
import { BrowserRouter } from 'react-router-dom'
import BookCard from './BookCard'

const renderWithRouter = (component) => {
  return render(<BrowserRouter>{component}</BrowserRouter>)
}

describe('BookCard', () => {
  const mockBook = {
    id: 1,
    title: 'Test Book Title',
    author: { id: 1, name: 'Test Author' },
    isbn: '978-1234567890',
    publicationYear: 2024,
    categories: [{ id: 1, name: 'Fiction' }],
    availableCopies: 3,
    totalCopies: 5,
    coverUrl: null
  }

  it('should render book title', () => {
    renderWithRouter(<BookCard book={mockBook} />)
    expect(screen.getByText('Test Book Title')).toBeInTheDocument()
  })

  it('should render author name', () => {
    renderWithRouter(<BookCard book={mockBook} />)
    expect(screen.getByText(/Test Author/i)).toBeInTheDocument()
  })

  it('should render publication year', () => {
    renderWithRouter(<BookCard book={mockBook} />)
    expect(screen.getByText(/2024/)).toBeInTheDocument()
  })

  it('should show availability status', () => {
    renderWithRouter(<BookCard book={mockBook} />)
    expect(screen.getByText(/3/)).toBeInTheDocument() // available copies
  })

  it('should render "Unavailable" when no copies available', () => {
    const unavailableBook = { ...mockBook, availableCopies: 0 }
    renderWithRouter(<BookCard book={unavailableBook} />)
    expect(screen.getByText(/Niedostępna/i)).toBeInTheDocument()
  })

  it('should render ISBN when provided', () => {
    renderWithRouter(<BookCard book={mockBook} />)
    expect(screen.getByText(/978-1234567890/)).toBeInTheDocument()
  })

  it('should render category when provided', () => {
    renderWithRouter(<BookCard book={mockBook} />)
    expect(screen.getByText(/Fiction/)).toBeInTheDocument()
  })

  it('should handle missing author gracefully', () => {
    const bookNoAuthor = { ...mockBook, author: null }
    renderWithRouter(<BookCard book={bookNoAuthor} />)
    expect(screen.getByText('Test Book Title')).toBeInTheDocument()
  })
})
