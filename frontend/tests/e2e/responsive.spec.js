const { test, expect } = require('@playwright/test')

const viewports = [
  { name: 'mobile', width: 360, height: 640 },
  { name: 'tablet', width: 768, height: 1024 },
  { name: 'desktop', width: 1280, height: 720 },
]

const publicRoutes = [
  '/',
  '/books',
  '/books/1',
  '/announcements',
  '/announcements/1',
  '/login',
  '/register',
]

const userRoutes = [
  '/',
  '/books',
  '/books/1',
  '/recommended',
  '/announcements',
  '/my-loans',
  '/reservations',
  '/favorites',
  '/notifications',
  '/profile',
]

const adminRoutes = [
  '/admin',
  '/librarian',
  '/reports',
  '/users/1/details',
]

const publicAssertions = {
  '/': async (page) => {
    await expect(page.locator('.landing-page.public-home')).toBeVisible()
  },
  '/books': async (page) => {
    await expect(page.locator('form.book-search')).toBeVisible()
  },
  '/books/1': async (page) => {
    await expect(page.locator('.book-actions')).toBeVisible()
    await expect(page.locator('.review-summary')).toBeVisible()
  },
  '/announcements': async (page) => {
    await expect(page.locator('section.surface-card .section-header h2')).toHaveCount(2)
  },
  '/announcements/1': async (page) => {
    await expect(page.locator('.page-header__actions button')).toBeVisible()
  },
  '/login': async (page) => {
    await expect(page.locator('#login-email')).toBeVisible()
    await expect(page.locator('#login-password')).toBeVisible()
  },
  '/register': async (page) => {
    await expect(page.locator('#register-name')).toBeVisible()
    await expect(page.locator('#register-email')).toBeVisible()
  },
}

const userAssertions = {
  '/': async (page) => {
    await expect(page.locator('.card-grid--columns-3')).toBeVisible()
  },
  '/books': publicAssertions['/books'],
  '/books/1': publicAssertions['/books/1'],
  '/recommended': async (page) => {
    await expect(page.locator('.recommended-groups')).toBeVisible()
  },
  '/announcements': publicAssertions['/announcements'],
  '/my-loans': async (page) => {
    await expect(page.locator('.loans-panel')).toBeVisible()
  },
  '/reservations': async (page) => {
    await expect(page.getByRole('button', { name: 'Anuluj' })).toBeVisible()
  },
  '/favorites': async (page) => {
    await expect(page.locator('.resource-list .resource-item')).toHaveCount(1)
  },
  '/notifications': async (page) => {
    await expect(page.locator('.list.list--bordered')).toBeVisible()
  },
  '/profile': async (page) => {
    await expect(page.locator('.tabs')).toBeVisible()
    await expect(page.locator('#password-current')).toBeVisible()
  },
}

const adminAssertions = {
  '/admin': async (page) => {
    await expect(page.locator('.admin-panel')).toBeVisible()
  },
  '/librarian': async (page) => {
    await expect(page.locator('.librarian-panel')).toBeVisible()
  },
  '/reports': async (page) => {
    await expect(page.locator('.grid.grid-2')).toBeVisible()
  },
  '/users/1/details': async (page) => {
    await expect(page.locator('.user-details-container')).toBeVisible()
  },
}

function makeToken(payload) {
  const header = Buffer.from(JSON.stringify({ alg: 'HS256', typ: 'JWT', kid: '1' })).toString('base64url')
  const body = Buffer.from(JSON.stringify(payload)).toString('base64url')
  return `${header}.${body}.signature`
}

function buildBook(id = 1) {
  return {
    id,
    title: 'Test Book',
    author: { name: 'Test Author' },
    categories: [{ name: 'Fiction' }],
    publisher: 'Test Publisher',
    publicationYear: 2020,
    resourceType: 'Book',
    signature: 'T-001',
    isbn: '9780000000000',
    description: 'Sample description.',
    copies: 2,
    totalCopies: 2,
    storageCopies: 1,
    openStackCopies: 1,
    targetAgeGroup: 'adult',
    targetAgeGroupLabel: 'Adult',
    averageRating: 4.5,
    ratingCount: 1,
    isFavorite: false,
  }
}

function buildAnnouncement(id = 1) {
  return {
    id,
    title: 'Announcement',
    content: 'Sample announcement content.',
    createdAt: '2025-01-01T10:00:00Z',
    eventAt: null,
    type: 'info',
  }
}

