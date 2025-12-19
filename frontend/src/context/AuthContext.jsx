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
    
    // Check if token is expired
    const now = Math.floor(Date.now() / 1000)
    if (payload.exp && payload.exp < now) {
      console.warn('JWT token expired')
      return null
    }
    
    return {
      id: payload.sub ?? null,
      email: payload.email ?? null,
      name: payload.name ?? null,
      roles: Array.isArray(payload.roles) ? payload.roles : [],
      exp: payload.exp,
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
  const [isInitialized, setIsInitialized] = useState(false)
  
  // Mark as initialized after first render
  React.useEffect(() => {
    setIsInitialized(true)
  }, [])

  useEffect(() => {
    if (token) {
      localStorage.setItem('token', token)
      
      // Decode JWT and set user immediately (no backend call needed)
      const decoded = decodeJwt(token)
      if (decoded) {
        setUser(decoded)
        
        // Set up auto-logout when token expires
        const now = Math.floor(Date.now() / 1000)
        const expiresIn = decoded.exp ? (decoded.exp - now) * 1000 : null
        
        if (expiresIn && expiresIn > 0) {
          const timeoutId = setTimeout(() => {
            setToken(null)
            setUser(null)
            localStorage.removeItem('token')
            if (isInitialized) {
              navigate('/login', { state: { message: 'Sesja wygasła, zaloguj się ponownie' } })
            }
          }, expiresIn)
          
          return () => clearTimeout(timeoutId)
        }
      } else {
        // Invalid or expired token, clear it
        setToken(null)
        localStorage.removeItem('token')
        setUser(null)
      }
    } else {
      localStorage.removeItem('token')
      setUser(null)
    }
  }, [token, navigate, isInitialized])

  function login(tokenValue) {
    setToken(tokenValue)
    
    // Immediately decode JWT and set user (no waiting)
    const decoded = decodeJwt(tokenValue)
    if (decoded) {
      setUser(decoded)
    }
    // Note: navigation is handled by the Login component
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
