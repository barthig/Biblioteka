// Utils - barrel exports
export * from './rbac'
export { default as logger, LogLevel } from './logger'
export { default as uiPreferences } from './uiPreferences'

// Common utility functions
export function formatDate(date, options = {}) {
  if (!date) return '-'
  const d = new Date(date)
  
  const defaultOptions = {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    ...options
  }
  
  return d.toLocaleDateString('pl-PL', defaultOptions)
}

export function formatDateTime(date) {
  return formatDate(date, {
    hour: '2-digit',
    minute: '2-digit'
  })
}

export function formatCurrency(amount, currency = 'PLN') {
  if (amount == null) return '-'
  return new Intl.NumberFormat('pl-PL', {
    style: 'currency',
    currency
  }).format(amount)
}

export function truncateText(text, maxLength = 100) {
  if (!text || text.length <= maxLength) return text
  return text.substring(0, maxLength).trim() + '...'
}

export function debounce(fn, delay = 300) {
  let timeoutId
  return (...args) => {
    clearTimeout(timeoutId)
    timeoutId = setTimeout(() => fn(...args), delay)
  }
}

export function throttle(fn, limit = 100) {
  let inThrottle
  return (...args) => {
    if (!inThrottle) {
      fn(...args)
      inThrottle = true
      setTimeout(() => inThrottle = false, limit)
    }
  }
}

export function classNames(...classes) {
  return classes.filter(Boolean).join(' ')
}

export function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms))
}

export function generateId() {
  return `id_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
}

export function isEmpty(value) {
  if (value == null) return true
  if (Array.isArray(value)) return value.length === 0
  if (typeof value === 'object') return Object.keys(value).length === 0
  if (typeof value === 'string') return value.trim().length === 0
  return false
}

export function copyToClipboard(text) {
  if (navigator.clipboard) {
    return navigator.clipboard.writeText(text)
  }
  
  // Fallback for older browsers
  const textarea = document.createElement('textarea')
  textarea.value = text
  document.body.appendChild(textarea)
  textarea.select()
  document.execCommand('copy')
  document.body.removeChild(textarea)
  return Promise.resolve()
}

export function downloadFile(data, filename, type = 'application/json') {
  const blob = new Blob([data], { type })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
}
