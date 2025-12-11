const { test, expect } = require('@playwright/test')

const API_PREFIX = '**/api'

const sampleBooks = [
  {
    id: 101,
    title: 'Solaris',
    author: { name: 'Stanisław Lem' },
    publicationYear: 1961,
    copies: 2,
    totalCopies: 3,
    openStackCopies: 1,
    storageCopies: 1,
    resourceType: 'KSIĄŻKA',
    targetAgeGroupLabel: 'Dorośli',
    description: 'Klasyka polskiej fantastyki.',
  },
  {
    id: 102,
    title: 'Wiedźmin',
    author: { name: 'Andrzej Sapkowski' },
    publicationYear: 1990,
    copies: 0,
    totalCopies: 2,
    openStackCopies: 0,
    storageCopies: 0,
    resourceType: 'KSIĄŻKA',
    targetAgeGroupLabel: 'Dorośli',
  },
]

const sampleFacets = {
  authors: [{ id: 1, name: 'Stanisław Lem' }, { id: 2, name: 'Andrzej Sapkowski' }],
  categories: [{ id: 1, name: 'Fantastyka' }],
  publishers: ['SuperNowa'],
  resourceTypes: ['KSIĄŻKA'],
  years: { min: 1950, max: 2025 },
  ageGroups: [{ value: 'ADULT', label: 'Dorośli' }],
}

function stubBooks(page) {
  page.route(`${API_PREFIX}/books`, route => {
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ data: sampleBooks, meta: { total: sampleBooks.length } }),
    })
  })

  page.route(`${API_PREFIX}/books/filters`, route => {
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify(sampleFacets),
    })
  })
}

test.describe('Katalog książek', () => {
  test.beforeEach(async ({ page }) => {
    stubBooks(page)
  })

  test('pokazuje listę książek i stan dostępności', async ({ page }) => {
    await page.goto('/books')

    await expect(page.getByRole('heading', { name: 'Książki' })).toBeVisible()
    await expect(page.getByText('Solaris')).toBeVisible()
    await expect(page.getByText('Stanisław Lem')).toBeVisible()
    await expect(page.getByText('Dostępne 2/3')).toBeVisible()

    await expect(page.getByText('Wiedźmin')).toBeVisible()
    await expect(page.getByText('Brak wolnych egzemplarzy')).toBeVisible()
  })
})
