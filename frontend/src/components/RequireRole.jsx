import React from 'react'
import { Navigate, useLocation } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

export default function RequireRole({ allowed, children }) {
  const { user, token } = useAuth()
  const location = useLocation()
  
  // Check both context and localStorage for authentication status
  const isAuthenticated = Boolean(token || localStorage.getItem('token'))
  const roles = user?.roles || []
  const isAllowed = roles.some(role => allowed.includes(role))

  if (!isAuthenticated) {
    return <Navigate to="/login" state={{ from: location }} replace />
  }

  // If authenticated but user data not yet loaded from context, wait
  if (!user && isAuthenticated) {
    return (
      <div className="page page--centered">
        <div className="surface-card form-card">
          <p>Ładowanie...</p>
        </div>
      </div>
    )
  }

  if (!isAllowed) {
    return (
      <div className="page page--centered">
        <div className="surface-card form-card">
          <h2>Brak dostępu</h2>
          <p>To miejsce jest dostępne tylko dla ról: {allowed.join(', ')}.</p>
        </div>
      </div>
    )
  }

  return children
}
