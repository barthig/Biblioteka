import { test, expect } from '@playwright/test'

function makeToken(payload) {
  const header = Buffer.from(JSON.stringify({ alg: 'HS256', typ: 'JWT', kid: '1' })).toString('base64url')
  const body = Buffer.from(JSON.stringify(payload)).toString('base64url')
  return `${header}.${body}.signature`
}

test('staff route redirects to login when unauthenticated', async ({ page }) => {
  await page.goto('/staff?section=admin')
  await expect(page).toHaveURL(/\/login/)
})

test('staff route denies access for reader role', async ({ page }) => {
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

  await page.goto('/staff?section=admin')
  await expect(page.getByRole('heading', { name: 'Brak dostępu' })).toBeVisible()
  await expect(page.getByText(/ROLE_LIBRARIAN, ROLE_ADMIN/)).toBeVisible()
})
