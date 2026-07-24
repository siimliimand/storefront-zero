# Storefront Zero

A minimal, performance-first WordPress/WooCommerce theme.

## Features

- **Flight PHP routing** — lightweight HTTP layer for HTMX fragment endpoints
- **HTMX dynamic fragments** — server-rendered partial updates without full page reloads
- **Web Components** — encapsulated interactive UI widgets
- **Tailwind CSS** — utility-first styling with build pipeline
- **WooCommerce integration** — full storefront, cart, checkout, and account support
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

## Development

Run Tailwind CSS in watch mode for live-reloading styles:

```bash
npm run dev
```

### Flight PHP Routes

Flight PHP handles HTMX fragment requests at `/htmx-api/*`. Controllers in `src/` return HTML fragments consumed by HTMX on the frontend.

### HTMX Fragments

HTMX attributes trigger requests to the Flight PHP routes. Each route returns a partial HTML response that HTMX swaps into the DOM — no full page reloads needed.

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