function mockApiRoutes(page) {
  page.route('**/api/**', route => {
    const request = route.request()
    const url = new URL(request.url())
    const path = url.pathname
    const search = url.search
    const method = request.method()
    const json = (data) => route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify(data),
    })

    if (method !== 'GET') {
      return json({ success: true, data: {} })
    }

    if (path === '/api/books/filters') {
      return json({
        authors: [{ id: 1, name: 'Test Author' }],
        categories: [{ id: 1, name: 'Fiction' }],
        publishers: ['Test Publisher'],
        resourceTypes: ['Book'],
        years: { min: 2000, max: 2024 },
        ageGroups: [{ value: 'adult', label: 'Adult' }],
      })
    }

    if (path === '/api/books') {
      return json({
        data: [buildBook(1)],
        meta: { page: 1, totalPages: 1, total: 1, limit: 20 },
      })
    }

    if (/^\/api\/books\/\d+\/reviews$/.test(path)) {
      return json({ summary: { average: 4.5, total: 1 }, reviews: [], userReview: null })
    }

    if (/^\/api\/books\/\d+\/ratings$/.test(path)) {
      return json({ average: 4.5, count: 1, userRating: null })
    }

    if (/^\/api\/books\/\d+$/.test(path)) {
      return json(buildBook(Number(path.split('/').pop())))
    }

    if (path === '/api/books/recommended') {
      return json({ groups: [{ key: 'staff', label: 'Staff picks', books: [buildBook(2)] }] })
    }

    if (path === '/api/announcements') {
      return json({ data: [buildAnnouncement(1)], meta: { totalPages: 1 } })
    }

    if (/^\/api\/announcements\/\d+$/.test(path)) {
      return json(buildAnnouncement(Number(path.split('/').pop())))
    }

    if (path === '/api/dashboard') {
      return json({
        booksCount: 120,
        usersCount: 45,
        loansCount: 12,
        reservationsQueue: 3,
        activeLoans: 4,
        activeReservations: 2,
        favoritesCount: 5,
        activeUsers: 3,
        serverLoad: 15,
        transactionsToday: 6,
        pendingReservations: 1,
        overdueLoans: 0,
        expiredReservations: 0,
      })
    }

    if (path === '/api/alerts') {
      return json([])
    }

    if (path === '/api/library/hours') {
      return json({ days: [] })
    }

    if (path === '/api/me') {
      return json({
        id: 1,
        name: 'Reader',
        email: 'reader@example.com',
        phoneNumber: '',
        addressLine: '',
        city: '',
        postalCode: '',
        pesel: '',
        cardNumber: '123456',
        cardExpiry: '2026-12-31',
        accountStatus: 'Aktywne',
        defaultBranch: 'main',
        newsletter: false,
        keepHistory: false,
        emailLoans: true,
        emailReservations: true,
        emailFines: true,
        emailAnnouncements: false,
        theme: 'auto',
        fontSize: 'standard',
        language: 'pl',
      })
    }

    if (path === '/api/me/fees') {
      return json({ data: [] })
    }

    if (path === '/api/users/me/ratings') {
      return json({ ratings: [] })
    }

    if (path === '/api/me/loans') {
      return json({
        data: [
          {
            id: 1,
            borrowedAt: '2025-01-01T00:00:00Z',
            dueAt: '2025-02-01T00:00:00Z',
            extensionsCount: 0,
            book: { id: 10, title: 'Pan Tadeusz' },
            bookCopy: { inventoryCode: 'INV-1' },
          }
        ]
      })
    }

    if (path === '/api/reservations' && search === '?history=true') {
      return json({
        data: [
          {
            id: 1,
            status: 'ACTIVE',
            reservedAt: '2025-01-01T10:00:00Z',
            expiresAt: '2025-02-01T10:00:00Z',
            book: { title: 'Reserved Book' }
          },
          {
            id: 2,
            status: 'FULFILLED',
            reservedAt: '2025-01-01T10:00:00Z',
            fulfilledAt: '2025-01-05T10:00:00Z',
            book: { title: 'Fulfilled Book' }
          }
        ]
      })
    }

    if (path === '/api/reservations') {
      return json({ data: [] })
    }

    if (path === '/api/favorites') {
      return json({
        data: [
          {
            id: 1,
            createdAt: '2025-01-01T00:00:00Z',
            book: { id: 11, title: 'Favorite Book', author: { name: 'Author' } }
          }
        ]
      })
    }

    if (path === '/api/notifications') {
      return json({
        data: [
          {
            id: 1,
            title: 'Reminder',
            message: 'Test notification',
            type: 'info',
            createdAt: '2025-01-01T12:00:00Z'
          }
        ]
      })
    }

    if (path === '/api/statistics/dashboard') {
      return json({
        activeLoans: 2,
        overdueLoans: 0,
        pendingReservations: 1,
        totalUsers: 20,
        totalBooks: 200,
        availableCopies: 15,
        popularBooks: [],
        recentActivity: [],
      })
    }

    if (path === '/api/users') {
      return json([])
    }

    if (path === '/api/admin/system/settings') {
      return json({ settings: [] })
    }

    if (path === '/api/admin/system/integrations') {
      return json({ integrations: [] })
    }

    if (path === '/api/admin/system/roles') {
      return json({ roles: [] })
    }

    if (path.startsWith('/api/audit-logs')) {
      return json({ data: [] })
    }

    if (path === '/api/loans') {
      return json({ data: [] })
    }

    if (path === '/api/reports/usage') {
      return json({ loans: 0, overdueLoans: 0, activeUsers: 0, availableCopies: 0 })
    }

    if (path === '/api/reports/circulation/popular') {
      return json({ data: [] })
    }

    if (path === '/api/reports/patrons/segments') {
      return json({ data: [] })
    }

    if (path === '/api/reports/financial') {
      return json({ totalRevenue: 0, totalExpenses: 0, balance: 0 })
    }

    if (path === '/api/reports/inventory') {
      return json({ storageCopies: 0, openStackCopies: 0, removedCopies: 0 })
    }

    if (path === '/api/settings') {
      return json({ loanLimitPerUser: 5, loanDurationDays: 21, notificationsEnabled: true })
    }

    if (path === '/api/collections') {
      return json({ collections: [] })
    }

    if (path === '/api/fines') {
      return json({ data: [] })
    }

    if (/^\/api\/users\/\d+\/details$/.test(path)) {
      return json({
        user: {
          id: 1,
          name: 'Test User',
          email: 'user@example.com',
          phoneNumber: '',
          addressLine: '',
          city: '',
          postalCode: '',
          pesel: '',
          cardNumber: '123456',
          roles: ['ROLE_USER'],
          blocked: false,
        },
        activeLoans: [],
        loanHistory: [],
        activeFines: [],
        paidFines: [],
        statistics: {
          totalLoans: 0,
          activeLoansCount: 0,
          activeFinesCount: 0,
          totalFineAmount: 0,
        },
      })
    }

    return json({ data: [] })
  })
}

