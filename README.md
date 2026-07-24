# Storefront Zero

A minimal, performance-first WordPress/WooCommerce theme.

## Features

- **Flight PHP routing** — lightweight HTTP layer for HTMX fragment endpoints
- **HTMX dynamic fragments** — server-rendered partial updates without full page reloads
- **MVC architecture** — controllers in `app/Controllers/`, views in `app/Views/`
- **Web Components** — encapsulated interactive UI widgets with ARIA support
- **Tailwind CSS** — utility-first styling with design tokens and component layers
- **WooCommerce templates** — full overrides for single product, cart, quantity input, add-to-cart, notices, and archive
- **Local HTMX** — bundled vendor script, no CDN dependency
- **Nonce-scoped security** — dedicated `storefront_zero_htmx` nonce for HTMX requests
- **Rate limiting** — transient-based caching on search endpoint (60s TTL)
- **Security hardening** — sanitized server variables, path traversal guards, Content-Type headers
- **Accessibility** — skip-to-content link, ARIA labels, translatable strings, focus management
- **Design system** — Tailwind brand tokens (colors, fonts), reusable `.btn-primary` and `.card` components
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
│   ├── Controllers/          # Flight PHP route handlers
│   │   ├── CartController.php
│   │   └── ProductController.php
│   ├── Views/                # View templates rendered by ThemeApp\View
│   │   ├── View.php          # Static view renderer with path traversal guard
│   │   ├── cart-error.php
│   │   ├── mini-cart.php
│   │   └── search-results.php
│   └── routes.php            # Flight route definitions + nonce middleware
├── assets/
│   ├── css/
│   │   ├── input.css         # Tailwind directives + @layer components
│   │   └── main.css          # Compiled output
│   └── js/
│       ├── app.js            # HTMX config, search toggle, a11y handlers
│       ├── vendor/
│       │   └── htmx.min.js   # Local HTMX v1.9.10
│       └── web-components/
│           └── mobile-drawer.js
├── template-parts/           # WordPress loop templates
│   ├── content.php           # Blog post display with aria-labels
│   ├── content-none.php      # No posts found
│   └── content-page.php      # Page display
├── woocommerce/              # WooCommerce template overrides
│   ├── archive-product.php   # Product archive (fixed header/footer)
│   ├── cart/
│   │   └── cart.php          # Tailwind-styled cart page
│   ├── global/
│   │   └── quantity-input.php # Quantity stepper with +/- buttons
│   ├── loop/
│   │   └── add-to-cart.php   # HTMX-powered add-to-cart
│   ├── notices/
│   │   ├── success.php       # Green Tailwind alert
│   │   └── error.php         # Red Tailwind alert
│   └── single-product.php    # Single product page override
├── functions.php             # Theme setup, enqueues, Flight init, WC asset management
├── header.php                # Site header with HTMX search, logo support, skip-to-content
├── footer.php                # Site footer
├── index.php                 # Main template (WordPress loop)
├── sidebar.php               # Widget area
└── style.css                 # Theme metadata
```

## Development

Run Tailwind CSS in watch mode for live-reloading styles:

```bash
npm run dev
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

### WordPress Template Hierarchy

Standard WordPress templates live in `template-parts/` and follow the WordPress loop convention. The `index.php` loads them via `get_template_part()`.

### Flight PHP Routes

Flight PHP handles HTMX fragment requests at `/htmx-api/*`. All mutating requests (POST, PUT, DELETE) are verified against the `storefront_zero_htmx` nonce via middleware in `app/routes.php`.

| Method | Route | Controller | Description |
|---|---|---|---|
| GET | `/search` | `ProductController::liveSearch()` | Live product search (rate-limited, cached 60s) |
| POST | `/cart/add` | `CartController::addToCart()` | Add product to cart |

### HTMX Fragments

HTMX attributes trigger requests to the Flight PHP routes. Each route returns a partial HTML response that HTMX swaps into the DOM — no full page reloads needed.

