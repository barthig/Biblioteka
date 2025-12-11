// @ts-check
const { defineConfig } = require('@playwright/test')

const baseURL = process.env.BASE_URL || 'http://localhost:5173'

module.exports = defineConfig({
  testDir: './tests/e2e',
  fullyParallel: true,
  retries: 0,
  timeout: 30_000,
  use: {
    baseURL,
    trace: 'retain-on-failure',
    headless: true,
    viewport: { width: 1280, height: 720 },
  },
  reporter: [['list']],
})
