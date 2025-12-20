const { test, expect } = require('@playwright/test')

const API_PREFIX = '**/api'

function makeToken(payload) {
  const header = Buffer.from(JSON.stringify({ alg: 'HS256', typ: 'JWT', kid: '1' })).toString('base64url')
  const body = Buffer.from(JSON.stringify(payload)).toString('base64url')
  return `${header}.${body}.signature`
}

function stubAuthRoutes(page) {
  const token = makeToken({
    sub: 1,
    email: 'reader@example.com',
    name: 'Reader',
    roles: ['ROLE_USER'],
    exp: Math.floor(Date.now() / 1000) + 3600,
  })

  page.route(`${API_PREFIX}/auth/login`, route => {
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ token }),
    })
  })

  page.route(`${API_PREFIX}/dashboard`, route => {
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        activeLoans: 3,
        activeReservations: 2,
        favoritesCount: 5,
      }),
    })
  })

  page.route(`${API_PREFIX}/alerts`, route => {
    route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify([]) })
  })

  page.route(`${API_PREFIX}/library/hours`, route => {
    route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(null) })
  })

  const emptyList = JSON.stringify({ data: [], meta: { total: 0 } })
  page.route(`${API_PREFIX}/reservations?history=true`, route => route.fulfill({ status: 200, contentType: 'application/json', body: emptyList }))
  page.route(`${API_PREFIX}/favorites`, route => route.fulfill({ status: 200, contentType: 'application/json', body: emptyList }))
  page.route(`${API_PREFIX}/loans`, route => route.fulfill({ status: 200, contentType: 'application/json', body: emptyList }))
}

test('user can log in and see dashboard stats', async ({ page }) => {
  stubAuthRoutes(page)

  await page.goto('/login')
  await page.fill('#login-email', 'reader@example.com')
  await page.fill('#login-password', 'secret123')
  await page.click('button[type="submit"]')

  await expect(page).toHaveURL(/\/$/)
  await expect(page.getByRole('heading', { name: /Witaj/i })).toBeVisible()
  const stats = page.locator('.stat-card strong')
  await expect(stats).toHaveText(['3', '2', '5'])
})
