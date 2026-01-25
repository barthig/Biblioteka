/**
 * AdminLayout - Layout for admin pages with sidebar
 */
import { Outlet, NavLink } from 'react-router-dom'
import { Navbar } from '../components/common'

const adminLinks = [
  { to: '/admin', label: 'Dashboard', icon: '📊' },
  { to: '/admin/users', label: 'Użytkownicy', icon: '👥' },
  { to: '/admin/books', label: 'Książki', icon: '📚' },
  { to: '/admin/loans', label: 'Wypożyczenia', icon: '📋' },
  { to: '/admin/settings', label: 'Ustawienia', icon: '⚙️' },
  { to: '/admin/logs', label: 'Logi', icon: '📝' },
]

export function AdminLayout() {
  return (
    <div className="app-shell theme-root">
      <Navbar />
      <div className="admin-layout">
        <aside className="admin-layout__sidebar">
          <nav className="admin-nav">
            <h3 className="admin-nav__title">Panel Admina</h3>
            <ul className="admin-nav__list">
              {adminLinks.map((link) => (
                <li key={link.to} className="admin-nav__item">
                  <NavLink
                    to={link.to}
                    end={link.to === '/admin'}
                    className={({ isActive }) =>
                      `admin-nav__link ${isActive ? 'admin-nav__link--active' : ''}`
                    }
                  >
                    <span className="admin-nav__icon">{link.icon}</span>
                    <span className="admin-nav__label">{link.label}</span>
                  </NavLink>
                </li>
              ))}
            </ul>
          </nav>
        </aside>
        <main className="admin-layout__main">
          <div className="admin-layout__content">
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  )
}

export default AdminLayout