HTMX is loaded locally from `assets/js/vendor/htmx.min.js` (v1.9.10) to avoid CDN dependencies. The search results dropdown auto-toggles visibility via an `htmx:afterSwap` listener.

### Web Components

The theme uses web components for interactive widgets. Each component is self-registering via `customElements.define()` in its own file under `assets/js/web-components/`. Scripts are loaded as ES modules via a `script_loader_tag` filter.

Current components:
- `<mobile-drawer>` — mobile navigation with ARIA attributes, Escape key support, deterministic IDs, and focus management

### WooCommerce Templates

The theme provides full WooCommerce template overrides:

| Template | Purpose |
|---|---|
| `single-product.php` | Single product page with theme header/footer |
| `cart/cart.php` | Tailwind-styled cart with quantity inputs and remove buttons |
| `global/quantity-input.php` | Quantity stepper with +/- buttons |
| `loop/add-to-cart.php` | HTMX-powered add-to-cart button |
| `notices/success.php` | Green Tailwind alert box |
| `notices/error.php` | Red Tailwind alert box |
| `archive-product.php` | Product archive (uses theme header/footer) |

### Design System

Tailwind CSS is configured with design tokens in `tailwind.config.js`:

- **Brand colors**: `brand-50` through `brand-900` (blue palette)
- **Surface colors**: `surface` (white), `muted` (gray)
- **Font family**: Inter with system fallbacks

Component classes in `assets/css/input.css`:
- `.btn-primary` — branded button with hover, focus ring, and transitions
- `.card` — surface card with border, shadow, and padding

### Accessibility

All interactive components include:
- **Skip-to-content link** — visible on focus, placed after `wp_body_open()`
- **ARIA labels** — search input, spinner, Read more links with post titles
- **Keyboard navigation** — Escape key handlers, focus management in drawers
- **Translatable strings** — `_n()` for singular/plural, text domain `'storefront-zero'`
- **Semantic HTML** — `<nav>` landmarks instead of `role="menu"` / `role="menuitem"`

## Security

- **Nonce scoping**: HTMX requests use a dedicated `storefront_zero_htmx` nonce (not the default `wp_rest` nonce).
- **Middleware verification**: All mutating HTMX requests (POST, PUT, DELETE) pass through Flight middleware that verifies the nonce.
- **Input sanitization**: `$_SERVER['REQUEST_URI']` sanitized with `sanitize_text_field(wp_unslash())`.
- **Path traversal guard**: `basename()` applied to view names in `View::render()`.
- **Output escaping**: WordPress escaping functions (`esc_html()`, `esc_url()`, `esc_attr()`, `wp_kses_post()`) used throughout.
- **Content-Type headers**: All HTMX responses include `Content-Type: text/html; charset=utf-8`.
- **Rate limiting**: Search endpoint cached with 60s transient TTL.
- **Strict types**: `declare(strict_types=1)` on all PHP files.

## Performance

- **WC script dequeuing**: `select2`, `zoom`, `prettyPhoto`, and variation scripts are dequeued on non-product pages.
- **Transient caching**: Search results cached for 60 seconds via WordPress transients.
- **Local HTMX**: No CDN dependency, single request.
- **type="module"**: Web component scripts loaded as ES modules for modern browser optimization.

## Plugin Compatibility

### WP Rocket

To ensure HTMX functionality works correctly with WP Rocket caching:

1. **Exclude JS from Delay/Minification:**
   Go to WP Rocket → Settings → File Optimization → JavaScript Files
   - Add `htmx.min.js` to "Exclude JavaScript Files"
   - Add `app.js` to "Exclude JavaScript Files"

   These files must load immediately (not deferred) for HTMX to function properly.

2. **Fragment Caching:**
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
4. Run `composer install` before submitting.
5. Submit a pull request with a clear description of the change.

## License

GPL v2 or later — same as WordPress.
