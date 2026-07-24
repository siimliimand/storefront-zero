# Storefront Zero

A minimal, performance-first WordPress/WooCommerce theme built on Flight PHP, HTMX, and Tailwind CSS.

## Features

### Core
- **Flight PHP routing** — lightweight HTTP layer for HTMX fragment endpoints
- **HTMX dynamic fragments** — server-rendered partial updates without full page reloads
- **MVC architecture** — controllers with constructor DI in `app/Controllers/`, views in `app/Views/`
- **DI Container** — minimal PSR-11-style container in `app/Container.php` for `WC_Cart`, `View`, and controllers
- **Flight request API** — controllers use `Flight::request()->data` / `->query` instead of superglobals
- **Local HTMX** — bundled vendor script (`defer` loaded), no CDN dependency

### WooCommerce
- **Full template overrides** — single product, cart, checkout, my account, archive, add-to-cart, related, tabs
- **Variable product support** — `<product-variation-form>` web component handles attribute selection and HTMX submission
- **Live mini-cart sync** — header mini-cart auto-refreshes via `cartUpdated` HTMX events
- **Real-time cart operations** — quantity update and item removal via HTMX without page reloads
- **WC stubs** — `php-stubs/woocommerce-stubs` via Composer for PHPStan analysis

### Design System
- **Dark mode** — `<dark-mode-toggle>` web component with `localStorage` persistence and `prefers-color-scheme` fallback
- **Tailwind design tokens** — brand colors (50–900), `darkSurface`/`darkCard` colors, `darkMode: 'class'`
- **Component library** — `.btn-primary`, `.btn-secondary`, `.btn-outline`, `.badge`, `.form-input`, `.form-select`, `.alert-success`, `.alert-error`, `.alert-info`, `.card`
- **Inter font** — self-hosted woff2 with `<link rel="preload">` and `font-display: swap`
- **WC base resets** — `@layer base` overrides for WooCommerce default styles
- **Design token audit** — all raw Tailwind colors replaced with brand token references

### Accessibility
- **Skip-to-content link** — visible on focus, placed after `wp_body_open()`
- **Semantic HTML** — `<button>` for add-to-cart (not `<a>`), `<form role="search">`, `<nav>` landmarks
- **ARIA labels** — mobile menu toggle, search input, product actions
- **`aria-live` regions** — search results and mini-cart announce updates to screen readers
- **Toast stacking** — vertical container prevents overlapping notifications
- **Translatable strings** — `esc_html_e()` with `'storefront-zero'` text domain

### Security
- **Sanitized superglobals** — `$_SERVER['REQUEST_METHOD']` and `$_SERVER['HTTP_X_WP_NONCE']` wrapped with `sanitize_text_field(wp_unslash())`
- **Nonce-scoped security** — dedicated `storefront_zero_htmx` nonce for HTMX requests
- **Cache-safe nonces** — `GET /htmx-api/nonce` endpoint with auto-retry on 403 (uses `ThemeSettings.endpoint`)
- **CSRF on coupon form** — `wp_nonce_field()` added to cart coupon form
- **Flight request API** — no direct `$_POST`/`$_GET` access in controllers

### Performance
- **Lazy product images** — `loading="lazy"` on cart and archive product thumbnails
- **Deferred HTMX** — `defer` attribute on HTMX script tag
- **xxh3 cache keys** — faster than `md5()` for transient cache keys
- **Batch product queries** — `wc_get_products(['include' => $ids])` replaces N+1 `wc_get_product()` calls
- **filemtime fallback** — safe version strings when build files don't exist yet
- **esbuild minification** — JS bundle pipeline for `app.js` and web components
- **WC script dequeuing** — `select2`, `zoom`, `prettyPhoto` dequeued on non-product pages

### Code Quality
- **PHPStan Level 8** — static analysis with `phpstan-wordpress` extension
- **Pest tests** — behavioral tests for CartController, ProductController, and View::render
- **Playwright E2E tests** — end-to-end tests for HTMX cart and search interactions
- **CI pipeline** — GitHub Actions for PHPStan, Pest, npm build, and Playwright
- **`.editorconfig`** — consistent indentation across PHP, JS, CSS, HTML
- **`declare(strict_types=1)`** — on all PHP files including view templates

## Requirements

- PHP 8.3+
- WordPress 6.4+
- WooCommerce 8.0+
- Composer
- Node.js 18+

## Installation

