/**
 * RBAC (Role-Based Access Control) utilities
 * 
 * Defines permissions and role hierarchies for the library system
 */

// Available roles in the system
export const ROLES = {
  ADMIN: 'ROLE_ADMIN',
  LIBRARIAN: 'ROLE_LIBRARIAN',
  EMPLOYEE: 'ROLE_EMPLOYEE',
  USER: 'ROLE_USER'
}

// Role hierarchy (higher roles include permissions of lower roles)
export const ROLE_HIERARCHY = {
  [ROLES.ADMIN]: [ROLES.ADMIN, ROLES.LIBRARIAN, ROLES.EMPLOYEE, ROLES.USER],
  [ROLES.LIBRARIAN]: [ROLES.LIBRARIAN, ROLES.EMPLOYEE, ROLES.USER],
  [ROLES.EMPLOYEE]: [ROLES.EMPLOYEE, ROLES.USER],
  [ROLES.USER]: [ROLES.USER]
}

// Permissions in the system
export const PERMISSIONS = {
  // User management
  'users.view': [ROLES.ADMIN, ROLES.LIBRARIAN],
  'users.create': [ROLES.ADMIN],
  'users.edit': [ROLES.ADMIN],
  'users.delete': [ROLES.ADMIN],
  'users.block': [ROLES.ADMIN, ROLES.LIBRARIAN],
  'users.viewFines': [ROLES.ADMIN, ROLES.LIBRARIAN, ROLES.EMPLOYEE],
  
  // Book management
  'books.view': [ROLES.ADMIN, ROLES.LIBRARIAN, ROLES.EMPLOYEE, ROLES.USER],
  'books.create': [ROLES.ADMIN, ROLES.LIBRARIAN],
  'books.edit': [ROLES.ADMIN, ROLES.LIBRARIAN],
  'books.delete': [ROLES.ADMIN],
  'books.manageCopies': [ROLES.ADMIN, ROLES.LIBRARIAN],
  
  // Loan management
  'loans.view': [ROLES.ADMIN, ROLES.LIBRARIAN, ROLES.EMPLOYEE],
  'loans.viewOwn': [ROLES.USER],
  'loans.create': [ROLES.ADMIN, ROLES.LIBRARIAN, ROLES.EMPLOYEE],
  'loans.return': [ROLES.ADMIN, ROLES.LIBRARIAN, ROLES.EMPLOYEE],
  'loans.extend': [ROLES.ADMIN, ROLES.LIBRARIAN, ROLES.EMPLOYEE, ROLES.USER],
  'loans.forceReturn': [ROLES.ADMIN, ROLES.LIBRARIAN],
  
  // Reservation management
  'reservations.view': [ROLES.ADMIN, ROLES.LIBRARIAN, ROLES.EMPLOYEE],
  'reservations.viewOwn': [ROLES.USER],
  'reservations.create': [ROLES.ADMIN, ROLES.LIBRARIAN, ROLES.EMPLOYEE, ROLES.USER],
  'reservations.cancel': [ROLES.ADMIN, ROLES.LIBRARIAN, ROLES.EMPLOYEE, ROLES.USER],
  'reservations.fulfill': [ROLES.ADMIN, ROLES.LIBRARIAN, ROLES.EMPLOYEE],
  
  // Fines management
  'fines.view': [ROLES.ADMIN, ROLES.LIBRARIAN],
  'fines.viewOwn': [ROLES.USER],
  'fines.create': [ROLES.ADMIN, ROLES.LIBRARIAN],
  'fines.waive': [ROLES.ADMIN],
  'fines.pay': [ROLES.ADMIN, ROLES.LIBRARIAN, ROLES.USER],
  
  // Reports and analytics
  'reports.view': [ROLES.ADMIN, ROLES.LIBRARIAN],
  'reports.export': [ROLES.ADMIN],
  'analytics.view': [ROLES.ADMIN, ROLES.LIBRARIAN],
  
  // System settings
  'settings.view': [ROLES.ADMIN],
  'settings.edit': [ROLES.ADMIN],
  'settings.backup': [ROLES.ADMIN],
  
  // Categories and authors
  'categories.manage': [ROLES.ADMIN, ROLES.LIBRARIAN],
  'authors.manage': [ROLES.ADMIN, ROLES.LIBRARIAN],
  
  // Inventory
  'inventory.view': [ROLES.ADMIN, ROLES.LIBRARIAN],
  'inventory.conduct': [ROLES.ADMIN, ROLES.LIBRARIAN],
  
  // Notifications
  'notifications.send': [ROLES.ADMIN, ROLES.LIBRARIAN]
}

