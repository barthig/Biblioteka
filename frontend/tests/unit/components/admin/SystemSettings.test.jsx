import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import SystemSettings from '../../../../src/SystemSettings'

describe('SystemSettings', () => {
  const baseProps = {
    settings: [],
    integrations: [],
    loading: false,
    systemLoaded: true,
    loadSystem: vi.fn(),
    updateSetting: vi.fn(),
    integrationForm: { name: '', provider: '', endpoint: '', apiKey: '', enabled: false },
    setIntegrationForm: vi.fn(),
    createIntegration: vi.fn((event) => event.preventDefault()),
    toggleIntegration: vi.fn(),
    testIntegration: vi.fn()
  }

  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('calls updateSetting via edit button', async () => {
    const props = {
      ...baseProps,
      settings: [{ key: 'site_name', value: 'Library' }]
    }
    vi.spyOn(globalThis, 'prompt').mockReturnValue('New Name')
    render(<SystemSettings {...props} />)

    await userEvent.click(screen.getByRole('button', { name: /Edytuj/i }))
    expect(props.updateSetting).toHaveBeenCalledWith('site_name', 'New Name')
    globalThis.prompt.mockRestore()
  })

  it('submits integration form', async () => {
    const props = {
      ...baseProps,
      integrationForm: {
        name: 'Test',
        provider: 'http',
        endpoint: 'https://example.test',
        apiKey: '',
        enabled: true
      }
    }
    render(<SystemSettings {...props} />)
    await userEvent.click(screen.getByRole('button', { name: /Zapisz/i }))
    expect(props.createIntegration).toHaveBeenCalled()
  })
})

