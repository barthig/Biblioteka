import React from 'react'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { act, fireEvent, render, screen, waitFor } from '@testing-library/react'

vi.mock('../../../src/registerServiceWorker', () => ({
  reloadForPwaUpdate: vi.fn(),
  triggerPwaInstall: vi.fn(async () => true)
}))

import PwaStatusBanner from '../../../src/components/common/PwaStatusBanner'
import { reloadForPwaUpdate, triggerPwaInstall } from '../../../src/registerServiceWorker'

describe('PwaStatusBanner', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    Object.defineProperty(window.navigator, 'onLine', {
      configurable: true,
      value: true
    })
  })

  it('renders install banner when install prompt is available', async () => {
    render(<PwaStatusBanner />)

    await act(async () => {
      window.dispatchEvent(new CustomEvent('pwa:install-available', { detail: { available: true } }))
    })

    expect(await screen.findByRole('button', { name: /Zainstaluj/i })).toBeInTheDocument()
    fireEvent.click(screen.getByRole('button', { name: /Zainstaluj/i }))

    await waitFor(() => expect(triggerPwaInstall).toHaveBeenCalled())
  })

  it('renders update banner and triggers reload action', async () => {
    render(<PwaStatusBanner />)

    await act(async () => {
      window.dispatchEvent(new CustomEvent('pwa:update-available'))
    })

    expect(await screen.findByRole('button', { name: /Odswiez aplikacje/i })).toBeInTheDocument()
    fireEvent.click(screen.getByRole('button', { name: /Odswiez aplikacje/i }))

    expect(reloadForPwaUpdate).toHaveBeenCalled()
  })

  it('shows offline banner when browser goes offline', async () => {
    render(<PwaStatusBanner />)

    Object.defineProperty(window.navigator, 'onLine', {
      configurable: true,
      value: false
    })

    await act(async () => {
      window.dispatchEvent(new Event('offline'))
    })

    expect(await screen.findByText(/Tryb offline/i)).toBeInTheDocument()
  })
})