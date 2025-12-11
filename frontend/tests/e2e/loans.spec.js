const { test, expect } = require('@playwright/test')

const API_PREFIX = '**/api'

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

test.describe('Moje wypożyczenia', () => {
  test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => {
      window.localStorage.setItem('token', 'test.jwt.token')
    })
    stubLoans(page)
  })

  test('pokazuje aktywne wypożyczenie i pozwala przedłużyć', async ({ page }) => {
    await page.goto('/my-loans')

    await expect(page.getByRole('heading', { name: 'Moje wypożyczenia' })).toBeVisible()
    await expect(page.getByText('Pan Tadeusz')).toBeVisible()
    await expect(page.getByText('Termin zwrotu')).toBeVisible()

    await page.getByRole('button', { name: 'Przedłuż' }).click()
    await expect(page.getByText('Termin wypożyczenia został przedłużony')).toBeVisible()
    await expect(page.getByText('Przedłużenia: 1')).toBeVisible()
  })
})
