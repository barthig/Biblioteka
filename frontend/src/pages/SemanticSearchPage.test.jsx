import { describe, it, expect, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import SemanticSearchPage from './SemanticSearchPage'

vi.mock('../components/SemanticSearch', () => ({
  default: () => <div data-testid="semantic-search" />
}))

describe('SemanticSearchPage', () => {
  it('renders header and search component', () => {
    render(<SemanticSearchPage />)
    expect(screen.getByRole('heading', { name: /Wyszukiwanie semantyczne/i })).toBeInTheDocument()
    expect(screen.getByTestId('semantic-search')).toBeInTheDocument()
  })
})
