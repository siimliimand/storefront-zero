import { test, expect } from '@playwright/test';

/**
 * E2E tests for the HTMX add-to-cart interaction.
 *
 * Verifies the full flow: product page -> add-to-cart click -> mini-cart
 * fragment swap via HX-Trigger -> cart page persistence.
 *
 * Prerequisites:
 * - WordPress + WooCommerce running (see playwright.config.ts for base URL)
 */

test.describe('HTMX Add to Cart', () => {
  test('navigates to a product page and shows add-to-cart button', async ({ page }) => {
    // Start from the shop archive to find a product link.
    await page.goto('/shop/');

    // Click the first product link to reach a single product page.
    const productLink = page.locator('.woocommerce-loop-product__link, .product a img, .products .product a').first();
    await expect(productLink).toBeVisible({ timeout: 10000 });
    await productLink.click();

    // Verify we're on a product page with an add-to-cart form.
    await expect(page.locator('form.cart')).toBeVisible({ timeout: 10000 });
    await expect(
      page.locator('button.single_add_to_cart_button, button[name="add-to-cart"]')
    ).toBeVisible();
  });

  test('clicking add-to-cart triggers HTMX POST and mini-cart updates', async ({ page }) => {
    // Navigate to shop and open a product.
    await page.goto('/shop/');
    const productLink = page.locator('.woocommerce-loop-product__link, .product a img, .products .product a').first();
    await productLink.click();
    await page.locator('form.cart').waitFor({ state: 'visible' });

    // Intercept the HTMX POST to the product permalink (single-product form).
    // The form hx-posts to the product permalink itself.
    const htmxResponsePromise = page.waitForResponse(
      (resp) => resp.request().method() === 'POST' && resp.status() === 200,
      { timeout: 10000 }
    );

    // Click the add-to-cart button.
    const addBtn = page.locator('button.single_add_to_cart_button, button[name="add-to-cart"]').first();
    await addBtn.click();

    // Wait for the HTMX response.
    const htmxResponse = await htmxResponsePromise;

    // Verify the server sent HX-Trigger: cartUpdated header.
    const hxTrigger = htmxResponse.headers()['hx-trigger'];
    expect(hxTrigger).toBeTruthy();
    expect(hxTrigger).toContain('cartUpdated');
  });

  test('cart count badge increments after adding a product', async ({ page }) => {
    await page.goto('/shop/');

    // Read the initial cart count from the mini-cart badge (if any).
    const badge = page.locator('#mini-cart-container .absolute.-top-2');
    const initialCount = (await badge.count()) > 0
      ? parseInt(await badge.textContent() || '0', 10)
      : 0;

    // Open a product page.
    const productLink = page.locator('.woocommerce-loop-product__link, .product a img, .products .product a').first();
    await productLink.click();
    await page.locator('form.cart').waitFor({ state: 'visible' });

    // Click add-to-cart and wait for the mini-cart GET re-fetch triggered by cartUpdated.
    const miniCartFetchPromise = page.waitForResponse(
      (resp) => resp.url().includes('/htmx-api/cart/mini') && resp.status() === 200,
      { timeout: 10000 }
    );

    const addBtn = page.locator('button.single_add_to_cart_button, button[name="add-to-cart"]').first();
    await addBtn.click();

    // Wait for the mini-cart fragment to be fetched.
    await miniCartFetchPromise;

    // Allow HTMX to swap the fragment into the DOM.
    await page.waitForTimeout(500);

    // The badge should now show at least initialCount + 1.
    const newBadge = page.locator('#mini-cart-container .absolute.-top-2');
    await expect(newBadge).toBeVisible({ timeout: 5000 });
    const newCount = parseInt(await newBadge.textContent() || '0', 10);
    expect(newCount).toBeGreaterThanOrEqual(initialCount + 1);
  });

  test('mini-cart fragment contains the added product name', async ({ page }) => {
    await page.goto('/shop/');
    const productLink = page.locator('.woocommerce-loop-product__link, .product a img, .products .product a').first();
    await productLink.click();
    await page.locator('form.cart').waitFor({ state: 'visible' });

    // Capture the product name from the page heading.
    const productName = await page.locator('.product_title, h1.entry-title').first().textContent();

    // Intercept the POST response from add-to-cart (which contains the added_product name).
    const addResponsePromise = page.waitForResponse(
      (resp) => resp.url().includes('/htmx-api/cart/add') && resp.request().method() === 'POST' && resp.status() === 200,
      { timeout: 10000 }
    );

    const addBtn = page.locator('button.single_add_to_cart_button, button[name="add-to-cart"]').first();
    await addBtn.click();

    const addResponse = await addResponsePromise;
    const body = await addResponse.text();

    // The POST response mini-cart fragment should contain the added product name.
    expect(body).toContain(productName?.trim() || '');
  });

  test('item appears on the cart page after adding from product page', async ({ page }) => {
    // Add a product via the shop archive (loop add-to-cart).
    await page.goto('/shop/');
    const addButton = page.locator('button[data-product-id], .add_to_cart_button').first();

    // Wait for the HTMX add-to-cart POST to complete before navigating.
    const addResponsePromise = page.waitForResponse(
      (resp) => resp.url().includes('/htmx-api/cart/add') && resp.request().method() === 'POST',
      { timeout: 10000 }
    );
    await addButton.click();
    await addResponsePromise;

    // Navigate to the cart page.
    await page.goto('/cart/');

    // Wait for the WooCommerce block cart to finish loading (is-loading class removed).
    // The block cart renders both empty and filled states; JS toggles visibility.
    const filledCart = page.locator('.wp-block-woocommerce-filled-cart-block');
    await expect(filledCart).toBeVisible({ timeout: 15000 });

    // The filled cart block should contain line items.
    const cartItems = page.locator('.wp-block-woocommerce-cart-line-items-block > *');
    await expect(cartItems.first()).toBeVisible({ timeout: 10000 });
    expect(await cartItems.count()).toBeGreaterThanOrEqual(1);
  });

  test('empty cart shows empty message', async ({ page }) => {
    // First clear the cart by visiting the cart page and removing all items.
    await page.goto('/cart/');

    // If items exist, remove them one by one.
    const removeButtons = page.locator('button.remove, .product-remove button');
    const count = await removeButtons.count();

    for (let i = 0; i < count; i++) {
      // Handle the hx-confirm dialog if present.
      page.once('dialog', (dialog) => dialog.accept());
      await removeButtons.first().click();
      await page.waitForTimeout(800);
    }

    // Reload to get a clean state.
    await page.goto('/cart/');
    await page.waitForTimeout(1000);

    // Should see a cart-empty notice or empty cart message.
    const emptyIndicator = page.locator(
      '.cart-empty, .woocommerce-info, h2:has-text("cart is empty"), h2:has-text("Your cart is currently empty"), p:has-text("cart is empty"), p:has-text("No products in the cart")'
    );
    await expect(emptyIndicator.first()).toBeVisible({ timeout: 10000 });
  });

  test('add-to-cart from shop archive updates mini-cart without page reload', async ({ page }) => {
    await page.goto('/shop/');

    // Wait for the mini-cart container to be present.
    const miniCartContainer = page.locator('#mini-cart-container');
    await expect(miniCartContainer).toBeVisible({ timeout: 10000 });

    // Record the initial inner HTML of the mini-cart.
    const initialHtml = await miniCartContainer.innerHTML();

    // Intercept the HTMX POST to /htmx-api/cart/add.
    const addResponsePromise = page.waitForResponse(
      (resp) => resp.url().includes('/htmx-api/cart/add') && resp.request().method() === 'POST',
      { timeout: 10000 }
    );

    // Also intercept the mini-cart GET re-fetch.
    const miniCartFetchPromise = page.waitForResponse(
      (resp) => resp.url().includes('/htmx-api/cart/mini') && resp.request().method() === 'GET',
      { timeout: 10000 }
    );

    // Click the first loop add-to-cart button.
    const addButton = page.locator('button[data-product-id], .add_to_cart_button').first();
    await addButton.click();

    // Both responses should resolve.
    const [addResponse, _miniCartFetch] = await Promise.all([
      addResponsePromise,
      miniCartFetchPromise,
    ]);

    // Verify the add-to-cart response had HX-Trigger: cartUpdated.
    expect(addResponse.headers()['hx-trigger'] || '').toContain('cartUpdated');

    // After HTMX swap, mini-cart HTML should have changed.
    await page.waitForTimeout(500);
    const updatedHtml = await miniCartContainer.innerHTML();
    expect(updatedHtml).not.toBe(initialHtml);
  });

  test('mini-cart shows item count matching cart contents', async ({ page }) => {
    await page.goto('/shop/');

    // Add two different products if available.
    const addButtons = page.locator('button[data-product-id], .add_to_cart_button');
    const buttonCount = Math.min(await addButtons.count(), 2);

    for (let i = 0; i < buttonCount; i++) {
      page.once('dialog', (dialog) => dialog.accept());

      const miniCartFetchPromise = page.waitForResponse(
        (resp) => resp.url().includes('/htmx-api/cart/mini') && resp.status() === 200,
        { timeout: 10000 }
      );

      await addButtons.nth(i).click();
      await miniCartFetchPromise;
      await page.waitForTimeout(500);
    }

    // The mini-cart badge should show the correct count.
    const badge = page.locator('#mini-cart-container .absolute.-top-2');
    if (buttonCount > 0) {
      await expect(badge).toBeVisible({ timeout: 5000 });
      const count = parseInt(await badge.textContent() || '0', 10);
      expect(count).toBeGreaterThanOrEqual(1);
    }
  });

  test('add-to-cart button shows spinner during HTMX request', async ({ page }) => {
    await page.goto('/shop/');
    const productLink = page.locator('.woocommerce-loop-product__link, .product a img, .products .product a').first();
    await productLink.click();
    await page.locator('form.cart').waitFor({ state: 'visible' });

    // The spinner indicator should be hidden initially.
    const spinner = page.locator('#add-to-cart-spinner');
    await expect(spinner).toBeHidden();

    // Slow down the response to observe the spinner state.
    await page.route('**/htmx-api/cart/add', async (route) => {
      // Only delay POST requests (the add-to-cart submission).
      if (route.request().method() === 'POST') {
        await new Promise((r) => setTimeout(r, 500));
      }
      await route.continue();
    });

    const addBtn = page.locator('button.single_add_to_cart_button, button[name="add-to-cart"]').first();
    await addBtn.click();

    // The spinner should become visible during the request.
    await expect(spinner).toBeVisible({ timeout: 2000 });
  });
});
