const { test, expect } = require('@playwright/test')

const API_PREFIX = '**/api'

function makeToken(payload) {
  const header = Buffer.from(JSON.stringify({ alg: 'HS256', typ: 'JWT', kid: '1' })).toString('base64url')
  const body = Buffer.from(JSON.stringify(payload)).toString('base64url')
  return `${header}.${body}.signature`
}

const initialLoan = {
  id: 1,
  borrowedAt: '2025-01-01T00:00:00Z',
  dueAt: '2025-01-21T00:00:00Z',
  extensionsCount: 0,
  book: { id: 10, title: 'Pan Tadeusz' },
  bookCopy: { inventoryCode: 'INV-1' },
}

const extendedLoan = {
  ...initialLoan,
  dueAt: '2025-02-04T00:00:00Z',
  extensionsCount: 1,
  lastExtendedAt: '2025-01-15T00:00:00Z',
}

function stubLoans(page) {
  page.route(`${API_PREFIX}/loans`, route => {
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ data: [initialLoan], meta: { total: 1 } }),
    })
  })

  page.route(`${API_PREFIX}/loans/${initialLoan.id}/extend`, route => {
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ data: extendedLoan }),
    })
  })
}

test.describe('Moje wypozyczenia', () => {
  test.beforeEach(async ({ page }) => {
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
    stubLoans(page)
  })

  test('pokazuje aktywne wypozyczenie i pozwala przedluzyc', async ({ page }) => {
    await page.goto('/my-loans')

    await expect(page.getByRole('heading', { name: /Moje/i })).toBeVisible()
    await expect(page.getByText('Pan Tadeusz')).toBeVisible()
    await expect(page.getByText('Termin zwrotu')).toBeVisible()

    await page.getByRole('button', { name: /Przed/ }).click()
    await expect(page.locator('.success')).toContainText(/Termin/)
    await expect(page.locator('.resource-item__meta').first().getByText(/: 1/)).toBeVisible()
  })
})
