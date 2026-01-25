import { render, screen, fireEvent } from '@testing-library/react'
import { describe, it, expect, vi } from 'vitest'
import Modal from '../../../src/components/common/Modal'

describe('Modal', () => {
  it('does not render when closed', () => {
    const { container } = render(<Modal isOpen={false} title="Dialog" />)
    expect(container.firstChild).toBeNull()
  })

  it('renders title, content and footer when open', () => {
    render(
      <Modal isOpen title="Dialog" footer={<div>Akcje</div>}>
        <p>Treść</p>
      </Modal>
    )

    expect(screen.getByText('Dialog')).toBeInTheDocument()
    expect(screen.getByText('Treść')).toBeInTheDocument()
    expect(screen.getByText('Akcje')).toBeInTheDocument()
  })

  it('closes when overlay or close button is clicked', () => {
    const onClose = vi.fn()
    render(
      <Modal isOpen title="Dialog" onClose={onClose}>
        <p>Treść</p>
      </Modal>
    )

    fireEvent.click(screen.getByRole('button', { name: 'Zamknij' }))
    fireEvent.click(screen.getByText('Treść').closest('.modal-overlay'))

    expect(onClose).toHaveBeenCalledTimes(2)
  })
})

