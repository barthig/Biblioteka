import React, { useState } from 'react'
import { apiFetch } from '../api'

const AVAILABLE_CATEGORIES = [
  'Kryminał', 'Fantastyka', 'Romans', 'Thriller', 'Science Fiction',
  'Horror', 'Fantasy', 'Biografia', 'Historia', 'Popularnonaukowa',
  'Poradniki', 'Literatura piękna', 'Poezja', 'Dramat', 'Literatura młodzieżowa',
  'Dla dzieci', 'Komiks', 'Reportaż', 'Esej', 'Filozofia'
]

export default function OnboardingModal({ onComplete }) {
  const [selectedCategories, setSelectedCategories] = useState([])
  const [saving, setSaving] = useState(false)

  function toggleCategory(category) {
    setSelectedCategories(prev =>
      prev.includes(category)
        ? prev.filter(c => c !== category)
        : [...prev, category]
    )
  }

  async function handleComplete() {
    setSaving(true)
    try {
      await apiFetch('/api/users/me/onboarding', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ preferredCategories: selectedCategories })
      })
      onComplete?.()
    } catch (err) {
      console.error('Onboarding failed:', err)
      // Nawet jeśli fail, pozwól przejść dalej
      onComplete?.()
    } finally {
      setSaving(false)
    }
  }

  function handleSkip() {
    onComplete?.()
  }

  return (
    <div className="onboarding-overlay">
      <div className="onboarding-modal">
        <h2>📚 Witaj w bibliotece!</h2>
        <p className="onboarding-modal__intro">
          Wybierz kategorie książek, które Cię interesują. Pomoże nam to polecać Ci najlepsze pozycje.
          Możesz wybrać dowolną liczbę kategorii lub pominąć ten krok.
        </p>

        <div className="onboarding-categories">
          {AVAILABLE_CATEGORIES.map(category => (
            <button
              key={category}
              className={`category-option ${selectedCategories.includes(category) ? 'category-option--selected' : ''}`}
              onClick={() => toggleCategory(category)}
              type="button"
            >
              {category}
            </button>
          ))}
        </div>

        <div className="onboarding-actions">
          <button
            onClick={handleSkip}
            className="btn btn-secondary"
            disabled={saving}
          >
            Pomiń
          </button>
          <button
            onClick={handleComplete}
            className="btn btn-primary"
            disabled={saving}
          >
            {saving ? 'Zapisywanie...' : `Kontynuuj${selectedCategories.length > 0 ? ` (${selectedCategories.length})` : ''}`}
          </button>
        </div>
      </div>
    </div>
  )
}
