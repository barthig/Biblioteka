import React from 'react'
import PropTypes from 'prop-types'
import { Navigate, useLocation } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext'
import { useRBAC } from '../../hooks/useRBAC'
import { LoadingState } from '../ui/LoadingState'

/**
 * RequireAuth - Requires authentication to access children
 */
export function RequireAuth({ children, fallback }) {
  const { user, token, loading } = useAuth()
  const location = useLocation()

  if (loading) {
    return fallback || <LoadingState text="Sprawdzanie autoryzacji..." />
  }

  if (!token || !user) {
    return <Navigate to="/login" state={{ from: location }} replace />
  }

  return children
}

RequireAuth.propTypes = {
  children: PropTypes.node.isRequired,
  fallback: PropTypes.node
}

/**
 * RequireRole - Requires specific role(s) to access children
 */
export function RequireRole({ 
  roles, 
  children, 
  fallback,
  redirectTo = null,
  showAccessDenied = true
}) {
  const { user, token, loading } = useAuth()
  const { hasAnyRole } = useRBAC()
  const location = useLocation()

  if (loading) {
    return fallback || <LoadingState text="Sprawdzanie autoryzacji..." />
  }

  if (!token || !user) {
    return <Navigate to="/login" state={{ from: location }} replace />
  }

  if (!hasAnyRole(roles)) {
    if (redirectTo) {
      return <Navigate to={redirectTo} replace />
    }
    
    if (showAccessDenied) {
      return (
        <div className="access-denied">
          <div className="access-denied__content">
            <h2>🔒 Brak dostępu</h2>
            <p>Nie masz uprawnień do wyświetlenia tej strony.</p>
            <p className="access-denied__hint">
              Wymagana rola: {roles.map(r => r.replace('ROLE_', '')).join(' lub ')}
            </p>
          </div>
        </div>
      )
    }
    
    return null
  }

  return children
}

RequireRole.propTypes = {
  roles: PropTypes.arrayOf(PropTypes.string).isRequired,
  children: PropTypes.node.isRequired,
  fallback: PropTypes.node,
  redirectTo: PropTypes.string,
  showAccessDenied: PropTypes.bool
}

/**
 * RequirePermission - Requires specific permission(s) to access children
 */
export function RequirePermission({
  permission,
  permissions,
  requireAll = false,
  children,
  fallback = null
}) {
  const { can, canAny, canAll } = useRBAC()

  const permissionList = permission ? [permission] : permissions || []

  const hasAccess = requireAll 
    ? canAll(permissionList)
    : canAny(permissionList)

  if (!hasAccess) {
    return fallback
  }

  return children
}

RequirePermission.propTypes = {
  permission: PropTypes.string,
  permissions: PropTypes.arrayOf(PropTypes.string),
  requireAll: PropTypes.bool,
  children: PropTypes.node.isRequired,
  fallback: PropTypes.node
}

/**
 * Can - Conditional rendering based on permission
 * 
 * @example
 * <Can permission="books.edit">
 *   <EditButton />
 * </Can>
 * 
 * <Can permission="books.edit" fallback={<ViewOnlyMessage />}>
 *   <EditButton />
 * </Can>
 */
export function Can({ permission, children, fallback = null }) {
  const { can } = useRBAC()

  if (!can(permission)) {
    return fallback
  }

  return children
}

Can.propTypes = {
  permission: PropTypes.string.isRequired,
  children: PropTypes.node.isRequired,
  fallback: PropTypes.node
}

/**
 * Cannot - Conditional rendering when permission is NOT granted
 */
export function Cannot({ permission, children }) {
  const { can } = useRBAC()

  if (can(permission)) {
    return null
  }

  return children
}

Cannot.propTypes = {
  permission: PropTypes.string.isRequired,
  children: PropTypes.node.isRequired
}

/**
 * IfAdmin - Show content only for admins
 */
export function IfAdmin({ children, fallback = null }) {
  const { isAdmin } = useRBAC()
  return isAdmin ? children : fallback
}

/**
 * IfStaff - Show content only for staff (admin, librarian, employee)
 */
export function IfStaff({ children, fallback = null }) {
  const { isStaff } = useRBAC()
  return isStaff ? children : fallback
}

/**
 * IfUser - Show content only for regular users
 */
export function IfUser({ children, fallback = null }) {
  const { isStaff } = useRBAC()
  return !isStaff ? children : fallback
}

// Default export for backward compatibility
export default RequireRole
