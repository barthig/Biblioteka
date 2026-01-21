import React from 'react'
import { Routes, Route } from 'react-router-dom'
import { Toaster } from 'react-hot-toast'
import { AuthProvider } from './context/AuthContext'
import { ResourceCacheProvider } from './context/ResourceCacheContext'
import Navbar from './components/Navbar'
import RequireRole from './components/RequireRole'
import Login from './pages/Login'
import Books from './pages/Books'
import Dashboard from './pages/Dashboard'
import BookDetails from './pages/BookDetails'
import MyLoans from './pages/MyLoans'
import Register from './pages/Register'
import Profile from './pages/Profile'
import Reservations from './pages/Reservations'
import Favorites from './pages/Favorites'
import Recommended from './pages/Recommended'
import AdminPanel from './pages/AdminPanel'
import LibrarianPanel from './pages/LibrarianPanel'
import Announcements from './pages/Announcements'
import UserDetails from './pages/UserDetails'
import Notifications from './pages/Notifications'
import Reports from './pages/Reports'

export default function App() {
  return (
    <AuthProvider>
      <ResourceCacheProvider>
        <Toaster 
          position="top-right"
          toastOptions={{
            duration: 4000,
            style: {
              background: '#363636',
              color: '#fff',
            },
            success: {
              duration: 3000,
              iconTheme: {
                primary: '#4caf50',
                secondary: '#fff',
              },
            },
            error: {
              duration: 5000,
              iconTheme: {
                primary: '#f44336',
                secondary: '#fff',
              },
            },
          }}
        />
        <div className="app-shell theme-root">
          <Navbar />
          <main className="main">
            <div className="content-shell">
              <Routes>
                <Route path="/" element={<Dashboard />} />
                <Route path="/books" element={<Books />} />
                <Route path="/books/:id" element={<BookDetails />} />
                <Route path="/recommended" element={<Recommended />} />
                <Route path="/announcements" element={<Announcements />} />
                <Route path="/announcements/:id" element={<Announcements />} />
                <Route path="/my-loans" element={<MyLoans />} />
                <Route path="/reservations" element={<Reservations />} />
                <Route path="/favorites" element={<Favorites />} />
                <Route path="/notifications" element={<Notifications />} />
                <Route path="/login" element={<Login />} />
                <Route path="/register" element={<Register />} />
                <Route path="/profile" element={<Profile />} />
                <Route
                  path="/admin/*"
                  element={(
                    <RequireRole allowed={['ROLE_ADMIN']}>
                      <AdminPanel />
                    </RequireRole>
                  )}
                />
                <Route
                  path="/users/:id/details"
                  element={(
                    <RequireRole allowed={['ROLE_LIBRARIAN', 'ROLE_ADMIN']}>
                      <UserDetails />
                    </RequireRole>
                  )}
                />
                <Route
                  path="/librarian"
                  element={(
                    <RequireRole allowed={['ROLE_LIBRARIAN', 'ROLE_ADMIN']}>
                      <LibrarianPanel />
                    </RequireRole>
                  )}
                />
                <Route
                  path="/reports"
                  element={(
                    <RequireRole allowed={['ROLE_LIBRARIAN', 'ROLE_ADMIN']}>
                      <Reports />
                    </RequireRole>
                  )}
                />
              </Routes>
              <footer className="footer">
                <p>(c) 2025 Biblioteka. System zarządzania biblioteką i wypożyczeniami.</p>
                <div className="footer__links">
                  <a href="#regulamin">Regulamin</a>
                  <a href="#prywatnosc">Polityka prywatności</a>
                  <a href="#kontakt">Kontakt</a>
                </div>
              </footer>
            </div>
          </main>
        </div>
      </ResourceCacheProvider>
    </AuthProvider>
  )
}

