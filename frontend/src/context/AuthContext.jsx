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
      email: payload.email ?? null,
      name: payload.name ?? null,
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
  const [user, setUser] = useState(() => {
    // Initialize user immediately from token if available
    const initialToken = localStorage.getItem('token')
    if (initialToken) {
      return decodeJwt(initialToken)
    }
    return null
  })
  const navigate = useNavigate()

  useEffect(() => {
    if (token) {
      localStorage.setItem('token', token)
      
      // Decode JWT and set user immediately (no backend call needed)
      const decoded = decodeJwt(token)
      if (decoded) {
        setUser(decoded)
      } else {
        // Invalid token, clear it
        setToken(null)
        localStorage.removeItem('token')
        setUser(null)
      }
    } else {
      localStorage.removeItem('token')
      setUser(null)
    }
  }, [token])

  function login(tokenValue) {
    setToken(tokenValue)
    
    // Immediately decode JWT and set user (no waiting)
    const decoded = decodeJwt(tokenValue)
    if (decoded) {
      setUser(decoded)
    }
    
    // Navigate instantly
    navigate('/')
    // Full profile will be loaded by useEffect in the background
  }

  function logout() {
    setToken(null)
    setUser(null)
    navigate('/login')
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
