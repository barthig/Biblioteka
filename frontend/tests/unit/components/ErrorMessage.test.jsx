import { describe, it, expect, vi } from 'vitest'
import { render, screen, fireEvent } from '@testing-library/react'
import ErrorMessage from '../../../src/ErrorMessage'

describe('ErrorMessage', () => {
  it('renders nothing without error', () => {
    const { container } = render(<ErrorMessage error={null} />)
    expect(container.firstChild).toBeNull()
  })

  it('renders string error', () => {
    render(<ErrorMessage error="Boom" />)
    expect(screen.getByText('Boom')).toBeInTheDocument()
  })

  it('renders error object and calls dismiss', () => {
    const onDismiss = vi.fn()
    render(<ErrorMessage error={new Error('Failed')} onDismiss={onDismiss} />)
    fireEvent.click(screen.getByRole('button', { name: /zamknij/i }))
    expect(onDismiss).toHaveBeenCalled()
  })
})

