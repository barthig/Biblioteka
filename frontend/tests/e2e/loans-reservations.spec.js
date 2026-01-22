const { test, expect } = require('@playwright/test')

function makeToken(payload) {
  const header = Buffer.from(JSON.stringify({ alg: 'HS256', typ: 'JWT', kid: '1' })).toString('base64url')
  const body = Buffer.from(JSON.stringify(payload)).toString('base64url')
  return `${header}.${body}.signature`
}

test('loans and reservations pages', async ({ page }) => {
  const token = makeToken({ sub: 1, email: 'user@example.com', name: 'User', roles: ['ROLE_USER'], exp: Math.floor(Date.now() / 1000) + 3600 })
  await page.addInitScript((value) => localStorage.setItem('token', value), token)

  await page.route('**/api/me/loans', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        data: [
          { id: 1, book: { title: 'Loaned Book' }, dueAt: '2025-02-01', returnedAt: null, extensionsCount: 0 },
          { id: 2, book: { title: 'Returned Book' }, borrowedAt: '2025-01-01', returnedAt: '2025-01-10' }
        ]
      })
    })
  })
  await page.route('**/api/loans/1/extend', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ id: 1, book: { title: 'Loaned Book' }, dueAt: '2025-02-15', returnedAt: null, extensionsCount: 1 })
    })
  })
  await page.route('**/api/reservations?history=true', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        data: [
          { id: 1, status: 'ACTIVE', reservedAt: '2025-01-01T10:00:00Z', expiresAt: '2025-02-01T10:00:00Z', book: { title: 'Reserved Book' } },
          { id: 2, status: 'FULFILLED', reservedAt: '2025-01-01T10:00:00Z', fulfilledAt: '2025-01-05T10:00:00Z', book: { title: 'Fulfilled Book' } }
        ]
      })
    })
  })
  await page.route('**/api/reservations/1', async (route) => {
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true }) })
  })

  await page.goto('/my-loans')
  await expect(page.getByText('Loaned Book')).toBeVisible()
  await expect(page.getByText('Returned Book')).toBeVisible()

  await page.goto('/reservations')
  await expect(page.getByText('Reserved Book')).toBeVisible()
  await page.click('button:has-text("Anul")')
})
