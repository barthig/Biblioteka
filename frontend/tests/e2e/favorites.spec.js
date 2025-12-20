const { test, expect } = require('@playwright/test')

function makeToken(payload) {
  const header = Buffer.from(JSON.stringify({ alg: 'HS256', typ: 'JWT', kid: '1' })).toString('base64url')
  const body = Buffer.from(JSON.stringify(payload)).toString('base64url')
  return `${header}.${body}.signature`
}

test('favorites list and remove', async ({ page }) => {
  const token = makeToken({ sub: 1, email: 'user@example.com', name: 'User', roles: ['ROLE_USER'], exp: Math.floor(Date.now() / 1000) + 3600 })
  await page.addInitScript((value) => localStorage.setItem('token', value), token)

  await page.route('**/api/favorites', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ data: [{ id: 1, createdAt: '2025-01-01T00:00:00Z', book: { id: 10, title: 'Alpha', author: { name: 'Author A' } } }] })
    })
  })
  await page.route('**/api/favorites/10', async (route) => {
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true }) })
  })

  await page.goto('/favorites')
  await expect(page.getByText('Alpha')).toBeVisible()

  await page.click('button:has-text("Usu")')
  await expect(page.getByText('Alpha')).toHaveCount(0)
})