async function visitRoutes(page, routes, assertions) {
  for (const viewport of viewports) {
    await page.setViewportSize({ width: viewport.width, height: viewport.height })
    for (const route of routes) {
      await page.goto(route)
      await expect(page.locator('main').first()).toBeVisible()
      if (assertions && assertions[route]) {
        await assertions[route](page)
      }
    }
  }
}

test.describe('responsive coverage', () => {
  test('public views render on all breakpoints', async ({ page }) => {
    mockApiRoutes(page)
    await visitRoutes(page, publicRoutes, publicAssertions)
  })

  test('user views render on all breakpoints', async ({ page }) => {
    const token = makeToken({
      sub: 1,
      email: 'reader@example.com',
      name: 'Reader',
      roles: ['ROLE_USER'],
      exp: Math.floor(Date.now() / 1000) + 3600,
    })
    await page.addInitScript(value => {
      window.localStorage.setItem('token', value)
    }, token)
    mockApiRoutes(page)
    await visitRoutes(page, userRoutes, userAssertions)
  })

  test('admin views render on all breakpoints', async ({ page }) => {
    const token = makeToken({
      sub: 1,
      email: 'admin@example.com',
      name: 'Admin',
      roles: ['ROLE_ADMIN', 'ROLE_LIBRARIAN'],
      exp: Math.floor(Date.now() / 1000) + 3600,
    })
    await page.addInitScript(value => {
      window.localStorage.setItem('token', value)
    }, token)
    mockApiRoutes(page)
    await visitRoutes(page, adminRoutes, adminAssertions)
  })
})
