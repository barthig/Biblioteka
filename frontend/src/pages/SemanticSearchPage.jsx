import React from 'react'
import SemanticSearch from '../components/SemanticSearch'

export default function SemanticSearchPage() {
  return (
    <div className="page">
      <header className="page-header">
        <div>
          <h1>Wyszukiwanie semantyczne</h1>
          <p className="support-copy">Szukaj ksiazek po znaczeniu opisu.</p>
        </div>
      </header>
      <div className="surface-card">
        <SemanticSearch />
      </div>
    </div>
  )
}
