import React, { createContext, useContext, useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'

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
  const navigate = useNavigate()

  useEffect(() => {
    if (token) {
      localStorage.setItem('token', token)
      const decoded = decodeJwt(token)
      setUser(decoded)
    } else {
      localStorage.removeItem('token')
      setUser(null)
    }
  }, [token])

  function login(tokenValue) {
    setToken(tokenValue)
    const decoded = decodeJwt(tokenValue)
    setUser(decoded)
    navigate('/')
  }

  function logout() {
    setToken(null)
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
