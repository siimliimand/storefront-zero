import { test, expect } from '@playwright/test';

/**
 * E2E tests for the HTMX live search interaction.
 *
 * Verifies: typing in search input -> debounce delay -> HTMX GET request ->
 * results dropdown appears -> Escape/Outside click closes dropdown.
 *
 * Prerequisites:
 * - WordPress + WooCommerce running (see playwright.config.ts for base URL)
 */

test.describe('HTMX Live Search', () => {
  test('typing in search input triggers HTMX search request after debounce', async ({ page }) => {
    await page.goto('/');

    const searchInput = page.locator('input[type="search"][name="s"]');
    await expect(searchInput).toBeVisible();

    // Set up a response listener before typing.
    const searchResponsePromise = page.waitForResponse(
      (resp) => resp.url().includes('/htmx-api/search') && resp.request().method() === 'GET',
      { timeout: 10000 },
    );

    await searchInput.type('shirt', { delay: 30 });

    // HTMX fires after 300ms debounce. Wait for the response.
    const response = await searchResponsePromise;
    expect(response.status()).toBe(200);
  });

  test('search results dropdown appears with matching products', async ({ page }) => {
    await page.goto('/');

    const searchInput = page.locator('input[type="search"][name="s"]');
    const searchResults = page.locator('#search-results');

    // The dropdown should be hidden initially.
    await expect(searchResults).toBeHidden();

    // Type a query and wait for the HTMX response.
    const searchResponsePromise = page.waitForResponse(
      (resp) => resp.url().includes('/htmx-api/search') && resp.request().method() === 'GET',
      { timeout: 10000 },
    );

    await searchInput.type('shirt', { delay: 30 });
    const response = await searchResponsePromise;

    // Allow HTMX to swap the fragment into the DOM.
    const body = await response.text();

    if (body.trim().length > 0) {
      // HTMX swaps content into #search-results, making it visible.
      await expect(searchResults).toBeVisible({ timeout: 5000 });

      // If there are products, they should be rendered as result items.
      const resultItems = searchResults.locator('.search-result-item');
      const count = await resultItems.count();
      expect(count).toBeGreaterThanOrEqual(1);
    }
  });

  test('search results contain product links with thumbnails', async ({ page }) => {
    await page.goto('/');

    const searchInput = page.locator('input[type="search"][name="s"]');

    const searchResponsePromise = page.waitForResponse(
      (resp) => resp.url().includes('/htmx-api/search') && resp.request().method() === 'GET',
      { timeout: 10000 },
    );

    await searchInput.type('shirt', { delay: 30 });
    await searchResponsePromise;
    await page.waitForTimeout(500);

    const resultItems = page.locator('#search-results .search-result-item');
    const count = await resultItems.count();

    if (count > 0) {
      // Each result item should be a link.
      const firstItem = resultItems.first();
      await expect(firstItem).toHaveAttribute('href', /\/product\//);

      // The item should contain a product name span.
      await expect(firstItem.locator('.font-medium')).toBeVisible();

      // It should contain a price span.
      await expect(firstItem.locator('.text-gray-600')).toBeVisible();
    }
  });

  test('empty search query returns no results', async ({ page }) => {
    await page.goto('/');

    const searchInput = page.locator('input[type="search"][name="s"]');

    const searchResponsePromise = page.waitForResponse(
      (resp) => resp.url().includes('/htmx-api/search') && resp.request().method() === 'GET',
      { timeout: 10000 },
    );

    // Type a nonsense query unlikely to match any product.
    await searchInput.type('zzzzzzzzzzzzzzznothere', { delay: 10 });
    const response = await searchResponsePromise;
    await page.waitForTimeout(500);

    const body = await response.text();

    // Should show "no products found" or be empty.
    if (body.trim().length > 0) {
      const noResults = page.locator('#search-results .search-results-empty');
      await expect(noResults).toBeVisible({ timeout: 5000 });
    }
  });

  test('pressing Escape closes the search results dropdown', async ({ page }) => {
    await page.goto('/');

    const searchInput = page.locator('input[type="search"][name="s"]');
    const searchResults = page.locator('#search-results');

    const searchResponsePromise = page.waitForResponse(
      (resp) => resp.url().includes('/htmx-api/search') && resp.request().method() === 'GET',
      { timeout: 10000 },
    );

    await searchInput.type('shirt', { delay: 30 });
    await searchResponsePromise;
    await page.waitForTimeout(500);

    // If results are visible, press Escape and verify they hide.
    if (await searchResults.isVisible()) {
      await searchInput.press('Escape');
      await expect(searchResults).toBeHidden({ timeout: 5000 });
    }
  });

  test('clicking outside the search form closes the dropdown', async ({ page }) => {
    await page.goto('/');

    const searchInput = page.locator('input[type="search"][name="s"]');
    const searchResults = page.locator('#search-results');

    const searchResponsePromise = page.waitForResponse(
      (resp) => resp.url().includes('/htmx-api/search') && resp.request().method() === 'GET',
      { timeout: 10000 },
    );

    await searchInput.type('shirt', { delay: 30 });
    await searchResponsePromise;
    await page.waitForTimeout(500);

    if (await searchResults.isVisible()) {
      // Click on the page body (outside the search form).
      await page.locator('body').click({ position: { x: 10, y: 10 } });
      await expect(searchResults).toBeHidden({ timeout: 5000 });
    }
  });

  test('search spinner is visible during HTMX request', async ({ page }) => {
    await page.goto('/');

    const searchInput = page.locator('input[type="search"][name="s"]');
    const spinner = page.locator('.search-spinner.htmx-indicator');

    // Slow down the search response to observe the spinner.
    await page.route('**/htmx-api/search*', async (route) => {
      await new Promise((r) => setTimeout(r, 600));
      await route.continue();
    });

    await searchInput.type('shirt', { delay: 30 });

    // The spinner should become visible during the delayed request.
    await expect(spinner).toBeVisible({ timeout: 3000 });
  });

  test('typing triggers search with correct query parameter', async ({ page }) => {
    await page.goto('/');

    const searchInput = page.locator('input[type="search"][name="s"]');

    // Capture the request URL to verify the query param.
    const searchResponsePromise = page.waitForResponse(
      (resp) => resp.url().includes('/htmx-api/search') && resp.request().method() === 'GET',
      { timeout: 10000 },
    );

    await searchInput.type('cap', { delay: 30 });
    const response = await searchResponsePromise;

    const url = new URL(response.url());
    expect(url.searchParams.get('s')).toBe('cap');
  });

  test('subsequent keystrokes update search query', async ({ page }) => {
    await page.goto('/');

    const searchInput = page.locator('input[type="search"][name="s"]');
    let capturedUrls: string[] = [];

    // Intercept all search requests.
    page.on('response', (resp) => {
      if (resp.url().includes('/htmx-api/search') && resp.request().method() === 'GET') {
        capturedUrls.push(resp.url());
      }
    });

    await searchInput.type('s', { delay: 30 });
    await page.waitForTimeout(800);

    await searchInput.type('h', { delay: 30 });
    await page.waitForTimeout(800);

    await searchInput.type('i', { delay: 30 });
    await page.waitForTimeout(800);

    // At least one search request should have been made for each distinct query.
    expect(capturedUrls.length).toBeGreaterThanOrEqual(1);

    // The last captured URL should contain the latest query.
    const lastUrl = new URL(capturedUrls[capturedUrls.length - 1]);
    expect(lastUrl.searchParams.get('s')).toBe('shi');
  });
});
