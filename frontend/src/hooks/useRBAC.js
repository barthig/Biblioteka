import { useCallback, useMemo } from 'react'
import { useAuth } from '../context/AuthContext'
import {
  hasRole,
  hasAnyRole,
  hasPermission,
  hasAnyPermission,
  hasAllPermissions,
  getPermissionsForRoles,
  getHighestRole,
  isStaff,
  isAdmin,
  ROLES
} from '../utils/rbac'

/**
 * useRBAC - Hook for role-based access control
 * 
 * @example
 * const { can, hasRole, isAdmin, isStaff } = useRBAC();
 * 
 * if (can('books.edit')) {
 *   // Show edit button
 * }
 */
export function useRBAC() {
  const { user } = useAuth()
  const userRoles = useMemo(() => user?.roles || [], [user])

  // Check single permission
  const can = useCallback((permission) => {
    return hasPermission(userRoles, permission)
  }, [userRoles])

  // Check any of permissions
  const canAny = useCallback((permissions) => {
    return hasAnyPermission(userRoles, permissions)
  }, [userRoles])

  // Check all permissions
  const canAll = useCallback((permissions) => {
    return hasAllPermissions(userRoles, permissions)
  }, [userRoles])

  // Check if user has role
  const checkRole = useCallback((role) => {
    return hasRole(userRoles, role)
  }, [userRoles])

  // Check if user has any of roles
  const hasAnyOfRoles = useCallback((roles) => {
    return hasAnyRole(userRoles, roles)
  }, [userRoles])

  // Get all user permissions
  const permissions = useMemo(() => {
    return getPermissionsForRoles(userRoles)
  }, [userRoles])

  // Get highest role
  const highestRole = useMemo(() => {
    return getHighestRole(userRoles)
  }, [userRoles])

  return {
    // Permission checks
    can,
    canAny,
    canAll,
    
    // Role checks
    hasRole: checkRole,
    hasAnyRole: hasAnyOfRoles,
    
    // Shortcuts
    isAdmin: isAdmin(userRoles),
    isStaff: isStaff(userRoles),
    isLibrarian: hasRole(userRoles, ROLES.LIBRARIAN),
    isEmployee: hasRole(userRoles, ROLES.EMPLOYEE),
    isUser: hasRole(userRoles, ROLES.USER),
    
    // User info
    roles: userRoles,
    permissions,
    highestRole,
    
    // Constants
    ROLES
  }
}

export default useRBAC
