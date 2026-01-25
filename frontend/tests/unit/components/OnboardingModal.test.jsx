import { describe, it, expect, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import OnboardingModal from '../../../src/OnboardingModal'
import { apiFetch } from '../api'

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

describe('OnboardingModal', () => {
  it('submits selected categories and calls onComplete', async () => {
    apiFetch.mockResolvedValue({})
    const onComplete = vi.fn()
    render(<OnboardingModal onComplete={onComplete} />)

    await userEvent.click(screen.getByRole('button', { name: /Krymina/i }))
    await userEvent.click(screen.getByRole('button', { name: /Kontynuuj/i }))

    await waitFor(() => {
      expect(apiFetch).toHaveBeenCalledWith('/api/users/me/onboarding', expect.objectContaining({ method: 'POST' }))
      expect(onComplete).toHaveBeenCalled()
    })
  })

  it('allows skipping onboarding', () => {
    const onComplete = vi.fn()
    render(<OnboardingModal onComplete={onComplete} />)
    screen.getByRole('button', { name: /Pomi/i }).click()
    expect(onComplete).toHaveBeenCalled()
  })
})

