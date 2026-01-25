import React from 'react'
import { Navigate, useLocation } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext'

export default function RequireRole({ allowed, children }) {
  const { user, token } = useAuth()
  const location = useLocation()
  
  // Check for valid authentication - user must be present (token validated)
  const isAuthenticated = Boolean(user)
  const roles = user?.roles || []
  const isAllowed = roles.some(role => allowed.includes(role))

  // If token exists but user not yet loaded, show loading state
  if (token && !user) {
    return (
      <div className="page page--centered">
        <div className="surface-card form-card">
          <p>Ładowanie...</p>
        </div>
      </div>
    )
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" state={{ from: location }} replace />
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
