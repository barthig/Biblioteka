import { Link } from 'react-router-dom'

export default function NotFound() {
  return (
    <div className="page page--centered" style={{ padding: '2rem', textAlign: 'center' }}>
      <div className="surface-card form-card">
        <h1 style={{ fontSize: '3rem', marginBottom: '0.5rem' }}>404</h1>
        <h2>Strona nie znaleziona</h2>
        <p style={{ color: 'var(--text-secondary)', margin: '1rem 0' }}>
          Strona, której szukasz, nie istnieje lub została przeniesiona.
        </p>
        <Link to="/" className="btn btn--primary">
          Wróć na stronę główną
        </Link>
      </div>
    </div>
  )
}
