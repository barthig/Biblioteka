const { test, expect } = require('@playwright/test')

test('announcements list and details', async ({ page }) => {
  await page.route('**/api/announcements?page=*', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        data: [
          { id: 1, title: 'Alpha', createdAt: '2025-01-01T00:00:00Z', content: 'First announcement' }
        ],
        meta: { totalPages: 1 }
      })
    })
  })
  await page.route('**/api/announcements/1', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ id: 1, title: 'Alpha', createdAt: '2025-01-01T00:00:00Z', content: 'First announcement' })
    })
  })

  await page.goto('/announcements')
  await expect(page.getByText('Alpha')).toBeVisible()
  await page.click('text=Alpha')
  await expect(page.getByRole('heading', { name: 'Alpha' })).toBeVisible()
})
