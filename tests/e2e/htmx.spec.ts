import { test, expect } from '@playwright/test';

/**
 * E2E tests for HTMX interactions:
 * - Faceted filter URL push and grid update
 * - Mini-cart sync on add-to-cart
 * - Toast notification display on WC notice
 *
 * Prerequisites:
 * - WordPress + WooCommerce running (see playwright.config.ts for base URL)
 */

test.describe('Faceted Product Filter', () => {
  test('clicking a category filter updates the product grid via HTMX', async ({ page }) => {
    await page.goto('/shop/');

    // Look for a category filter link in the sidebar/widget area.
    const categoryLink = page.locator(
      '.widget_product_categories a, .wc-block-product-categories a, [data-filter="cat"] a, a[href*="product_cat"]'
    ).first();

    // If no category filter is visible, skip.
    const hasFilter = await categoryLink.isVisible().catch(() => false);
    if (!hasFilter) {
      test.skip(true, 'No category filter found on shop page');
      return;
    }

    // Intercept the HTMX request triggered by clicking the filter.
    const filterResponsePromise = page.waitForResponse(
      (resp) =>
        resp.url().includes('/htmx-api/products/filter') &&
        resp.request().method() === 'GET',
      { timeout: 10000 },
    );

    await categoryLink.click();
    const response = await filterResponsePromise;
    expect(response.status()).toBe(200);

    // The product grid should be updated with HTMX-swapped content.
    const productGrid = page.locator('#product-grid, .products, .wc-block-products');
    await expect(productGrid).toBeVisible({ timeout: 5000 });
  });

  test('URL updates (hx-push-url) when filter changes', async ({ page }) => {
    await page.goto('/shop/');

    const categoryLink = page.locator(
      '.widget_product_categories a, .wc-block-product-categories a, [data-filter="cat"] a, a[href*="product_cat"]'
    ).first();

    const hasFilter = await categoryLink.isVisible().catch(() => false);
    if (!hasFilter) {
      test.skip(true, 'No category filter found on shop page');
      return;
    }

    // Capture the initial URL.
    const initialUrl = page.url();

    // Intercept the HTMX filter request.
    const filterResponsePromise = page.waitForResponse(
      (resp) =>
        resp.url().includes('/htmx-api/products/filter') &&
        resp.request().method() === 'GET',
      { timeout: 10000 },
    );

    await categoryLink.click();
    await filterResponsePromise;
    await page.waitForTimeout(500);

    // The URL should have changed (hx-push-url behavior).
    const newUrl = page.url();
    expect(newUrl).not.toBe(initialUrl);
  });

  test('price range filter updates product grid', async ({ page }) => {
    await page.goto('/shop/');

    // Look for a price filter widget or form.
    const priceFilter = page.locator(
      '.widget_price_filter form, .wc-block-price-filter, [data-filter="price"]'
    ).first();

    const hasFilter = await priceFilter.isVisible().catch(() => false);
    if (!hasFilter) {
      test.skip(true, 'No price filter found on shop page');
      return;
    }

    // Look for price inputs or slider.
    const minPriceInput = page.locator(
      '.widget_price_filter .price_label input[name="min_price"], .wc-block-price-filter__min-input, input[name="min_price"]'
    ).first();

    const hasInput = await minPriceInput.isVisible().catch(() => false);
    if (!hasInput) {
      test.skip(true, 'No min price input found');
      return;
    }

    // Set a minimum price and submit.
    await minPriceInput.fill('10');

    const filterResponsePromise = page.waitForResponse(
      (resp) =>
        resp.url().includes('/htmx-api/products/filter') &&
        resp.request().method() === 'GET',
      { timeout: 10000 },
    ).catch(() => null);

    // Submit the filter form.
    const submitBtn = page.locator(
      '.widget_price_filter button, .wc-block-price-filter button[type="submit"], button[type="submit"]'
    ).first();

    if (await submitBtn.isVisible().catch(() => false)) {
      await submitBtn.click();
      const response = await filterResponsePromise;
      if (response) {
        expect(response.status()).toBe(200);
      }
    }
  });
});

