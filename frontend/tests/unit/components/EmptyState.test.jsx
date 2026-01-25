import { render, screen } from '@testing-library/react'
import { describe, it, expect } from 'vitest'
import { FaBook } from 'react-icons/fa'
import EmptyState from '../../../src/EmptyState'

describe('EmptyState', () => {
  it('renders default title and custom message', () => {
    render(<EmptyState message="Brak wyników wyszukiwania" />)

    expect(screen.getByText('Brak danych')).toBeInTheDocument()
    expect(screen.getByText('Brak wyników wyszukiwania')).toBeInTheDocument()
  })

  it('renders custom icon and action content', () => {
    render(
      <EmptyState
        icon={FaBook}
        title="Pusta lista"
        action={<button>Dodaj</button>}
      />
    )

    expect(screen.getByText('Pusta lista')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Dodaj' })).toBeInTheDocument()
  })
})

