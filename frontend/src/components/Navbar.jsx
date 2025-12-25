import React, { useCallback, useState } from 'react'
import { NavLink } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { apiFetch } from '../api'
import { useResourceCache } from '../context/ResourceCacheContext'

const navLinkClass = ({ isActive }) => isActive ? 'top-nav__link is-active' : 'top-nav__link'

export default function Navbar() {
  const [menuOpen, setMenuOpen] = useState(false)
  const { token, user, logout } = useAuth()
  const { prefetchResource } = useResourceCache()
  const roles = user?.roles || []
  const isAdmin = roles.includes('ROLE_ADMIN')
  const isLibrarian = roles.includes('ROLE_LIBRARIAN') || isAdmin

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
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
              <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
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

          {token && (
            <div className="top-nav__secondary">
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
              {isLibrarian && (
                <>
                  <NavLink to="/librarian" className={navLinkClass} onClick={closeMenu}>
                    Panel bibliotekarza
                  </NavLink>
                  <NavLink to="/reports" className={navLinkClass} onClick={closeMenu}>
                    Raporty
                  </NavLink>
                  <NavLink to="/admin/assets" className={navLinkClass} onClick={closeMenu}>
                    Pliki książek
                  </NavLink>
                </>
              )}
              {isAdmin && (
                <>
                  <NavLink to="/admin/catalog" className={navLinkClass} onClick={closeMenu}>
                    Katalog import/eksport
                  </NavLink>
                  <NavLink to="/admin/acquisitions" className={navLinkClass} onClick={closeMenu}>
                    Akcesje
                  </NavLink>
                  <NavLink to="/admin/logs" className={navLinkClass} onClick={closeMenu}>
                    Logi
                  </NavLink>
                  <NavLink to="/admin" className={navLinkClass} onClick={closeMenu}>
                    Panel administratora
                  </NavLink>
                </>
              )}
            </div>
          )}
        </nav>

        <div className="top-nav__actions">
          {token ? (
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
