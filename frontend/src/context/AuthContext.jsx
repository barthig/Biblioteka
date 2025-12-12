import React, { createContext, useContext, useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { apiFetch } from '../api'

const AuthContext = createContext(null)

function base64Decode(input) {
  if (typeof atob === 'function') {
    return atob(input)
  }
  if (typeof Buffer === 'function') {
    return Buffer.from(input, 'base64').toString('binary')
  }
  throw new Error('Brak dekodera base64')
}

function decodeJwt(token) {
  try {
    const parts = token.split('.')
    if (parts.length !== 3) return null
    const base64 = parts[1].replace(/-/g, '+').replace(/_/g, '/').padEnd(Math.ceil(parts[1].length / 4) * 4, '=')
    const payload = JSON.parse(base64Decode(base64))
    return {
      id: payload.sub ?? null,
      roles: Array.isArray(payload.roles) ? payload.roles : [],
      raw: payload,
    }
  } catch (err) {
    console.warn('Failed to decode JWT', err)
    return null
  }
}

export function AuthProvider({ children }) {
  const [token, setToken] = useState(() => localStorage.getItem('token'))
  const [user, setUser] = useState(null)
  const [loading, setLoading] = useState(true)
  const navigate = useNavigate()

  useEffect(() => {
    async function loadUser() {
      if (token) {
        localStorage.setItem('token', token)
        try {
          // Fetch full user data from server
          const userData = await apiFetch('/api/auth/profile')
          setUser(userData)
        } catch (err) {
          console.error('Failed to load user profile:', err)
          // Fallback to JWT decode if profile fetch fails
          const decoded = decodeJwt(token)
          if (decoded) {
            setUser(decoded)
          } else {
            // Invalid token, clear it
            setToken(null)
            localStorage.removeItem('token')
          }
        }
      } else {
        localStorage.removeItem('token')
        setUser(null)
      }
      setLoading(false)
    }

    loadUser()
  }, [token])

  function login(tokenValue) {
    setToken(tokenValue)
    // User will be loaded by useEffect
    navigate('/')
  }

  function logout() {
    setToken(null)
    setUser(null)
    navigate('/login')
  }

  if (loading) {
    return (
      <div className="app-shell">
        <div className="page page--centered">
          <div className="surface-card">
            <p>Ładowanie...</p>
          </div>
        </div>
      </div>
    )
  }

  return (
    <AuthContext.Provider value={{ token, user, login, logout }}>
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth() {
  return useContext(AuthContext)
}
