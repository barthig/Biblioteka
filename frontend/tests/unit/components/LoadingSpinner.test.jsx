import { describe, it, expect } from 'vitest'
import { render, screen } from '@testing-library/react'
import LoadingSpinner from '../../../src/components/common/LoadingSpinner'

describe('LoadingSpinner', () => {
  it('renders default message', () => {
    render(<LoadingSpinner />)
    expect(screen.getByText(/adowanie/i)).toBeInTheDocument()
  })

  it('renders custom size and message', () => {
    render(<LoadingSpinner size="large" message="Loading..." />)
    expect(screen.getByText('Loading...')).toBeInTheDocument()
    expect(document.querySelector('.spinner-large')).toBeTruthy()
  })
})

