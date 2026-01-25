/**
 * AuthLayout - Layout for authentication pages (login, register)
 */
import { Outlet } from 'react-router-dom'

export function AuthLayout() {
  return (
    <div className="auth-layout">
      <div className="auth-layout__container">
        <div className="auth-layout__brand">
          <h1 className="auth-layout__title">📚 Biblioteka</h1>
          <p className="auth-layout__subtitle">System zarządzania biblioteką</p>
        </div>
        <div className="auth-layout__content">
          <Outlet />
        </div>
        <div className="auth-layout__footer">
          <p>© 2025 Biblioteka. Wszelkie prawa zastrzeżone.</p>
        </div>
      </div>
    </div>
  )
}

export default AuthLayout
