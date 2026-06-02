import { test, expect } from '@playwright/test'

function makeToken(payload) {
  const header = Buffer.from(JSON.stringify({ alg: 'HS256', typ: 'JWT', kid: '1' })).toString('base64url')
  const body = Buffer.from(JSON.stringify(payload)).toString('base64url')
  return `${header}.${body}.signature`
}

test('profile fees tab shows active library fee', async ({ page }) => {
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

  await page.route('**/api/me', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        id: 1,
        name: 'Reader',
        email: 'reader@example.com',
        cardNumber: '123456',
      })
    })
  })

  await page.route('**/api/me/fees', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        data: [
          {
            id: 7,
            reason: 'Przetrzymanie książki',
            amount: '12.50',
            currency: 'PLN',
            createdAt: '2025-01-10T00:00:00Z',
            paidAt: null,
          }
        ]
      })
    })
  })

  await page.goto('/profile')
  await page.getByRole('button', { name: 'Opłaty i płatności' }).click()
  await page.getByRole('button', { name: /Rozwiń opłaty i płatności/ }).click()

  await expect(page.getByText('Przetrzymanie książki')).toBeVisible()
  await expect(page.getByText('12.50 PLN')).toBeVisible()
  await expect(page.getByRole('button', { name: 'Ureguluj online' })).toBeVisible()
})
