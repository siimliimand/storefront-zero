# Storefront Zero

A minimal, performance-first WordPress/WooCommerce theme.

## Features

- **Flight PHP routing** — lightweight HTTP layer for HTMX fragment endpoints
- **HTMX dynamic fragments** — server-rendered partial updates without full page reloads
- **MVC architecture** — controllers in `app/Controllers/`, views in `app/Views/`
- **Web Components** — encapsulated interactive UI widgets with ARIA support
- **Tailwind CSS** — utility-first styling with design tokens, dark mode, and component layers
- **WooCommerce templates** — full overrides for single product, cart, quantity input, add-to-cart, notices, and archive
- **Local HTMX** — bundled vendor script, no CDN dependency
- **Nonce-scoped security** — dedicated `storefront_zero_htmx` nonce for HTMX requests
- **Cache-safe nonces** — `GET /htmx-api/nonce` endpoint with auto-retry on 403 for full-page cache compatibility
- **Live mini-cart sync** — header mini-cart auto-refreshes via `cartUpdated` HTMX events
- **Real-time cart operations** — quantity update and item removal via HTMX without page reloads
- **Toast notifications** — `<toast-notification>` web component for transient user feedback
- **Subdirectory-aware routing** — Flight PHP init normalizes URIs via `parse_url()` + `home_url()` for subdirectory installs
- **Safe view rendering** — `View::render()` wraps output buffer in `try/finally` to prevent leaks on exceptions
- **Transient caching** — product search results cached as product IDs (not serialized objects)
- **Rate limiting** — transient-based caching on search endpoint (60s TTL)
- **Security hardening** — sanitized server variables, path traversal guards, Content-Type headers
- **Accessibility** — skip-to-content link, ARIA labels, `aria-live` regions for HTMX swaps, translatable strings, focus management
- **Design system** — Tailwind brand tokens (50–900), `darkSurface`/`darkCard` colors, fade-in animation, `darkMode: 'class'`
- **Static analysis** — PHPStan Level 8 with `phpstan-wordpress` extension
- **Automated tests** — Pest PHP test suite covering routes, controllers, and view rendering
- **Performance** — WC script dequeuing on non-product pages, transient caching, `type="module"` for web components

## Requirements

