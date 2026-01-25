/**
 * Application configuration constants
 */

// API Configuration
export const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000'
export const API_TIMEOUT = 30000
export const API_RETRY_ATTEMPTS = 3

// App metadata
export const APP_NAME = 'Biblioteka'
export const APP_VERSION = '1.0.0'
export const APP_DESCRIPTION = 'System zarządzania biblioteką'

// Local storage keys
export const STORAGE_KEYS = {
  TOKEN: 'token',
  REFRESH_TOKEN: 'refreshToken',
  USER: 'user',
  THEME: 'theme',
  LANGUAGE: 'language',
  ONBOARDING_COMPLETED: 'onboarding_completed',
}

// Feature flags
export const FEATURES = {
  SEMANTIC_SEARCH: import.meta.env.VITE_FEATURE_SEMANTIC_SEARCH !== 'false',
  RECOMMENDATIONS: import.meta.env.VITE_FEATURE_RECOMMENDATIONS !== 'false',
  DIGITAL_ASSETS: import.meta.env.VITE_FEATURE_DIGITAL_ASSETS !== 'false',
  BETA_FEATURES: import.meta.env.VITE_FEATURE_BETA === 'true',
}

// Re-export from enums for convenience
export { ROLES, ROUTES } from './enums'
