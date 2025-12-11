const { test, expect } = require('@playwright/test')

const API_PREFIX = '**/api'

function stubAuthRoutes(page) {
  page.route(`${API_PREFIX}/auth/login`, route => {
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ token: 'test.jwt.token' }),
    })
  })

  page.route(`${API_PREFIX}/dashboard`, route => {
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        booksCount: 123,
        usersCount: 45,
        loansCount: 7,
        reservationsQueue: 2,
      }),
    })
  })

  const emptyList = JSON.stringify({ data: [], meta: { total: 0 } })
  page.route(`${API_PREFIX}/reservations?history=true`, route => route.fulfill({ status: 200, contentType: 'application/json', body: emptyList }))
  page.route(`${API_PREFIX}/favorites`, route => route.fulfill({ status: 200, contentType: 'application/json', body: emptyList }))
  page.route(`${API_PREFIX}/loans`, route => route.fulfill({ status: 200, contentType: 'application/json', body: emptyList }))
}

test('user can log in and see dashboard stats', async ({ page }) => {
  stubAuthRoutes(page)

  await page.goto('/login')
  await page.getByLabel('Email').fill('reader@example.com')
  await page.getByLabel('Hasło').fill('secret123')
  await page.getByRole('button', { name: 'Zaloguj' }).click()

  await expect(page).toHaveURL(/\/$/)
  await expect(page.getByRole('heading', { name: 'Panel biblioteki' })).toBeVisible()
  await expect(page.getByText('Książki w katalogu')).toBeVisible()
  await expect(page.getByText('123')).toBeVisible()
  await expect(page.getByText('Aktywni czytelnicy')).toBeVisible()
  await expect(page.getByText('45')).toBeVisible()
})