- PHP 8.1+
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
│   ├── Controllers/              # Flight PHP route handlers
│   │   ├── CartController.php    # Cart operations: add, update qty, remove, mini-cart
│   │   ├── NonceController.php   # Nonce refresh endpoint
│   │   └── ProductController.php # Live product search with transient caching
│   ├── Views/                    # View templates rendered by ThemeApp\View
│   │   ├── View.php              # Static view renderer with try/finally safety
│   │   ├── cart-error.php
│   │   ├── mini-cart.php         # Detailed mini-cart panel
│   │   ├── mini-cart-fragment.php # HTMX-swappable mini-cart header fragment
│   │   └── search-results.php
│   ├── woocommerce-stubs.php     # PHPStan stubs for WC_Product and WC_Cart
│   └── routes.php                # Flight route definitions + nonce middleware
├── assets/
│   ├── css/
│   │   ├── input.css             # Tailwind directives + @layer components
│   │   └── main.css              # Compiled output
│   └── js/
│       ├── app.js                # HTMX config, nonce auto-retry, toast listener, a11y
│       ├── vendor/
│       │   └── htmx.min.js       # Local HTMX v1.9.10
│       └── web-components/
│           ├── mobile-drawer.js   # Mobile navigation
│           └── toast-notification.js # Transient toast notifications
├── template-parts/               # WordPress loop templates
│   ├── content.php
│   ├── content-none.php
│   └── content-page.php
├── tests/                        # Pest PHP test suite
│   ├── Pest.php                  # Pest config
│   ├── TestCase.php              # Base test case with WP stubs
│   ├── helpers.php               # Test helpers
│   ├── helpers-wp-stubs.php      # Minimal WP/WC function stubs
│   ├── Routes/
│   │   └── RouteTest.php
│   ├── Controllers/
│   │   ├── CartControllerTest.php
│   │   ├── NonceControllerTest.php
│   │   └── ProductControllerTest.php
│   └── Views/
│       └── ViewTest.php
├── woocommerce/                  # WooCommerce template overrides
│   ├── archive-product.php
│   ├── cart/
│   │   └── cart.php              # HTMX-powered cart with live qty/remove
│   ├── global/
│   │   └── quantity-input.php
│   ├── loop/
│   │   └── add-to-cart.php
│   ├── notices/
│   │   ├── success.php
│   │   └── error.php
│   └── single-product.php
├── functions.php                 # Theme setup, enqueues, Flight init, WC asset management
├── header.php                    # Site header with HTMX search, mini-cart, skip-to-content
├── footer.php
├── index.php
├── sidebar.php
├── phpstan.neon                  # PHPStan Level 8 configuration
├── phpunit.xml.dist              # PHPUnit/Pest configuration
├── tailwind.config.js            # Tailwind config with dark mode, brand tokens, animations
└── style.css                     # Theme metadata
```

## Development

Run Tailwind CSS in watch mode for live-reloading styles:

```bash
npm run dev
```

### Static Analysis

PHPStan is configured at Level 8 with the WordPress extension:

```bash
vendor/bin/phpstan analyse
```

### Tests

Run the Pest PHP test suite:

```bash
vendor/bin/pest
```

### MVC Architecture

The theme uses a lightweight MVC pattern:

- **Controllers** (`app/Controllers/`) handle request logic and delegate rendering to views.
- **Views** (`app/Views/`) are plain PHP templates rendered via `ThemeApp\View::render($name, $data)`.
- **Routes** (`app/routes.php`) map Flight PHP routes to controller methods.

Example:

```php
// In a controller
use ThemeApp\View;

