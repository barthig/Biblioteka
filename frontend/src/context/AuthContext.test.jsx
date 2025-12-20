import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { AuthProvider, useAuth } from './AuthContext'

const mockNavigate = vi.fn()
vi.mock('react-router-dom', () => ({
  useNavigate: () => mockNavigate
}))

function base64UrlEncode(obj) {
  return Buffer.from(JSON.stringify(obj))
    .toString('base64')
    .replace(/\+/g, '-')
    .replace(/\//g, '_')
    .replace(/=+$/, '')
}

function buildToken(payload) {
  const header = base64UrlEncode({ alg: 'none', typ: 'JWT' })
  const body = base64UrlEncode(payload)
  return `${header}.${body}.sig`
}

function AuthConsumer() {
  const { user, login, logout } = useAuth()
  return (
    <div>
      <div data-testid="name">{user?.name || 'none'}</div>
      <button onClick={() => login(buildToken({ sub: 1, name: 'Jan', roles: [], exp: Math.floor(Date.now() / 1000) + 60 }))}>
        Login
      </button>
      <button onClick={logout}>Logout</button>
    </div>
  )
}

describe('AuthContext', () => {
  beforeEach(() => {
    localStorage.clear()
    vi.clearAllMocks()
  })

  it('initializes user from token', () => {
    const token = buildToken({ sub: 1, name: 'Alice', email: 'a@example.com', roles: ['ROLE_USER'], exp: Math.floor(Date.now() / 1000) + 60 })
    localStorage.setItem('token', token)

    render(
      <AuthProvider>
        <AuthConsumer />
      </AuthProvider>
    )

    expect(screen.getByTestId('name')).toHaveTextContent('Alice')
  })

  it('login updates user and logout navigates', async () => {
    render(
      <AuthProvider>
        <AuthConsumer />
      </AuthProvider>
    )

    await userEvent.click(screen.getByRole('button', { name: /Login/i }))
    expect(screen.getByTestId('name')).toHaveTextContent('Jan')

    await userEvent.click(screen.getByRole('button', { name: /Logout/i }))
    expect(mockNavigate).toHaveBeenCalledWith('/login')
  })

  it('clears expired token', () => {
    const expiredToken = buildToken({ sub: 1, name: 'Expired', roles: [], exp: Math.floor(Date.now() / 1000) - 10 })
    localStorage.setItem('token', expiredToken)

    render(
      <AuthProvider>
        <AuthConsumer />
      </AuthProvider>
    )

    expect(screen.getByTestId('name')).toHaveTextContent('none')
    expect(localStorage.getItem('token')).toBeNull()
  })
})
