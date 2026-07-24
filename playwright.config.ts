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
    baseURL: process.env.CI_BASE_URL || 'http://localhost:8080',
    headless: true,
    screenshot: 'only-on-failure',
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],

  // In CI, WordPress is started by scripts/setup-wp.sh before tests run.
  // Locally, start WordPress yourself (e.g., `ddev start`) before running tests.
  // Playwright will verify the server is reachable via baseURL before executing tests.
});
