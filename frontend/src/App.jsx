import React from 'react'
import { Routes, Route } from 'react-router-dom'
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

export default function App() {
  return (
    <AuthProvider>
      <ResourceCacheProvider>
        <div className="app-shell">
          <Navbar />
          <main className="main">
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
            <Route path="/login" element={<Login />} />
            <Route path="/register" element={<Register />} />
            <Route path="/profile" element={<Profile />} />
            <Route
              path="/admin"
              element={
                <RequireRole allowed={['ROLE_ADMIN']}>
                  <AdminPanel />
                </RequireRole>
              }
            />
            <Route
              path="/librarian"
              element={
                <RequireRole allowed={['ROLE_LIBRARIAN', 'ROLE_ADMIN']}>
                  <LibrarianPanel />
                </RequireRole>
              }
            />
            </Routes>
            <footer className="footer">
              <p>© 2025 Biblioteka. Wspieramy czytelników w odkrywaniu literatury i edukacji cyfrowej.</p>
              <div className="footer__links">
                <a href="#">Regulamin korzystania</a>
                <a href="#">Polityka prywatności</a>
                <a href="#">Kontakt</a>
              </div>
            </footer>
          </main>
        </div>
      </ResourceCacheProvider>
    </AuthProvider>
  )
}
