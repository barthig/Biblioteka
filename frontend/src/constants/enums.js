/**
 * Application enums and constants
 * Single source of truth for all app-wide values
 */

// ============================================
// USER ROLES
// ============================================
export const ROLES = {
  ADMIN: 'ROLE_ADMIN',
  LIBRARIAN: 'ROLE_LIBRARIAN',
  STAFF: 'ROLE_STAFF',
  USER: 'ROLE_USER',
}

export const ROLE_LABELS = {
  [ROLES.ADMIN]: 'Administrator',
  [ROLES.LIBRARIAN]: 'Bibliotekarz',
  [ROLES.STAFF]: 'Pracownik',
  [ROLES.USER]: 'Użytkownik',
}

export const ROLE_HIERARCHY = [ROLES.USER, ROLES.STAFF, ROLES.LIBRARIAN, ROLES.ADMIN]

// ============================================
// LOAN STATUS
// ============================================
export const LOAN_STATUS = {
  ACTIVE: 'active',
  RETURNED: 'returned',
  OVERDUE: 'overdue',
  LOST: 'lost',
  EXTENDED: 'extended',
}

export const LOAN_STATUS_LABELS = {
  [LOAN_STATUS.ACTIVE]: 'Aktywne',
  [LOAN_STATUS.RETURNED]: 'Zwrócone',
  [LOAN_STATUS.OVERDUE]: 'Przeterminowane',
  [LOAN_STATUS.LOST]: 'Zgubione',
  [LOAN_STATUS.EXTENDED]: 'Przedłużone',
}

export const LOAN_STATUS_COLORS = {
  [LOAN_STATUS.ACTIVE]: 'green',
  [LOAN_STATUS.RETURNED]: 'gray',
  [LOAN_STATUS.OVERDUE]: 'red',
  [LOAN_STATUS.LOST]: 'orange',
  [LOAN_STATUS.EXTENDED]: 'blue',
}

// ============================================
// RESERVATION STATUS
// ============================================
export const RESERVATION_STATUS = {
  PENDING: 'pending',
  READY: 'ready',
  CANCELLED: 'cancelled',
  EXPIRED: 'expired',
  FULFILLED: 'fulfilled',
}

export const RESERVATION_STATUS_LABELS = {
  [RESERVATION_STATUS.PENDING]: 'Oczekująca',
  [RESERVATION_STATUS.READY]: 'Gotowa do odbioru',
  [RESERVATION_STATUS.CANCELLED]: 'Anulowana',
  [RESERVATION_STATUS.EXPIRED]: 'Wygasła',
  [RESERVATION_STATUS.FULFILLED]: 'Zrealizowana',
}

// ============================================
// BOOK STATUS
// ============================================
export const BOOK_STATUS = {
  AVAILABLE: 'available',
  BORROWED: 'borrowed',
  RESERVED: 'reserved',
  MAINTENANCE: 'maintenance',
  LOST: 'lost',
  ARCHIVED: 'archived',
}

export const BOOK_STATUS_LABELS = {
  [BOOK_STATUS.AVAILABLE]: 'Dostępna',
  [BOOK_STATUS.BORROWED]: 'Wypożyczona',
  [BOOK_STATUS.RESERVED]: 'Zarezerwowana',
  [BOOK_STATUS.MAINTENANCE]: 'W konserwacji',
  [BOOK_STATUS.LOST]: 'Zgubiona',
  [BOOK_STATUS.ARCHIVED]: 'Zarchiwizowana',
}

// ============================================
// NOTIFICATION TYPES
// ============================================
export const NOTIFICATION_TYPE = {
  INFO: 'info',
  SUCCESS: 'success',
  WARNING: 'warning',
  ERROR: 'error',
  LOAN_DUE: 'loan_due',
  RESERVATION_READY: 'reservation_ready',
  SYSTEM: 'system',
}

// ============================================
// PAGINATION
// ============================================
export const PAGINATION = {
  DEFAULT_PAGE_SIZE: 10,
  PAGE_SIZE_OPTIONS: [5, 10, 20, 50, 100],
  MAX_PAGE_SIZE: 100,
}

// ============================================
// API ENDPOINTS
// ============================================
export const API_ENDPOINTS = {
  // Auth
  LOGIN: '/api/auth/login',
  REGISTER: '/api/auth/register',
  LOGOUT: '/api/auth/logout',
  REFRESH: '/api/auth/refresh',
  ME: '/api/auth/me',
  
  // Books
  BOOKS: '/api/books',
  BOOK: (id) => `/api/books/${id}`,
  
  // Loans
  LOANS: '/api/loans',
  LOAN: (id) => `/api/loans/${id}`,
  MY_LOANS: '/api/my-loans',
  
  // Reservations
  RESERVATIONS: '/api/reservations',
  RESERVATION: (id) => `/api/reservations/${id}`,
  
  // Users
  USERS: '/api/users',
  USER: (id) => `/api/users/${id}`,
  
  // Admin
  ADMIN_DASHBOARD: '/api/admin/dashboard',
  ADMIN_USERS: '/api/admin/users',
  ADMIN_LOGS: '/api/admin/logs',
}

// ============================================
// ROUTES
// ============================================
export const ROUTES = {
  // Public
  HOME: '/',
  LOGIN: '/login',
  REGISTER: '/register',
  
  // Books
  BOOKS: '/books',
  BOOK_DETAILS: (id) => `/books/${id}`,
  
  // User
  DASHBOARD: '/dashboard',
  PROFILE: '/profile',
  MY_LOANS: '/my-loans',
  RESERVATIONS: '/reservations',
  FAVORITES: '/favorites',
  NOTIFICATIONS: '/notifications',
  
  // Admin
  ADMIN: '/admin',
  ADMIN_USERS: '/admin/users',
  ADMIN_BOOKS: '/admin/books',
  ADMIN_LOANS: '/admin/loans',
  ADMIN_SETTINGS: '/admin/settings',
  ADMIN_LOGS: '/admin/logs',
  
  // Librarian
  LIBRARIAN: '/librarian',
  REPORTS: '/reports',
}

// ============================================
// DATE FORMATS
// ============================================
export const DATE_FORMATS = {
  DISPLAY: 'dd.MM.yyyy',
  DISPLAY_TIME: 'dd.MM.yyyy HH:mm',
  API: 'yyyy-MM-dd',
  API_TIME: "yyyy-MM-dd'T'HH:mm:ss",
}

// ============================================
// VALIDATION
// ============================================
export const VALIDATION = {
  PASSWORD_MIN_LENGTH: 8,
  PASSWORD_MAX_LENGTH: 128,
  NAME_MIN_LENGTH: 2,
  NAME_MAX_LENGTH: 100,
  ISBN_LENGTH: 13,
  MAX_FILE_SIZE: 5 * 1024 * 1024, // 5MB
  ALLOWED_IMAGE_TYPES: ['image/jpeg', 'image/png', 'image/webp'],
}
