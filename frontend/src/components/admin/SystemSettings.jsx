import React from 'react'

export default function SystemSettings({
  settings,
  integrations,
  loading,
  systemLoaded,
  loadSystem,
  updateSetting,
  integrationForm,
  setIntegrationForm,
  createIntegration,
  toggleIntegration,
  testIntegration
}) {
  return (
    <div className="grid two-columns">
      <section className="surface-card" role="region" aria-labelledby="settings-title">
        <div className="section-header">
          <h2 id="settings-title">Ustawienia systemowe</h2>
          {!loading && systemLoaded && (
            <button 
              className="btn btn-secondary" 
              onClick={loadSystem}
              aria-label="Odśwież ustawienia systemowe"
            >
              Odśwież
            </button>
          )}
        </div>
        
        {loading && <div aria-live="polite" role="status">Ładowanie...</div>}
        
        {!loading && (
          <ul className="list" role="list">
            {settings.length === 0 && <li>Brak ustawień do wyświetlenia.</li>}
            {settings.map(item => (
              <li key={item.key}>
                <div className="list-row">
                  <div>
                    <strong>{item.key}</strong>
                    {item.description && (
                      <div className="support-copy" id={`desc-${item.key}`}>
                        {item.description}
                      </div>
                    )}
                    <div className="support-copy" aria-label={`Aktualna wartość: ${item.value ?? ''}`}>
                      {String(item.value ?? '')}
                    </div>
                  </div>
                  <button
                    className="btn btn-sm"
                    onClick={() => updateSetting(item.key, prompt('Nowa wartość', item.value ?? '') || item.value)}
                    aria-label={`Edytuj ustawienie ${item.key}`}
                    aria-describedby={item.description ? `desc-${item.key}` : undefined}
                  >
                    Edytuj
                  </button>
                </div>
              </li>
            ))}
          </ul>
        )}
      </section>

      <section className="surface-card" role="region" aria-labelledby="integrations-title">
        <div className="section-header">
          <h2 id="integrations-title">Integracje</h2>
          {!loading && (
            <button 
              className="btn btn-secondary" 
              onClick={loadSystem}
              aria-label="Przeładuj integracje"
            >
              Przeładuj
            </button>
          )}
        </div>
        
        {loading && <div aria-live="polite" role="status">Ładowanie...</div>}
        
        {!loading && (
          <ul className="list" role="list">
            {integrations.length === 0 && <li>Brak zdefiniowanych integracji.</li>}
            {integrations.map(item => (
              <li key={item.id}>
                <div className="list-row">
                  <div>
                    <strong>{item.name || 'Integracja'}</strong>
                    <div className="support-copy">
                      {item.provider || 'typ nieznany'} - {item.settings?.endpoint || 'brak adresu'}
                    </div>
                    {item.lastStatus && (
                      <div className="support-copy" aria-live="polite">
                        Status: {item.lastStatus}
                      </div>
                    )}
                  </div>
                  <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }} role="group">
                    <button 
                      className="btn btn-sm btn-secondary" 
                      type="button" 
                      onClick={() => testIntegration(item.id)}
                      aria-label={`Testuj integrację ${item.name}`}
                    >
                      Testuj
                    </button>
                    <label className="switch" aria-label={`Włącz/wyłącz integrację ${item.name}`}>
                      <input
                        type="checkbox"
                        checked={!!item.enabled}
                        onChange={e => toggleIntegration(item.id, e.target.checked)}
                        aria-label={item.enabled ? 'Wyłącz integrację' : 'Włącz integrację'}
                      />
                      <span className="switch-slider" />
                    </label>
                  </div>
                </div>
              </li>
            ))}
          </ul>
        )}

        <form className="form" onSubmit={createIntegration} aria-labelledby="add-integration-title">
          <h3 id="add-integration-title">Dodaj integrację</h3>
          <div className="form-field">
            <label htmlFor="integration-name">Nazwa</label>
            <input 
              id="integration-name"
              value={integrationForm.name} 
              onChange={e => setIntegrationForm({ ...integrationForm, name: e.target.value })} 
              required 
              aria-required="true"
            />
          </div>
          <div className="form-field">
            <label htmlFor="integration-provider">Dostawca</label>
            <input 
              id="integration-provider"
              value={integrationForm.provider} 
              onChange={e => setIntegrationForm({ ...integrationForm, provider: e.target.value })} 
              required 
              aria-required="true"
            />
          </div>
          <div className="form-field">
            <label htmlFor="integration-endpoint">Endpoint</label>
            <input 
              id="integration-endpoint"
              value={integrationForm.endpoint} 
              onChange={e => setIntegrationForm({ ...integrationForm, endpoint: e.target.value })} 
              required 
              aria-required="true"
            />
          </div>
          <div className="form-field">
            <label htmlFor="integration-apikey">API key (opcjonalnie)</label>
            <input 
              id="integration-apikey"
              type="password"
              value={integrationForm.apiKey} 
              onChange={e => setIntegrationForm({ ...integrationForm, apiKey: e.target.value })} 
            />
          </div>
          <div className="form-field checkbox">
            <label>
              <input
                type="checkbox"
                checked={integrationForm.enabled}
                onChange={e => setIntegrationForm({ ...integrationForm, enabled: e.target.checked })}
              />
              Aktywna
            </label>
          </div>
          <button type="submit" className="btn btn-primary">
            Zapisz integrację
          </button>
        </form>
      </section>
    </div>
  )
}
