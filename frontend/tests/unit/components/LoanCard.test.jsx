import { describe, it, expect } from 'vitest'
import { render, screen } from '@testing-library/react'
import LoanCard from '../../../src/LoanCard'

describe('LoanCard', () => {
  const mockLoan = {
    id: 1,
    book: {
      id: 10,
      title: 'Borrowed Book',
      author: 'Author Name'
    },
    borrowedAt: '2024-12-01T10:00:00Z',
    dueAt: '2024-12-15T10:00:00Z',
    returnedAt: null
  }

  it('should render book title', () => {
    render(<LoanCard loan={mockLoan} />)
    expect(screen.getByText('Borrowed Book')).toBeInTheDocument()
  })

  it('should display author name', () => {
    render(<LoanCard loan={mockLoan} />)
    expect(screen.getByText(/Author Name/i)).toBeInTheDocument()
  })

  it('should show borrowed date', () => {
    render(<LoanCard loan={mockLoan} />)
    // Check date format (day.month.year)
    expect(screen.getByText(/01\.12\.2024/)).toBeInTheDocument()
  })
})

