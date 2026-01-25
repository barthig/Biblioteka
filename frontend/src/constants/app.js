// App constants
export const API_BASE_URL = process.env.VITE_API_URL || 'http://localhost:8000'
export const ROLES = {
  ADMIN: 'ROLE_ADMIN',
  LIBRARIAN: 'ROLE_LIBRARIAN',
  USER: 'ROLE_USER'
}
export const ROUTES = {
  HOME: '/',
  LOGIN: '/login',
  REGISTER: '/register',
  DASHBOARD: '/dashboard',
  BOOKS: '/books',
  ADMIN: '/admin'
}
