import { describe, it, expect, vi } from 'vitest'
import { render, screen, fireEvent } from '@testing-library/react'
import OnboardingModal from './OnboardingModal'
import { apiFetch } from '../api'

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

describe('OnboardingModal', () => {
  it('submits selected categories and calls onComplete', async () => {
    apiFetch.mockResolvedValue({})
    const onComplete = vi.fn()
    render(<OnboardingModal onComplete={onComplete} />)

    fireEvent.click(screen.getByRole('button', { name: /Krymina/i }))
    fireEvent.click(screen.getByRole('button', { name: /Kontynuuj/i }))

    expect(apiFetch).toHaveBeenCalledWith('/api/users/me/onboarding', expect.objectContaining({ method: 'POST' }))
    expect(onComplete).toHaveBeenCalled()
  })

  it('allows skipping onboarding', () => {
    const onComplete = vi.fn()
    render(<OnboardingModal onComplete={onComplete} />)
    fireEvent.click(screen.getByRole('button', { name: /Pomi/i }))
    expect(onComplete).toHaveBeenCalled()
  })
})