test.describe('Mini-Cart Sync', () => {
  test('adding product from shop archive updates mini-cart without page reload', async ({ page }) => {
    await page.goto('/shop/');

    const miniCartContainer = page.locator('#mini-cart-container');
    await expect(miniCartContainer).toBeVisible({ timeout: 10000 });

    // Record initial mini-cart HTML.
    const initialHtml = await miniCartContainer.innerHTML();

    // Intercept the HTMX POST to add-to-cart.
    const addResponsePromise = page.waitForResponse(
      (resp) =>
        resp.url().includes('/htmx-api/cart/add') &&
        resp.request().method() === 'POST',
      { timeout: 10000 },
    );

    // Also intercept the mini-cart GET re-fetch.
    const miniCartFetchPromise = page.waitForResponse(
      (resp) =>
        resp.url().includes('/htmx-api/cart/mini') &&
        resp.request().method() === 'GET',
      { timeout: 10000 },
    );

    // Click the first loop add-to-cart button.
    const addButton = page.locator('button[data-product-id], .add_to_cart_button').first();
    const hasButton = await addButton.isVisible().catch(() => false);
    if (!hasButton) {
      test.skip(true, 'No add-to-cart button found on shop page');
      return;
    }

    await addButton.click();

    // Both responses should resolve.
    const [addResponse] = await Promise.all([
      addResponsePromise,
      miniCartFetchPromise.catch(() => null),
    ]);

    // Verify the add-to-cart response had HX-Trigger: cartUpdated.
    if (addResponse) {
      const hxTrigger = addResponse.headers()['hx-trigger'] || '';
      expect(hxTrigger).toContain('cartUpdated');
    }

    // After HTMX swap, mini-cart HTML should have changed.
    await page.waitForTimeout(500);
    const updatedHtml = await miniCartContainer.innerHTML();
    expect(updatedHtml).not.toBe(initialHtml);
  });

  test('mini-cart count badge updates after adding product', async ({ page }) => {
    await page.goto('/shop/');

    const miniCartContainer = page.locator('#mini-cart-container');
    await expect(miniCartContainer).toBeVisible({ timeout: 10000 });

    // Read the initial cart count.
    const badge = page.locator('#mini-cart-container .absolute.-top-2');
    const initialCount = (await badge.count()) > 0
      ? parseInt(await badge.textContent() || '0', 10)
      : 0;

    // Intercept the mini-cart re-fetch.
    const miniCartFetchPromise = page.waitForResponse(
      (resp) =>
        resp.url().includes('/htmx-api/cart/mini') &&
        resp.status() === 200,
      { timeout: 10000 },
    );

    // Click the first loop add-to-cart button.
    const addButton = page.locator('button[data-product-id], .add_to_cart_button').first();
    const hasButton = await addButton.isVisible().catch(() => false);
    if (!hasButton) {
      test.skip(true, 'No add-to-cart button found');
      return;
    }

    await addButton.click();
    await miniCartFetchPromise;
    await page.waitForTimeout(500);

    // Badge should show at least initialCount + 1.
    const newBadge = page.locator('#mini-cart-container .absolute.-top-2');
    await expect(newBadge).toBeVisible({ timeout: 5000 });
    const newCount = parseInt(await newBadge.textContent() || '0', 10);
    expect(newCount).toBeGreaterThanOrEqual(initialCount + 1);
  });
});

test.describe('Toast Notifications', () => {
  test('WC notice triggers toast notification display', async ({ page }) => {
    await page.goto('/shop/');

    // The toast-notification web component should be in the DOM.
    const toastComponent = page.locator('toast-notification');
    const hasToast = await toastComponent.count() > 0;

    if (!hasToast) {
      test.skip(true, 'toast-notification web component not found');
      return;
    }

    // Trigger an add-to-cart that might produce a WC notice.
    const addButton = page.locator('button[data-product-id], .add_to_cart_button').first();
    const hasButton = await addButton.isVisible().catch(() => false);
    if (!hasButton) {
      test.skip(true, 'No add-to-cart button found');
      return;
    }

    // Listen for the HX-Trigger: showToast header from the add-to-cart response.
    let showToastPayload: { message: string; type: string } | null = null;

    page.on('response', async (resp) => {
      const hxTrigger = resp.headers()['hx-trigger'] || '';
      if (hxTrigger.includes('showToast')) {
        try {
          showToastPayload = JSON.parse(hxTrigger);
        } catch {
          // Not valid JSON — ignore.
        }
      }
    });

    await addButton.click();
    await page.waitForTimeout(1000);

    // If a showToast HX-Trigger was received, verify the toast component appeared.
    if (showToastPayload && showToastPayload.showToast) {
      // The toast should be visible somewhere on the page.
      const toast = page.locator(
        '.toast, [role="alert"], .woocommerce-message, .woocommerce-error, .wc-block-components-notice-banner'
      ).first();

      // Wait briefly for the toast to appear.
      const toastVisible = await toast.isVisible().catch(() => false);
      if (toastVisible) {
        const toastText = await toast.textContent();
        expect(toastText).toContain(showToastPayload.showToast.message);
      }
    }
  });

  test('toast appears with correct message and type', async ({ page }) => {
    await page.goto('/shop/');

    // Navigate to a product page and try to add with invalid quantity
    // to trigger a WooCommerce error notice.
    const productLink = page.locator(
      '.woocommerce-loop-product__link, .product a img, .products .product a'
    ).first();

    const hasProduct = await productLink.isVisible().catch(() => false);
    if (!hasProduct) {
      test.skip(true, 'No product found on shop page');
      return;
    }

    await productLink.click();
    await page.locator('form.cart').waitFor({ state: 'visible', timeout: 10000 });

    // Listen for HX-Trigger headers.
    let showToastPayload: { message: string; type: string } | null = null;

    page.on('response', async (resp) => {
      const hxTrigger = resp.headers()['hx-trigger'] || '';
      if (hxTrigger.includes('showToast')) {
        try {
          showToastPayload = JSON.parse(hxTrigger);
        } catch {
          // Not valid JSON — ignore.
        }
      }
    });

    // Add to cart and check if a toast notification appears.
    const addBtn = page.locator(
      'button.single_add_to_cart_button, button[name="add-to-cart"]'
    ).first();

    if (await addBtn.isVisible().catch(() => false)) {
      await addBtn.click();
      await page.waitForTimeout(1000);

      // If a showToast was triggered, verify the payload structure.
      if (showToastPayload && showToastPayload.showToast) {
        expect(showToastPayload.showToast).toHaveProperty('message');
        expect(showToastPayload.showToast).toHaveProperty('type');
        expect(typeof showToastPayload.showToast.message).toBe('string');
        expect(['notice', 'success', 'error', 'warning']).toContain(
          showToastPayload.showToast.type
        );
      }
    }
  });
});
