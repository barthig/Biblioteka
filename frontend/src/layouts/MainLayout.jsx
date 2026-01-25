/**
 * MainLayout - Default layout with navbar and footer
 */
import { Outlet } from 'react-router-dom'
import { Navbar } from '../components/common'

export function MainLayout() {
  return (
    <div className="app-shell theme-root">
      <Navbar />
      <main className="main">
        <div className="content-shell">
          <Outlet />
          <footer className="footer">
            <p>© 2025 Biblioteka. System zarządzania biblioteką i wypożyczeniami.</p>
            <div className="footer__links">
              <a href="#regulamin">Regulamin</a>
              <a href="#prywatnosc">Polityka prywatności</a>
              <a href="#kontakt">Kontakt</a>
            </div>
          </footer>
        </div>
      </main>
    </div>
  )
}

export default MainLayout
