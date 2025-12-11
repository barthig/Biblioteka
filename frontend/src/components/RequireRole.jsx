import React from 'react'
import { Navigate, useLocation } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

export default function RequireRole({ allowed, children }) {
  const { user } = useAuth()
  const location = useLocation()
  const roles = user?.roles || []
  const isAllowed = roles.some(role => allowed.includes(role))

  if (!user) {
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
