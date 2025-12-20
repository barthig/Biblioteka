const { test, expect } = require('@playwright/test')

function makeToken(payload) {
  const header = Buffer.from(JSON.stringify({ alg: 'HS256', typ: 'JWT', kid: '1' })).toString('base64url')
  const body = Buffer.from(JSON.stringify(payload)).toString('base64url')
  return `${header}.${body}.signature`
}

test('register flow stores token', async ({ page }) => {
  const token = makeToken({ sub: 1, email: 'user@example.com', name: 'User', roles: ['ROLE_USER'], exp: Math.floor(Date.now() / 1000) + 3600 })

  await page.route('**/api/auth/register', async (route) => {
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ verificationToken: 'token-1' }) })
  })
  await page.route('**/api/auth/verify/token-1', async (route) => {
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ pendingApproval: false }) })
  })
  await page.route('**/api/auth/login', async (route) => {
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ token }) })
  })

  await page.goto('/register')
  await page.fill('#register-name', 'Test User')
  await page.fill('#register-email', 'user@example.com')
  await page.fill('#register-password', 'password123')
  await page.fill('#register-confirm', 'password123')
  await page.click('button[type="submit"]')

  await page.waitForFunction(() => localStorage.getItem('token'))
  const stored = await page.evaluate(() => localStorage.getItem('token'))
  expect(stored).toBeTruthy()
})

test('login navigates to home', async ({ page }) => {
  const token = makeToken({ sub: 2, email: 'admin@example.com', name: 'Admin', roles: ['ROLE_ADMIN'], exp: Math.floor(Date.now() / 1000) + 3600 })

  await page.route('**/api/auth/login', async (route) => {
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ token }) })
  })

  await page.goto('/login')
  await page.fill('#login-email', 'admin@example.com')
  await page.fill('#login-password', 'password123')
  await page.click('button[type="submit"]')

  await expect(page).toHaveURL(/\/$/)
})
