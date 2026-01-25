/**
 * GuestGuard - Protects routes that should only be accessible to non-authenticated users
 * (e.g., login, register pages)
 */
import { Navigate, useLocation } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { LoadingSpinner } from '../components/common'

export function GuestGuard({ children, redirectTo = '/dashboard' }) {
  const { user, loading } = useAuth()
  const location = useLocation()

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <LoadingSpinner />
      </div>
    )
  }

  if (user) {
    // If user came from somewhere, redirect back; otherwise go to default
    const from = location.state?.from?.pathname || redirectTo
    return <Navigate to={from} replace />
  }

  return children
}

export default GuestGuard
