const { test, expect } = require('@playwright/test')

test('books list and details', async ({ page }) => {
  await page.route('**/api/books/filters', async (route) => {
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ authors: [], categories: [], publishers: [], resourceTypes: [], years: {}, ageGroups: [] }) })
  })
  await page.route('**/api/books?*', async (route) => {
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ data: [{ id: 1, title: 'Alpha', author: { name: 'Author A' }, copies: 1, totalCopies: 1 }] }) })
  })
  await page.route('**/api/books', async (route) => {
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ data: [{ id: 1, title: 'Alpha', author: { name: 'Author A' }, copies: 1, totalCopies: 1 }] }) })
  })
  await page.route('**/api/books/1', async (route) => {
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ id: 1, title: 'Alpha', author: { name: 'Author A' }, categories: [{ name: 'Fiction' }], copies: 1, totalCopies: 1 }) })
  })
  await page.route('**/api/books/1/reviews', async (route) => {
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ summary: { average: 4.5, total: 2 }, reviews: [], userReview: null }) })
  })

  await page.goto('/books')
  await expect(page.getByText('Alpha')).toBeVisible()

  await page.click('text=Alpha')
  await expect(page.getByRole('heading', { name: 'Alpha' })).toBeVisible()
})
