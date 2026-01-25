import { describe, it, expect, vi } from 'vitest'
import { render, screen, fireEvent } from '@testing-library/react'
import { StarRating, RatingDisplay } from '../../../src/components/books/StarRating'

describe('StarRating', () => {
  it('fills stars based on rating and handles click', () => {
    const onRate = vi.fn()
    render(<StarRating rating={3} onRate={onRate} />)
    const stars = screen.getAllByRole('button')
    expect(stars[0].className).toContain('star--filled')
    expect(stars[2].className).toContain('star--filled')
    expect(stars[4].className).not.toContain('star--filled')
    fireEvent.click(stars[3])
    expect(onRate).toHaveBeenCalledWith(4)
  })

  it('disables clicks when readonly', () => {
    const onRate = vi.fn()
    render(<StarRating rating={2} onRate={onRate} readonly />)
    const stars = screen.getAllByRole('button')
    expect(stars[0]).toBeDisabled()
    fireEvent.click(stars[1])
    expect(onRate).not.toHaveBeenCalled()
  })
})

describe('RatingDisplay', () => {
  it('shows empty state when no ratings', () => {
    render(<RatingDisplay averageRating={0} ratingCount={0} />)
    expect(screen.getByText(/Brak ocen/i)).toBeInTheDocument()
  })

  it('shows average and count', () => {
    render(<RatingDisplay averageRating={4.5} ratingCount={12} />)
    expect(screen.getByText('4.5')).toBeInTheDocument()
    expect(screen.getByText('(12)')).toBeInTheDocument()
  })
})

