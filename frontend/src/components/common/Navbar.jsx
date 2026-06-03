import React, { useCallback, useState } from 'react'
import { NavLink } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext'

const navLinkClass = ({ isActive }) => isActive ? 'top-nav__link is-active' : 'top-nav__link'

export default function Navbar() {
  const [menuOpen, setMenuOpen] = useState(false)
  const { user, logout } = useAuth()
  // Check for valid authentication - user must be present (token validated)
  const isAuthenticated = Boolean(user)
  const roles = user?.roles || []
  const isAdmin = roles.includes('ROLE_ADMIN')
  const isLibrarian = roles.includes('ROLE_LIBRARIAN')

  const toggleMenu = () => setMenuOpen(prev => !prev)
  const closeMenu = useCallback(() => setMenuOpen(false), [])

  return (
    <header className="top-nav">
      <div className="top-nav__inner">
        <NavLink to="/" className="top-nav__brand" onClick={closeMenu}>
          <span className="top-nav__logo" aria-hidden>
            <svg width="40" height="40" viewBox="0 0 64 64" fill="none" role="img" aria-label="Smart Library">
              <defs>
                <linearGradient id="navbar-logo-bg" x1="8" y1="7" x2="56" y2="57" gradientUnits="userSpaceOnUse">
                  <stop offset="0" stopColor="#16d1c3" />
                  <stop offset="0.5" stopColor="#0b8eb8" />
                  <stop offset="1" stopColor="#293a8f" />
                </linearGradient>
                <filter id="navbar-logo-glow" x="-30%" y="-30%" width="160%" height="160%">
                  <feGaussianBlur stdDeviation="1.2" result="blur" />
                  <feColorMatrix
                    in="blur"
                    type="matrix"
                    values="0 0 0 0 0.7 0 0 0 0 0.95 0 0 0 0 1 0 0 0 0.45 0"
                    result="glow"
                  />
                  <feMerge>
                    <feMergeNode in="glow" />
                    <feMergeNode in="SourceGraphic" />
                  </feMerge>
                </filter>
              </defs>
              <rect width="64" height="64" rx="17" fill="#0b102d" />
              <rect x="3" y="3" width="58" height="58" rx="15" fill="url(#navbar-logo-bg)" />
              <path d="M16 18c5.5-2.2 11.1-1.5 16 2.2 4.9-3.7 10.5-4.4 16-2.2" stroke="#ffffff" strokeOpacity="0.18" strokeWidth="1.2" strokeLinecap="round" />
              <g filter="url(#navbar-logo-glow)" stroke="#f5fdff" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round">
                <path d="M18 22c4.6-2 9.3-1.2 14 2.2v22c-4.7-3.4-9.4-4.2-14-2.2V22Z" />
                <path d="M46 22c-4.6-2-9.3-1.2-14 2.2v22c4.7-3.4 9.4-4.2 14-2.2V22Z" />
                <path d="M32 24.2V46" />
                <path d="M22 29c2.2-.5 4.3-.2 6.2.8" />
                <path d="M22 34c2.2-.5 4.3-.2 6.2.8" />
                <path d="M36 29.8c1.9-1 4-1.3 6.2-.8" />
                <path d="M36 34.8c1.9-1 4-1.3 6.2-.8" />
              </g>
              <g stroke="#dffbff" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" opacity="0.9">
                <path d="M19 41h-3.5A4.5 4.5 0 0 1 11 36.5V34" />
                <path d="M45 41h3.5A4.5 4.5 0 0 0 53 36.5V34" />
                <path d="M26 50v3" />
                <path d="M38 50v3" />
              </g>
              <g fill="#f5fdff">
                <circle cx="11" cy="32" r="1.7" opacity="0.9" />
                <circle cx="53" cy="32" r="1.7" opacity="0.9" />
                <circle cx="26" cy="55" r="1.4" opacity="0.78" />
                <circle cx="38" cy="55" r="1.4" opacity="0.78" />
              </g>
            </svg>
          </span>
          <div>
            <strong>Smart Library</strong>
            <span>Panel czytelnika</span>
          </div>
        </NavLink>

        <button
          className="top-nav__toggle"
          onClick={toggleMenu}
          aria-expanded={menuOpen}
          aria-controls="top-nav-links"
        >
          <span className="top-nav__toggle-bars" aria-hidden>
            <span />
            <span />
            <span />
          </span>
          <span className="top-nav__toggle-text">Menu</span>
        </button>

        <nav id="top-nav-links" className="top-nav__links" data-open={menuOpen}>
          <div className="top-nav__primary">
            <NavLink to="/" end className={navLinkClass} onClick={closeMenu}>
              Start
            </NavLink>
            <NavLink to="/books" className={navLinkClass} onClick={closeMenu}>
              Katalog
            </NavLink>
            <NavLink to="/recommended" className={navLinkClass} onClick={closeMenu}>
              Polecane
            </NavLink>
            <NavLink to="/announcements" className={navLinkClass} onClick={closeMenu}>
              Ogłoszenia
            </NavLink>
          </div>

          {isAuthenticated && (
            <div className="top-nav__secondary">
              {isAdmin ? (
                <>
                  <NavLink to="/staff?section=admin" className={navLinkClass} onClick={closeMenu}>
                    Panel personelu
                  </NavLink>
                  <NavLink to="/acquisitions" className={navLinkClass} onClick={closeMenu}>
                    Akcesje
                  </NavLink>
                  <NavLink to="/reports" className={navLinkClass} onClick={closeMenu}>
                    Raporty
                  </NavLink>
                  <NavLink to="/my-loans" className={navLinkClass} onClick={closeMenu}>
                    Moje wypożyczenia
                  </NavLink>
                  <NavLink to="/reservations" className={navLinkClass} onClick={closeMenu}>
                    Rezerwacje
                  </NavLink>
                  <NavLink to="/favorites" className={navLinkClass} onClick={closeMenu}>
                    Ulubione
                  </NavLink>
                  <NavLink to="/notifications" className={navLinkClass} onClick={closeMenu}>
                    Powiadomienia
                  </NavLink>
                  <NavLink to="/profile" className={navLinkClass} onClick={closeMenu}>
                    Profil
                  </NavLink>
                </>
              ) : isLibrarian ? (
                <>
                  <NavLink to="/staff?section=operations" className={navLinkClass} onClick={closeMenu}>
                    Panel personelu
                  </NavLink>
                  <NavLink to="/acquisitions" className={navLinkClass} onClick={closeMenu}>
                    Akcesje
                  </NavLink>
                  <NavLink to="/reports" className={navLinkClass} onClick={closeMenu}>
                    Raporty
                  </NavLink>
                  <NavLink to="/my-loans" className={navLinkClass} onClick={closeMenu}>
                    Moje wypożyczenia
                  </NavLink>
                  <NavLink to="/reservations" className={navLinkClass} onClick={closeMenu}>
                    Rezerwacje
                  </NavLink>
                  <NavLink to="/favorites" className={navLinkClass} onClick={closeMenu}>
                    Ulubione
                  </NavLink>
                  <NavLink to="/notifications" className={navLinkClass} onClick={closeMenu}>
                    Powiadomienia
                  </NavLink>
                  <NavLink to="/profile" className={navLinkClass} onClick={closeMenu}>
                    Profil
                  </NavLink>
                </>
              ) : (
                <>
                  <NavLink to="/my-loans" className={navLinkClass} onClick={closeMenu}>
                    Moje wypożyczenia
                  </NavLink>
                  <NavLink to="/reservations" className={navLinkClass} onClick={closeMenu}>
                    Rezerwacje
                  </NavLink>
                  <NavLink to="/favorites" className={navLinkClass} onClick={closeMenu}>
                    Ulubione
                  </NavLink>
                  <NavLink to="/notifications" className={navLinkClass} onClick={closeMenu}>
                    Powiadomienia
                  </NavLink>
                  <NavLink to="/profile" className={navLinkClass} onClick={closeMenu}>
                    Profil
                  </NavLink>
                </>
              )}
            </div>
          )}
        </nav>

        <div className="top-nav__actions">
          {isAuthenticated ? (
            <>
              <div className="top-nav__user">
                <div className="avatar avatar--sm">
                  {user?.name?.charAt(0).toUpperCase() || 'U'}
                </div>
                <span>{user?.name || 'U|ytkownik'}</span>
              </div>
              <button className="btn btn-ghost" onClick={() => { logout(); closeMenu() }}>
                Wyloguj
              </button>
            </>
          ) : (
            <>
              <NavLink to="/login" className="btn btn-primary" onClick={closeMenu}>Zaloguj się</NavLink>
              <NavLink to="/register" className="btn btn-outline" onClick={closeMenu}>Zarejestruj</NavLink>
            </>
          )}
        </div>
      </div>
    </header>
  )
}





