import { describe, it, expect, vi } from 'vitest'
import { render, screen, fireEvent } from '@testing-library/react'
import SuccessMessage from '../../../src/components/common/SuccessMessage'

describe('SuccessMessage', () => {
  it('renders nothing without message', () => {
    const { container } = render(<SuccessMessage message="" />)
    expect(container.firstChild).toBeNull()
  })

  it('renders message and calls dismiss', () => {
    const onDismiss = vi.fn()
    render(<SuccessMessage message="Saved" onDismiss={onDismiss} />)
    expect(screen.getByText('Saved')).toBeInTheDocument()
    fireEvent.click(screen.getByRole('button', { name: /zamknij/i }))
    expect(onDismiss).toHaveBeenCalled()
  })
})

