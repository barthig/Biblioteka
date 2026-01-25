import React, { useCallback, useState } from 'react'
import { NavLink } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext'
import { apiFetch } from '../../api'
import { useResourceCache } from '../../context/ResourceCacheContext'

const navLinkClass = ({ isActive }) => isActive ? 'top-nav__link is-active' : 'top-nav__link'

export default function Navbar() {
  const [menuOpen, setMenuOpen] = useState(false)
  const { token, user, logout } = useAuth()
  const { prefetchResource } = useResourceCache()
  // Check for valid authentication - user must be present (token validated)
  const isAuthenticated = Boolean(user)
  const roles = user?.roles || []
  const isAdmin = roles.includes('ROLE_ADMIN')
  const isLibrarian = roles.includes('ROLE_LIBRARIAN')

  const toggleMenu = () => setMenuOpen(prev => !prev)
  const closeMenu = useCallback(() => setMenuOpen(false), [])

  const prefetchLoans = useCallback(() => {
    if (!token) return
    prefetchResource('loans:/api/loans', () => apiFetch('/api/loans')).catch(() => {})
  }, [prefetchResource, token])

  const prefetchReservations = useCallback(() => {
    if (!token) return
    prefetchResource('reservations:/api/reservations?history=true', () => apiFetch('/api/reservations?history=true')).catch(() => {})
  }, [prefetchResource, token])

  const prefetchFavorites = useCallback(() => {
    if (!token) return
    prefetchResource('favorites:/api/favorites', () => apiFetch('/api/favorites')).catch(() => {})
  }, [prefetchResource, token])

  const prefetchRecommended = useCallback(() => {
    prefetchResource('recommended:/api/books/recommended', () => apiFetch('/api/books/recommended')).catch(() => {})
  }, [prefetchResource])

  return (
    <header className="top-nav">
      <div className="top-nav__inner">
        <NavLink to="/" className="top-nav__brand" onClick={closeMenu}>
          <span className="top-nav__logo" aria-hidden>
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
              <path d="M5 4.5h10.5a2.5 2.5 0 0 1 2.5 2.5v11a2 2 0 0 0-2-2H5z" />
              <path d="M5 4.5v11.5a2 2 0 0 1 2-2h10.5" />
              <path d="M9.5 7.5h5" />
              <path d="M9.5 10h5" />
            </svg>
          </span>
          <div>
            <strong>Biblioteka</strong>
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
            <NavLink to="/recommended" className={navLinkClass} onMouseEnter={prefetchRecommended} onFocus={prefetchRecommended} onClick={closeMenu}>
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
                  <NavLink to="/admin" className={navLinkClass} onClick={closeMenu}>
                    Panel administratora
                  </NavLink>
                  <NavLink to="/my-loans" className={navLinkClass} onMouseEnter={prefetchLoans} onFocus={prefetchLoans} onClick={closeMenu}>
                    Wypożyczenia
                  </NavLink>
                  <NavLink to="/reservations" className={navLinkClass} onMouseEnter={prefetchReservations} onFocus={prefetchReservations} onClick={closeMenu}>
                    Rezerwacje
                  </NavLink>
                  <NavLink to="/favorites" className={navLinkClass} onMouseEnter={prefetchFavorites} onFocus={prefetchFavorites} onClick={closeMenu}>
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
                  <NavLink to="/librarian" className={navLinkClass} onClick={closeMenu}>
                    Panel bibliotekarza
                  </NavLink>
                  <NavLink to="/reports" className={navLinkClass} onClick={closeMenu}>
                    Raporty
                  </NavLink>
                </>
              ) : (
                <>
                  <NavLink to="/my-loans" className={navLinkClass} onMouseEnter={prefetchLoans} onFocus={prefetchLoans} onClick={closeMenu}>
                    Wypożyczenia
                  </NavLink>
                  <NavLink to="/reservations" className={navLinkClass} onMouseEnter={prefetchReservations} onFocus={prefetchReservations} onClick={closeMenu}>
                    Rezerwacje
                  </NavLink>
                  <NavLink to="/favorites" className={navLinkClass} onMouseEnter={prefetchFavorites} onFocus={prefetchFavorites} onClick={closeMenu}>
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
                <span>{user?.name || 'Użytkownik'}</span>
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