View::render('search-results', [
    'products' => $products,
    'query'    => $query,
]);
```

### Flight PHP Routes

Flight PHP handles HTMX fragment requests at `/htmx-api/*`. All mutating requests (POST, PUT, DELETE) are verified against the `storefront_zero_htmx` nonce via middleware in `app/routes.php`.

| Method | Route | Controller | Description |
|---|---|---|---|
| GET | `/search` | `ProductController::liveSearch()` | Live product search (rate-limited, cached 60s) |
| GET | `/nonce` | inline JSON | Fresh nonce for cache-safe requests (cache-bustable) |
| GET | `/cart/mini` | `CartController::renderMiniCart()` | Mini-cart HTML fragment for header sync |
| POST | `/cart/add` | `CartController::addToCart()` | Add product to cart |
| POST | `/cart/update-qty` | `CartController::updateQuantity()` | Update cart item quantity |
| DELETE | `/cart/remove` | `CartController::removeItem()` | Remove item from cart |

### Subdirectory-Aware Routing

The theme handles both root and subdirectory WordPress installs. `storefront_zero_flight_init()` in `functions.php` normalizes the request URI against `home_url()` via `parse_url()`:

- Root install (`example.com/`): `/htmx-api/search` → routes through Flight
- Subdirectory install (`example.com/shop/`): `/shop/htmx-api/search` → routes through Flight
- Non-API requests pass through to the WordPress template hierarchy

### Nonce Cache Compatibility

Full-page caches (WP Rocket, Varnish, FastCGI) can serve stale pages with expired nonces. The theme handles this with:

1. **`GET /htmx-api/nonce`** — returns a fresh nonce (cache-bustable via `?_t=<timestamp>`)
2. **Auto-retry in `app.js`** — listens for `htmx:responseError` 403, fetches a fresh nonce, updates `window.ThemeSettings.nonce`, and retries the failed request (once per request)

### HTMX Fragments

HTMX attributes trigger requests to the Flight PHP routes. Each route returns a partial HTML response that HTMX swaps into the DOM — no full page reloads needed.

HTMX is loaded locally from `assets/js/vendor/htmx.min.js` (v1.9.10) to avoid CDN dependencies. The search results dropdown auto-toggles visibility via an `htmx:afterSwap` listener.

### Live Mini-Cart

The header mini-cart auto-refreshes after cart operations:

1. `CartController::addToCart()`, `updateQuantity()`, and `removeItem()` all set `HX-Trigger: cartUpdated`
2. `header.php` wraps the mini-cart in `<div id="mini-cart-container" hx-get="/htmx-api/cart/mini" hx-trigger="load, cartUpdated from:body" hx-swap="outerHTML" aria-live="polite">`
3. The `mini-cart-fragment.php` view renders the cart icon with item count badge

### Toast Notifications

The `<toast-notification>` web component displays transient user feedback:

- **Light DOM** — inherits Tailwind CSS classes from the parent document
- **Auto-dismiss** — fades out after 3 seconds
- **Types** — `success` (green) and `error` (red)
- **HTMX integration** — `app.js` parses `HX-Trigger` headers for `showToast` events and injects toast elements

```html
<toast-notification message="Item added to cart" type="success"></toast-notification>
```

Server-side trigger via response header:
```
HX-Trigger: {"showToast": {"message": "Item added", "type": "success"}}
```

### Web Components

The theme uses web components for interactive widgets. Each component is self-registering via `customElements.define()` in its own file under `assets/js/web-components/`. Scripts are loaded as ES modules via a `script_loader_tag` filter.

Current components:
- `<mobile-drawer>` — mobile navigation with ARIA attributes, Escape key support, deterministic IDs, and focus management
- `<toast-notification>` — transient toast notifications with auto-dismiss and HTMX integration

### WooCommerce Templates

The theme provides full WooCommerce template overrides:

| Template | Purpose |
|---|---|
| `single-product.php` | Single product page with theme header/footer |
| `cart/cart.php` | HTMX-powered cart with live quantity update and item removal |
| `global/quantity-input.php` | Quantity stepper with +/- buttons |
| `loop/add-to-cart.php` | HTMX-powered add-to-cart button |
| `notices/success.php` | Green Tailwind alert box |
| `notices/error.php` | Red Tailwind alert box |
| `archive-product.php` | Product archive (uses theme header/footer) |

### Design System

Tailwind CSS is configured with design tokens in `tailwind.config.js`:

- **Dark mode**: `darkMode: 'class'` — enable via `class="dark"` on `<html>`
- **Brand colors**: `brand-50` through `brand-900` (blue palette)
- **Surface colors**: `surface` (white), `muted` (gray), `darkSurface` (#1a1a2e), `darkCard` (#16213e)
- **Animations**: `animate-fade-in` for fade-in transitions
- **Font family**: Inter with system fallbacks

Component classes in `assets/css/input.css`:
- `.btn-primary` — branded button with hover, focus ring, and transitions
- `.card` — surface card with border, shadow, and padding

### Accessibility

All interactive components include:
- **Skip-to-content link** — visible on focus, placed after `wp_body_open()`
- **ARIA labels** — search input, spinner, Read more links with post titles
- **`aria-live` regions** — search results and mini-cart announce updates to screen readers
- **`role="status"`** — toast notifications announced by assistive technology
- **Keyboard navigation** — Escape key handlers, focus management in drawers
- **Translatable strings** — `_n()` for singular/plural, text domain `'storefront-zero'`
- **Semantic HTML** — `<nav>` landmarks instead of `role="menu"` / `role="menuitem"`

## Security

- **Nonce scoping**: HTMX requests use a dedicated `storefront_zero_htmx` nonce (not the default `wp_rest` nonce).
- **Middleware verification**: All mutating HTMX requests (POST, PUT, DELETE) pass through Flight middleware that verifies the nonce.
- **Cache-safe nonces**: `GET /htmx-api/nonce` endpoint provides fresh nonces for full-page cached sites, with auto-retry in `app.js`.
- **Input sanitization**: `$_SERVER['REQUEST_URI']` sanitized with `sanitize_text_field(wp_unslash())`.
- **Path traversal guard**: `basename()` applied to view names in `View::render()`.
- **Output escaping**: WordPress escaping functions (`esc_html()`, `esc_url()`, `esc_attr()`, `wp_kses_post()`) used throughout.
- **Content-Type headers**: All HTMX responses include `Content-Type: text/html; charset=utf-8`.
- **Rate limiting**: Search endpoint cached with 60s transient TTL.
- **Strict types**: `declare(strict_types=1)` on all PHP files.

## Performance

- **WC script dequeuing**: `select2`, `zoom`, `prettyPhoto`, and variation scripts are dequeued on non-product pages.
- **Transient caching**: Search results cached as product IDs (not serialized objects) for 60 seconds.
- **Local HTMX**: No CDN dependency, single request.
- **type="module"**: Web component scripts loaded as ES modules for modern browser optimization.
- **Product ID hydration**: Transients store lightweight IDs instead of full `WC_Product` objects, avoiding serialization bloat.

## Plugin Compatibility

### WP Rocket

To ensure HTMX functionality works correctly with WP Rocket caching:

1. **Exclude JS from Delay/Minification:**
   Go to WP Rocket → Settings → File Optimization → JavaScript Files
   - Add `htmx.min.js` to "Exclude JavaScript Files"
   - Add `app.js` to "Exclude JavaScript Files"

   These files must load immediately (not deferred) for HTMX to function properly.

2. **Nonce Auto-Retry:**
   The theme automatically handles stale nonces from full-page caches. When a 403 response is received, `app.js` fetches a fresh nonce from `/htmx-api/nonce` and retries the request.

3. **Fragment Caching:**
   Flight PHP serves HTML fragments for HTMX requests. Configure your server cache to:
   - Cache static fragments (search results, page content)
   - Bypass cache for cart/checkout responses (they set `Set-Cookie` headers)

### Fragment Caching

Flight PHP serves HTML fragments for HTMX requests. Configure your server cache to differentiate between static fragments (search results, page content) and dynamic cart responses.

#### Nginx

```nginx
# Cache HTMX fragment responses
location /htmx-api/ {
    # Don't cache cart/checkout responses (they set cookies)
    set $skip_cache 0;
    if ($request_uri ~* "/cart/") {
        set $skip_cache 1;
    }

    # Cache static fragments for 5 minutes
    proxy_cache_valid 200 5m;
    proxy_cache_bypass $skip_cache;
    proxy_no_cache $skip_cache;

    # Pass Set-Cookie headers for cart responses
    proxy_hide_header Set-Cookie;
    proxy_ignore_headers Set-Cookie;
}
```

#### Varnish

```vcl
sub vcl_backend_response {
    # Don't cache cart responses
    if (bereq.url ~ "/cart/") {
        set beresp.uncacheable = true;
        set beresp.ttl = 0s;
        return (deliver);
    }

    # Cache other HTMX fragments for 5 minutes
    if (bereq.url ~ "/htmx-api/") {
        set beresp.ttl = 5m;
    }
}
```

#### Redis (Object Cache)

```php
// In wp-config.php or a custom plugin
if (defined('WP_CACHE') && WP_CACHE) {
    // Skip cache for cart/checkout requests
    if (preg_match('#/cart/#', $_SERVER['REQUEST_URI'])) {
        define('WP_CACHE', false);
    }
}
```

## Contributing

1. Fork the repository.
2. Create a feature branch from `main`.
3. Make your changes following WordPress Coding Standards.
4. Run `composer install` and `vendor/bin/phpstan analyse` before submitting.
5. Submit a pull request with a clear description of the change.

## License

GPL v2 or later — same as WordPress.