/**
 * Check if user roles include a specific role (considering hierarchy)
 */
export function hasRole(userRoles, requiredRole) {
  if (!userRoles || !Array.isArray(userRoles)) return false
  
  return userRoles.some(role => {
    const hierarchy = ROLE_HIERARCHY[role] || [role]
    return hierarchy.includes(requiredRole)
  })
}

/**
 * Check if user roles include any of the required roles
 */
export function hasAnyRole(userRoles, requiredRoles) {
  if (!userRoles || !Array.isArray(userRoles)) return false
  if (!requiredRoles || requiredRoles.length === 0) return true
  
  return requiredRoles.some(required => hasRole(userRoles, required))
}

/**
 * Check if user roles include all of the required roles
 */
export function hasAllRoles(userRoles, requiredRoles) {
  if (!userRoles || !Array.isArray(userRoles)) return false
  if (!requiredRoles || requiredRoles.length === 0) return true
  
  return requiredRoles.every(required => hasRole(userRoles, required))
}

/**
 * Check if user has a specific permission
 */
export function hasPermission(userRoles, permission) {
  if (!userRoles || !Array.isArray(userRoles)) return false
  
  const allowedRoles = PERMISSIONS[permission]
  if (!allowedRoles) {
    console.warn(`Unknown permission: ${permission}`)
    return false
  }
  
  return hasAnyRole(userRoles, allowedRoles)
}

/**
 * Check if user has any of the specified permissions
 */
export function hasAnyPermission(userRoles, permissions) {
  if (!permissions || permissions.length === 0) return true
  return permissions.some(perm => hasPermission(userRoles, perm))
}

/**
 * Check if user has all of the specified permissions
 */
export function hasAllPermissions(userRoles, permissions) {
  if (!permissions || permissions.length === 0) return true
  return permissions.every(perm => hasPermission(userRoles, perm))
}

/**
 * Get all permissions for given roles
 */
export function getPermissionsForRoles(userRoles) {
  if (!userRoles || !Array.isArray(userRoles)) return []
  
  const permissions = new Set()
  
  Object.entries(PERMISSIONS).forEach(([permission, allowedRoles]) => {
    if (hasAnyRole(userRoles, allowedRoles)) {
      permissions.add(permission)
    }
  })
  
  return Array.from(permissions)
}

/**
 * Get user's highest role
 */
export function getHighestRole(userRoles) {
  if (!userRoles || !Array.isArray(userRoles)) return null
  
  const roleOrder = [ROLES.ADMIN, ROLES.LIBRARIAN, ROLES.EMPLOYEE, ROLES.USER]
  
  for (const role of roleOrder) {
    if (userRoles.includes(role)) {
      return role
    }
  }
  
  return userRoles[0] || null
}

/**
 * Check if user is staff (admin, librarian, or employee)
 */
export function isStaff(userRoles) {
  return hasAnyRole(userRoles, [ROLES.ADMIN, ROLES.LIBRARIAN, ROLES.EMPLOYEE])
}

/**
 * Check if user is admin
 */
export function isAdmin(userRoles) {
  return hasRole(userRoles, ROLES.ADMIN)
}

/**
 * Get role display name (Polish)
 */
export function getRoleDisplayName(role) {
  const names = {
    [ROLES.ADMIN]: 'Administrator',
    [ROLES.LIBRARIAN]: 'Bibliotekarz',
    [ROLES.EMPLOYEE]: 'Pracownik',
    [ROLES.USER]: 'Czytelnik'
  }
  return names[role] || role
}

/**
 * Get role badge color
 */
export function getRoleBadgeVariant(role) {
  const variants = {
    [ROLES.ADMIN]: 'purple',
    [ROLES.LIBRARIAN]: 'blue',
    [ROLES.EMPLOYEE]: 'cyan',
    [ROLES.USER]: 'gray'
  }
  return variants[role] || 'gray'
}
