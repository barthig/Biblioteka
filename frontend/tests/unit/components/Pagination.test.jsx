import { render, screen, fireEvent } from '@testing-library/react'
import { describe, it, expect, vi } from 'vitest'
import Pagination from '../../../src/components/common/Pagination'

describe('Pagination', () => {
  it('returns null when only one page exists', () => {
    const { container } = render(<Pagination currentPage={1} totalPages={1} onPageChange={() => {}} />)
    expect(container.firstChild).toBeNull()
  })

  it('renders page buttons with ellipsis for long ranges', () => {
    render(<Pagination currentPage={5} totalPages={10} onPageChange={() => {}} />)

    expect(screen.getByText('1')).toBeInTheDocument()
    expect(screen.getByText('10')).toBeInTheDocument()
    expect(screen.getAllByText('...').length).toBeGreaterThan(0)
    expect(screen.getByRole('button', { name: '5' })).toHaveClass('active')
  })

  it('calls onPageChange when navigating', () => {
    const onPageChange = vi.fn()
    render(<Pagination currentPage={2} totalPages={3} onPageChange={onPageChange} />)

    fireEvent.click(screen.getByText('← Poprzednia'))
    fireEvent.click(screen.getByText('3'))
    fireEvent.click(screen.getByText('Następna →'))

    expect(onPageChange).toHaveBeenCalledWith(1)
    expect(onPageChange).toHaveBeenCalledWith(3)
    expect(onPageChange).toHaveBeenCalledWith(3)
  })
})