1. Clone or download this repository into `wp-content/themes/storefront-zero/`.

2. Install PHP dependencies:

   ```bash
   composer install
   ```

3. Install and build frontend assets:

   ```bash
   npm install && npm run build
   ```

4. Activate the theme in WordPress admin → Appearance → Themes.

## Project Structure

```
storefront-zero/
├── app/
│   ├── Controllers/
│   │   ├── CartController.php      # Cart operations (instance methods, DI)
│   │   └── ProductController.php   # Live search (instance methods, DI)
│   ├── Views/
│   │   ├── View.php                # View renderer with buffer-level safety
│   │   ├── cart-error.php
│   │   ├── mini-cart.php
│   │   ├── mini-cart-fragment.php  # HTMX-swappable mini-cart header fragment
│   │   └── search-results.php
│   ├── Container.php               # Minimal DI container
│   └── routes.php                  # Flight routes + nonce middleware
├── assets/
│   ├── css/
│   │   ├── input.css               # Tailwind directives + @font-face + component classes
│   │   └── main.css                # Compiled output
│   ├── fonts/
│   │   └── inter-latin.woff2       # Self-hosted Inter variable font
│   └── js/
│       ├── app.js                  # HTMX config, nonce retry, toast, consolidated listeners
│       ├── qty-stepper.js          # Quantity +/- button handlers
│       ├── dist/                   # esbuild minified output
│       ├── vendor/
│       │   └── htmx.min.js         # Local HTMX v1.9.10
│       └── web-components/
│           ├── dark-mode-toggle.js  # Dark mode with localStorage + OS preference
│           ├── mobile-drawer.js     # Mobile navigation
│           ├── product-variation-form.js # Variable product HTMX integration
│           └── toast-notification.js    # Stacking toast notifications
├── template-parts/
│   ├── content.php                 # Post excerpt with translatable "Read more"
│   ├── content-none.php
│   └── content-page.php
├── tests/
│   ├── Feature/
│   │   ├── CartControllerTest.php  # 11 behavioral tests
│   │   ├── ProductControllerTest.php # 7 behavioral tests
│   │   └── ViewRenderTest.php      # 10 buffer-safety tests
│   ├── e2e/
│   │   ├── cart.spec.ts            # Playwright HTMX cart tests
│   │   └── search.spec.ts          # Playwright HTMX search tests
│   ├── Controllers/                # Source-reading contract tests
│   ├── Routes/
│   ├── Views/
│   ├── Pest.php
│   ├── TestCase.php
│   └── helpers-wp-stubs.php
├── woocommerce/
│   ├── archive-product.php         # Product archive with WC content hooks
│   ├── single-product.php          # Single product with WC content hooks
│   ├── cart/
│   │   └── cart.php                # HTMX cart with coupon nonce
│   ├── checkout/
│   │   └── form-checkout.php       # Tailwind-styled checkout form
│   ├── myaccount/
│   │   ├── dashboard.php           # Customer dashboard
│   │   └── navigation.php          # Account navigation
│   ├── single-product/
│   │   ├── add-to-cart/
│   │   │   └── simple.php          # Simple product add-to-cart
│   │   ├── related.php             # Related products grid
│   │   └── tabs/
│   │       ├── description.php
│   │       └── additional-information.php
│   ├── global/
│   │   └── quantity-input.php
│   ├── loop/
│   │   └── add-to-cart.php         # HTMX add-to-cart (button element)
│   └── notices/
│       ├── success.php
│       └── error.php
├── functions.php                   # Theme setup, enqueues, Flight init, lazy images, defer
├── header.php                      # Header with search, mini-cart, dark toggle, font preload
├── footer.php
├── index.php
├── page.php                        # Static pages
├── single.php                      # Blog posts
├── search.php                      # Search results
├── archive.php                     # Archives
├── 404.php                         # Not found with search
├── front-page.php                  # Front page with hero + product grid
├── sidebar.php
├── .editorconfig
├── .github/
│   └── workflows/
│       └── ci.yml                  # GitHub Actions CI pipeline
├── phpstan.neon                    # PHPStan Level 8 configuration
├── playwright.config.ts            # Playwright E2E test configuration
├── package.json                    # Tailwind + esbuild + Playwright
├── composer.json                   # PHP deps: WC stubs, Pest, PHPStan
├── tailwind.config.js
├── scripts/
│   └── build-js.js                 # esbuild JS build script
└── style.css                       # Theme metadata
```

