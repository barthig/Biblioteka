import React, { Suspense } from 'react'
import { Routes, Route } from 'react-router-dom'
import { Toaster } from 'react-hot-toast'
import { AuthProvider } from './context/AuthContext'
import { ResourceCacheProvider } from './context/ResourceCacheContext'
import ErrorBoundary from './components/ErrorBoundary'
import { AuthGuard } from './guards'
import { Navbar, PwaStatusBanner, RequireRole } from './components/common'

const Login = React.lazy(() => import('./pages/auth/Login'))
const Register = React.lazy(() => import('./pages/auth/Register'))
const Books = React.lazy(() => import('./pages/books/Books'))
const BookDetails = React.lazy(() => import('./pages/books/BookDetails'))
const Announcements = React.lazy(() => import('./pages/books/Announcements'))
const Acquisitions = React.lazy(() => import('./pages/books/Acquisitions'))
const Dashboard = React.lazy(() => import('./pages/dashboard/Dashboard'))
const Recommended = React.lazy(() => import('./pages/dashboard/Recommended'))
const MyLoans = React.lazy(() => import('./pages/loans/MyLoans'))
const Reservations = React.lazy(() => import('./pages/loans/Reservations'))
const Profile = React.lazy(() => import('./pages/user/Profile'))
const Favorites = React.lazy(() => import('./pages/user/Favorites'))
const Notifications = React.lazy(() => import('./pages/user/Notifications'))
const UserDetails = React.lazy(() => import('./pages/user/UserDetails'))
const StaffPanel = React.lazy(() => import('./pages/admin/StaffPanel'))
const Reports = React.lazy(() => import('./pages/admin/Reports'))
const NotFound = React.lazy(() => import('./pages/NotFound'))

export default function App() {
  return (
    <ErrorBoundary>
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
          <div className="app-shell theme-root min-h-screen">
            <Navbar />
            <PwaStatusBanner />
            <main className="main flex-1 px-4 sm:px-5 lg:px-10">
              <div className="content-shell mx-auto w-full max-w-screen-2xl">
                <Suspense
                  fallback={(
                    <div className="page page--centered px-4 py-8 sm:px-6">
                      <p>Ladowanie...</p>
                    </div>
                  )}
                >
                  <Routes>
                    <Route path="/" element={<Dashboard />} />
                    <Route path="/books" element={<Books />} />
                    <Route path="/books/:id" element={<BookDetails />} />
                    <Route path="/recommended" element={<AuthGuard><Recommended /></AuthGuard>} />
                    <Route path="/announcements" element={<Announcements />} />
                    <Route path="/announcements/:id" element={<Announcements />} />
                    <Route
                      path="/acquisitions"
                      element={(
                        <RequireRole allowed={['ROLE_LIBRARIAN', 'ROLE_ADMIN']}>
                          <Acquisitions />
                        </RequireRole>
                      )}
                    />
                    <Route path="/my-loans" element={<AuthGuard><MyLoans /></AuthGuard>} />
                    <Route path="/reservations" element={<AuthGuard><Reservations /></AuthGuard>} />
                    <Route path="/favorites" element={<AuthGuard><Favorites /></AuthGuard>} />
                    <Route path="/notifications" element={<AuthGuard><Notifications /></AuthGuard>} />
                    <Route path="/login" element={<Login />} />
                    <Route path="/register" element={<Register />} />
                    <Route path="/profile" element={<AuthGuard><Profile /></AuthGuard>} />
                    <Route
                      path="/staff"
                      element={(
                        <RequireRole allowed={['ROLE_LIBRARIAN', 'ROLE_ADMIN']}>
                          <StaffPanel />
                        </RequireRole>
                      )}
                    />
                    <Route
                      path="/admin/*"
                      element={(
                        <RequireRole allowed={['ROLE_ADMIN']}>
                          <StaffPanel />
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
                          <StaffPanel />
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
                    <Route path="*" element={<NotFound />} />
                  </Routes>
                </Suspense>
                <footer className="footer px-2 py-6 sm:px-4">
                  <p>(c) 2025 Biblioteka. System zarzadzania biblioteka i wypozyczeniami.</p>
                  <div className="footer__links flex-wrap gap-3 sm:gap-6">
                    <a href="#regulamin">Regulamin</a>
                    <a href="#prywatnosc">Polityka prywatnosci</a>
                    <a href="#kontakt">Kontakt</a>
                  </div>
                </footer>
              </div>
            </main>
          </div>
        </ResourceCacheProvider>
      </AuthProvider>
    </ErrorBoundary>
  )
}
