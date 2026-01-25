/**
 * RoleGuard - Protects routes that require specific roles
 */
import { Navigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { LoadingSpinner } from '../components/common'
import { ROLES } from '../constants/enums'

export function RoleGuard({ 
  children, 
  allowedRoles = [], 
  fallbackPath = '/dashboard',
  showForbidden = false 
}) {
  const { user, loading } = useAuth()

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <LoadingSpinner />
      </div>
    )
  }

  if (!user) {
    return <Navigate to="/login" replace />
  }

  const userRoles = user.roles || []
  const hasRequiredRole = allowedRoles.some(role => userRoles.includes(role))

  if (!hasRequiredRole) {
    if (showForbidden) {
      return (
        <div className="flex flex-col items-center justify-center min-h-screen">
          <h1 className="text-4xl font-bold text-red-600 mb-4">403</h1>
          <p className="text-xl text-gray-600">Access Denied</p>
          <p className="text-gray-500 mt-2">You don't have permission to access this page.</p>
        </div>
      )
    }
    return <Navigate to={fallbackPath} replace />
  }

  return children
}

// Convenience components for common role guards
export function AdminGuard({ children }) {
  return (
    <RoleGuard allowedRoles={[ROLES.ADMIN]} showForbidden>
      {children}
    </RoleGuard>
  )
}

export function LibrarianGuard({ children }) {
  return (
    <RoleGuard allowedRoles={[ROLES.ADMIN, ROLES.LIBRARIAN]} showForbidden>
      {children}
    </RoleGuard>
  )
}

export function StaffGuard({ children }) {
  return (
    <RoleGuard allowedRoles={[ROLES.ADMIN, ROLES.LIBRARIAN, ROLES.STAFF]} showForbidden>
      {children}
    </RoleGuard>
  )
}

export default RoleGuard
