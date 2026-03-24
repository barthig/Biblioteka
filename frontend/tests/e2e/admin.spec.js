import { test, expect } from '@playwright/test'

test('staff route redirects to login when unauthenticated', async ({ page }) => {
  await page.goto('/staff?section=admin')
  await expect(page).toHaveURL(/\/login/)
})
