import React from 'react'

export default class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props)
    this.state = { hasError: false, error: null }
  }

  static getDerivedStateFromError(error) {
    return { hasError: true, error }
  }

  componentDidCatch(error, errorInfo) {
    // In production, send to error tracking service (e.g. Sentry)
    if (import.meta.env.DEV) {
      // eslint-disable-next-line no-console
      console.error('[ErrorBoundary]', error, errorInfo)
    }
  }

  handleReset = () => {
    this.setState({ hasError: false, error: null })
  }

  render() {
    if (this.state.hasError) {
      if (this.props.fallback) {
        return this.props.fallback
      }

      return (
        <div className="page page--centered" style={{ padding: '2rem', textAlign: 'center' }}>
          <div className="surface-card form-card">
            <h2>Coś poszło nie tak</h2>
            <p style={{ color: 'var(--text-secondary)', margin: '1rem 0' }}>
              Wystąpił nieoczekiwany błąd. Spróbuj odświeżyć stronę.
            </p>
            {import.meta.env.DEV && this.state.error && (
              <pre style={{ textAlign: 'left', fontSize: '0.8rem', overflow: 'auto', maxHeight: '200px', marginBottom: '1rem' }}>
                {this.state.error.toString()}
              </pre>
            )}
            <div style={{ display: 'flex', gap: '1rem', justifyContent: 'center' }}>
              <button className="btn btn--primary" onClick={this.handleReset}>
                Spróbuj ponownie
              </button>
              <button className="btn btn--secondary" onClick={() => window.location.href = '/'}>
                Strona główna
              </button>
            </div>
          </div>
        </div>
      )
    }

    return this.props.children
  }
}
