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

## Contributing

1. Fork the repository.
2. Create a feature branch from `main`.
3. Make your changes following WordPress Coding Standards.
4. Run `composer install` before submitting.
5. Submit a pull request with a clear description of the change.

## License

GPL v2 or later — same as WordPress.
