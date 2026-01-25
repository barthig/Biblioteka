/**
 * AuthGuard - Protects routes that require authentication
 */
import { Navigate, useLocation } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { LoadingSpinner } from '../components/common'

export function AuthGuard({ children }) {
  const { user, loading } = useAuth()
  const location = useLocation()

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <LoadingSpinner />
      </div>
    )
  }

  if (!user) {
    // Redirect to login, save intended destination
    return <Navigate to="/login" state={{ from: location }} replace />
  }

  return children
}

export default AuthGuard
