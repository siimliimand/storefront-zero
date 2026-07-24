import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright configuration for Storefront Zero E2E tests.
 *
 * Tests verify HTMX interactions (add-to-cart, search) and theme functionality.
 *
 * Prerequisites: WordPress must be running locally (typically via DDEV on port 8080).
 * Start with: `ddev start`
 */
export default defineConfig({
  testDir: './tests/e2e',
  timeout: 30000,
  retries: 1,

  use: {
    baseURL: 'http://localhost:8080',
    headless: true,
    screenshot: 'only-on-failure',
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],

  webServer: {
    command: 'echo "WordPress must be running at http://localhost:8080 (ddev start)"',
    url: 'http://localhost:8080',
    reuseExistingServer: true,
  },
});
