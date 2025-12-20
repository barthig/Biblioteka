const { test, expect } = require('@playwright/test')

test('admin route redirects to login when unauthenticated', async ({ page }) => {
  await page.goto('/admin')
  await expect(page).toHaveURL(/\/login/)
})