## Development

### Build Commands

```bash
npm run dev          # Tailwind CSS watch mode
npm run build        # Combined CSS + JS production build
npm run build:js     # JS-only build (esbuild minification)
npm run test:e2e     # Playwright end-to-end tests
```

### Static Analysis

```bash
vendor/bin/phpstan analyse
```

### Tests

```bash
vendor/bin/pest              # Unit/behavioral tests
npm run test:e2e             # Playwright E2E (requires running WP)
```

### MVC Architecture

The theme uses a lightweight MVC pattern with dependency injection:

- **Container** (`app/Container.php`) — registers `WC_Cart`, `View`, and controller factories
- **Controllers** (`app/Controllers/`) — instance methods with constructor-injected dependencies
- **Views** (`app/Views/`) — plain PHP templates rendered via `$this->view->render($name, $data)`
- **Routes** (`app/routes.php`) — Flight PHP routes resolve controllers from the DI container

```php
// Container creates controllers with dependencies
$container = Container::create();
$cart = $container->get(CartController::class);

// Routes resolve from container
Flight::route('POST /cart/add', function () use ($container) {
    $container->get(CartController::class)->addToCart();
});
```

### Flight PHP Routes

| Method | Route | Controller | Description |
|---|---|---|---|
| GET | `/search` | `ProductController::liveSearch()` | Live product search (cached 60s, xxh3 hash) |
| GET | `/nonce` | inline JSON | Fresh nonce for cache-safe requests |
| GET | `/cart/mini` | `CartController::renderMiniCart()` | Mini-cart HTML fragment |
| POST | `/cart/add` | `CartController::addToCart()` | Add product (supports variations) |
| POST | `/cart/update-qty` | `CartController::updateQuantity()` | Update cart item quantity |
| DELETE | `/cart/remove` | `CartController::removeItem()` | Remove item from cart |

### Web Components

| Component | File | Purpose |
|---|---|---|
| `<dark-mode-toggle>` | `dark-mode-toggle.js` | Dark mode with localStorage + OS preference |
| `<mobile-drawer>` | `mobile-drawer.js` | Mobile navigation with ARIA |
| `<toast-notification>` | `toast-notification.js` | Stacking toast notifications |
| `<product-variation-form>` | `product-variation-form.js` | Variable product HTMX add-to-cart |

### WooCommerce Templates

| Template | Purpose |
|---|---|
| `single-product.php` | Single product with `woocommerce_before/after_main_content` hooks |
| `archive-product.php` | Product archive with WC content hooks |
| `cart/cart.php` | HTMX cart with coupon nonce field |
| `checkout/form-checkout.php` | Tailwind-styled checkout form |
| `myaccount/dashboard.php` | Customer dashboard |
| `myaccount/navigation.php` | Account navigation sidebar |
| `single-product/add-to-cart/simple.php` | Simple product add-to-cart with HTMX |
| `single-product/related.php` | Related products grid (4 max) |
| `single-product/tabs/description.php` | Product description tab |
| `single-product/tabs/additional-information.php` | Product attributes table |
| `loop/add-to-cart.php` | `<button>` add-to-cart (not `<a>`) |
| `global/quantity-input.php` | Quantity stepper |
| `notices/success.php` | Green Tailwind alert |
| `notices/error.php` | Red Tailwind alert |

## CI/CD

GitHub Actions workflow (`.github/workflows/ci.yml`) runs on push to `main`/`staging` and PRs:

1. **php-tests** — PHPStan Level 8 + Pest tests
2. **js-build** — `npm run build` (Tailwind + esbuild)
3. **e2e-tests** — Playwright browser tests (requires running WordPress instance)

## Plugin Compatibility

### WP Rocket

1. Exclude `htmx.min.js` and `app.js` from JavaScript Delay/Minification
2. Nonce auto-retry handles stale cached nonces automatically
3. Configure fragment caching to bypass for cart/checkout responses

### Fragment Caching

See nginx, Varnish, and Redis configuration examples in the [full caching guide](#fragment-caching-1).

## Contributing

1. Fork the repository.
2. Create a feature branch from `staging`.
3. Make your changes following WordPress Coding Standards.
4. Run `composer install && vendor/bin/phpstan analyse && vendor/bin/pest` before submitting.
5. Submit a pull request with a clear description of the change.

## License

GPL v2 or later — same as WordPress.
