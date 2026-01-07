const STORAGE_KEY = 'ui:preferences'

export function normalizeUiPreferences(raw = {}) {
  return {
    theme: raw.theme ?? 'auto',
    fontSize: raw.fontSize ?? 'standard',
    language: raw.language ?? 'pl',
  }
}

export function applyUiPreferences(prefs) {
  if (typeof document === 'undefined') return
  const normalized = normalizeUiPreferences(prefs)
  const root = document.documentElement
  if (!root) return

  if (normalized.theme && normalized.theme !== 'auto') {
    root.dataset.theme = normalized.theme
  } else {
    delete root.dataset.theme
  }

  if (normalized.fontSize && normalized.fontSize !== 'standard') {
    root.dataset.fontSize = normalized.fontSize
  } else {
    delete root.dataset.fontSize
  }

  if (normalized.language) {
    root.lang = normalized.language
  }
}

export function loadStoredUiPreferences() {
  if (typeof localStorage === 'undefined') return null
  try {
    const stored = localStorage.getItem(STORAGE_KEY)
    if (!stored) return null
    return normalizeUiPreferences(JSON.parse(stored))
  } catch {
    return null
  }
}

export function storeUiPreferences(prefs) {
  if (typeof localStorage === 'undefined') return
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(normalizeUiPreferences(prefs)))
  } catch {
    // ignore storage failures
  }
}

export function clearUiPreferences() {
  if (typeof localStorage === 'undefined') return
  try {
    localStorage.removeItem(STORAGE_KEY)
  } catch {
    // ignore storage failures
  }
}
