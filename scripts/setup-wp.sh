#!/usr/bin/env bash
# CI WordPress provisioning script
# Sets up WordPress + WooCommerce + Storefront Zero theme for E2E testing
set -euo pipefail

WP_DIR="${WP_DIR:-/tmp/wordpress}"
WP_PORT="${WP_PORT:-8080}"
WP_URL="http://localhost:${WP_PORT}"
DB_NAME="${DB_NAME:-wordpress}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-root}"
DB_HOST="${DB_HOST:-127.0.0.1}"

echo "==> Setting up WordPress at ${WP_DIR}"

# Download WordPress
mkdir -p "$WP_DIR"
wp core download --path="$WP_DIR" --allow-root 2>/dev/null || \
  curl -sL https://wordpress.org/latest.tar.gz | tar xz -C "$(dirname "$WP_DIR")" --strip-components=1

# Create wp-config.php
wp config create \
  --path="$WP_DIR" \
  --dbhost="$DB_HOST" \
  --dbname="$DB_NAME" \
  --dbuser="$DB_USER" \
  --dbpass="$DB_PASS" \
  --allow-root \
  --skip-check 2>/dev/null || true

# Install WordPress
wp core install \
  --path="$WP_DIR" \
  --url="$WP_URL" \
  --title="Storefront Zero E2E" \
  --admin_user=admin \
  --admin_password=admin \
  --admin_email=admin@example.com \
  --allow-root 2>/dev/null || true

# Install WooCommerce
wp plugin install woocommerce --activate --path="$WP_DIR" --allow-root 2>/dev/null || true

# Copy the theme into wp-content/themes/
THEME_DIR="$(cd "$(dirname "$0")/.." && pwd)"
rm -rf "$WP_DIR/wp-content/themes/storefront-zero"
cp -r "$THEME_DIR" "$WP_DIR/wp-content/themes/storefront-zero"

# Install theme Composer dependencies (vendor/ is gitignored)
cd "$WP_DIR/wp-content/themes/storefront-zero"
composer install --no-interaction --no-progress --prefer-dist 2>/dev/null || true
cd - > /dev/null

# Activate the theme
wp theme activate storefront-zero --path="$WP_DIR" --allow-root 2>/dev/null || true

# Flush rewrite rules
wp rewrite flush --path="$WP_DIR" --allow-root 2>/dev/null || true

# Create a sample product for E2E tests
wp wc product create \
  --path="$WP_DIR" \
  --user=admin \
  --name="Test Product" \
  --regular_price="29.99" \
  --status=publish \
  --type=simple \
  --allow-root 2>/dev/null || \
wp post create \
  --path="$WP_DIR" \
  --allow-root \
  --post_type=product \
  --post_title="Test Product" \
  --post_status=publish \
  --porcelain

# Create shop page if WooCommerce created it
wp option get woocommerce_shop_page_id --path="$WP_DIR" --allow-root 2>/dev/null || true

# Set permalink structure
wp rewrite structure '/%postname%/' --path="$WP_DIR" --allow-root 2>/dev/null || true

echo "==> Starting PHP built-in server on port ${WP_PORT}"
cd "$WP_DIR"
php -S "0.0.0.0:${WP_PORT}" -t . > /tmp/wp-server.log 2>&1 &
WP_PID=$!
echo "WordPress PID: ${WP_PID}"

# Wait for server to be ready
echo "==> Waiting for WordPress to be ready..."
for i in $(seq 1 30); do
  if curl -sf "http://localhost:${WP_PORT}/" > /dev/null 2>&1; then
    echo "==> WordPress is ready at ${WP_URL}"
    exit 0
  fi
  sleep 1
done

echo "==> ERROR: WordPress failed to start"
cat /tmp/wp-server.log
exit 1
