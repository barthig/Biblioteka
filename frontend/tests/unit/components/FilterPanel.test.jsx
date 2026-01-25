import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import FilterPanel from '../../../src/components/common/FilterPanel'

const baseFilters = {
  genres: ['Fantasy', 'Sci-Fi'],
  authors: ['Author A', 'Author B'],
  years: ['2020', '2021']
}

describe('FilterPanel', () => {
  const onFilterChange = vi.fn()

  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('toggles dropdown and renders available filters', async () => {
    render(<FilterPanel filters={{}} onFilterChange={onFilterChange} availableFilters={baseFilters} />)

    await userEvent.click(screen.getByRole('button', { name: /filtry/i }))
    expect(screen.getByRole('heading', { level: 3, name: /filtry/i })).toBeInTheDocument()
    const selects = screen.getAllByRole('combobox')
    expect(selects).toHaveLength(3)
  })

  it('notifies when a filter value is changed', async () => {
    render(<FilterPanel filters={{}} onFilterChange={onFilterChange} availableFilters={baseFilters} />)

    await userEvent.click(screen.getByRole('button', { name: /filtry/i }))
    const genreSelect = screen.getAllByRole('combobox')[0]
    await userEvent.selectOptions(genreSelect, 'Fantasy')

    expect(onFilterChange).toHaveBeenCalledWith({ genre: 'Fantasy' })
  })

  it('shows active filter count badge', () => {
    render(
      <FilterPanel
        filters={{ genre: 'Fantasy', year: '2020' }}
        onFilterChange={onFilterChange}
        availableFilters={baseFilters}
      />
    )

    expect(screen.getByText('2')).toBeInTheDocument()
  })

  it('clears all filters when clear button is clicked', async () => {
    render(
      <FilterPanel
        filters={{ genre: 'Fantasy', availableOnly: true }}
        onFilterChange={onFilterChange}
        availableFilters={baseFilters}
      />
    )

    await userEvent.click(screen.getByRole('button', { name: /filtry/i }))
    await userEvent.click(screen.getByRole('button', { name: /Wyczy/i }))

    expect(onFilterChange).toHaveBeenCalledWith({})
  })
})

