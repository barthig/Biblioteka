/**
 * FeatureGuard - Protects routes based on feature flags
 */
import { Navigate } from 'react-router-dom'

// Feature flags configuration (can be from API or env)
const FEATURE_FLAGS = {
  SEMANTIC_SEARCH: import.meta.env.VITE_FEATURE_SEMANTIC_SEARCH !== 'false',
  RECOMMENDATIONS: import.meta.env.VITE_FEATURE_RECOMMENDATIONS !== 'false',
  DIGITAL_ASSETS: import.meta.env.VITE_FEATURE_DIGITAL_ASSETS !== 'false',
  ACQUISITIONS: import.meta.env.VITE_FEATURE_ACQUISITIONS !== 'false',
  BETA_FEATURES: import.meta.env.VITE_FEATURE_BETA === 'true',
}

export function FeatureGuard({ 
  children, 
  feature, 
  fallbackPath = '/dashboard',
  showNotAvailable = false 
}) {
  const isEnabled = FEATURE_FLAGS[feature] ?? false

  if (!isEnabled) {
    if (showNotAvailable) {
      return (
        <div className="flex flex-col items-center justify-center min-h-screen">
          <h1 className="text-4xl font-bold text-gray-400 mb-4">🚧</h1>
          <p className="text-xl text-gray-600">Feature Not Available</p>
          <p className="text-gray-500 mt-2">This feature is currently disabled.</p>
        </div>
      )
    }
    return <Navigate to={fallbackPath} replace />
  }

  return children
}

// Hook to check feature flags
export function useFeatureFlag(feature) {
  return FEATURE_FLAGS[feature] ?? false
}

export default FeatureGuard
