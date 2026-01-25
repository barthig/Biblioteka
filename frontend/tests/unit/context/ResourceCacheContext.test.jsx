import { describe, it, expect, vi } from 'vitest'
import { render, waitFor } from '@testing-library/react'
import { ResourceCacheProvider, useResourceCache } from '../../../src/context/ResourceCacheContext'

function CacheConsumer({ onReady }) {
  const api = useResourceCache()
  onReady(api)
  return null
}

describe('ResourceCacheContext', () => {
  it('prefetches and caches resources', async () => {
    const onReady = vi.fn()
    render(
      <ResourceCacheProvider>
        <CacheConsumer onReady={onReady} />
      </ResourceCacheProvider>
    )

    const api = onReady.mock.calls[0][0]
    const loader = vi.fn().mockResolvedValue('value')
    const value1 = await api.prefetchResource('key', loader)
    const value2 = await api.prefetchResource('key', loader)

    expect(value1).toBe('value')
    expect(value2).toBe('value')
    expect(loader).toHaveBeenCalledTimes(1)
  })

  it('invalidates by prefix', async () => {
    const onReady = vi.fn()
    render(
      <ResourceCacheProvider>
        <CacheConsumer onReady={onReady} />
      </ResourceCacheProvider>
    )

    const api = onReady.mock.calls[0][0]
    api.setCachedResource('books:1', { id: 1 })
    api.setCachedResource('books:2', { id: 2 })
    api.invalidateResource('books:*')

    await waitFor(() => {
      expect(api.getCachedResource('books:1')).toBeUndefined()
      expect(api.getCachedResource('books:2')).toBeUndefined()
    })
  })
})

