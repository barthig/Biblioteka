import { describe, it, expect, vi } from 'vitest'
import { render, screen } from '@testing-library/react'

vi.mock('../../../src/components/books/SemanticSearch', () => ({
  default: () => <div data-testid="semantic-search" />
}))

// Import after mocks
import SemanticSearchPage from '../../../src/pages/books/SemanticSearchPage'

describe('SemanticSearchPage', () => {
  it('renders header and search component', () => {
    render(<SemanticSearchPage />)
    expect(screen.getByRole('heading', { name: /Wyszukiwanie semantyczne/i })).toBeInTheDocument()
    expect(screen.getByTestId('semantic-search')).toBeInTheDocument()
  })
})

