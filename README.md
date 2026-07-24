# Storefront Zero

A minimal, performance-first WordPress/WooCommerce theme.

## Features

- **Flight PHP routing** — lightweight HTTP layer for HTMX fragment endpoints
- **HTMX dynamic fragments** — server-rendered partial updates without full page reloads
- **MVC architecture** — controllers in `app/Controllers/`, views in `app/Views/`
- **Web Components** — encapsulated interactive UI widgets with ARIA support
- **Tailwind CSS** — utility-first styling with build pipeline
- **WooCommerce integration** — full storefront, cart, checkout, and account support
- **Local HTMX** — bundled vendor script, no CDN dependency
- **Nonce-scoped security** — dedicated `storefront_zero_htmx` nonce for HTMX requests
- **Lighthouse 95+ target** — performance-first architecture and defaults

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
│   │   ├── View.php          # Static view renderer
│   │   ├── cart-error.php
│   │   ├── mini-cart.php
│   │   └── search-results.php
│   └── routes.php            # Flight route definitions + nonce middleware
├── assets/
│   ├── css/
│   │   ├── input.css         # Tailwind directives
│   │   └── main.css          # Compiled output
│   └── js/
│       ├── app.js            # HTMX config, a11y handlers
│       ├── vendor/
│       │   └── htmx.min.js   # Local HTMX v1.9.10
│       └── web-components/
│           └── mobile-drawer.js
├── template-parts/           # WordPress loop templates
│   ├── content.php           # Blog post display
│   ├── content-none.php      # No posts found
│   └── content-page.php      # Page display
├── woocommerce/              # WooCommerce template overrides
├── functions.php             # Theme setup, enqueues, Flight init
├── header.php                # Site header with HTMX search
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
| GET | `/search` | `ProductController::liveSearch()` | Live product search |
| POST | `/cart/add` | `CartController::addToCart()` | Add product to cart |

### HTMX Fragments

HTMX attributes trigger requests to the Flight PHP routes. Each route returns a partial HTML response that HTMX swaps into the DOM — no full page reloads needed.

HTMX is loaded locally from `assets/js/vendor/htmx.min.js` (v1.9.10) to avoid CDN dependencies.

### Web Components

The theme uses web components for interactive widgets. Each component is self-registering via `customElements.define()` in its own file under `assets/js/web-components/`.

Current components:
- `<mobile-drawer>` — mobile navigation with ARIA attributes, Escape key support, and focus management

Web components are registered explicitly in `functions.php` (no `glob()` scan at runtime).

### Accessibility

All interactive components include:
- ARIA attributes (`aria-expanded`, `aria-controls`, `role`)
- Keyboard navigation (`Escape` key handlers)
- Focus management (focus trapping in drawers, focus return on dismiss)
- Outside-click dismiss for dropdowns

## Security

- **Nonce scoping**: HTMX requests use a dedicated `storefront_zero_htmx` nonce (not the default `wp_rest` nonce).
- **Middleware verification**: All mutating HTMX requests (POST, PUT, DELETE) pass through Flight middleware that verifies the nonce.
- **Input validation**: Server-side sanitization via `sanitize_text_field()`, `absint()`, and `wp_unslash()`.
- **Output escaping**: WordPress escaping functions (`esc_html()`, `esc_url()`, `esc_attr()`, `wp_kses_post()`) used throughout.

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
