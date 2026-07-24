#!/bin/sh
# WordPress + WooCommerce provisioning (runs inside Docker)
set -e

WP_PATH="/var/www/html"
THEME_PATH="$WP_PATH/wp-content/themes/storefront-zero"

echo "==> Waiting for WordPress files..."
until [ -f "$WP_PATH/wp-includes/version.php" ]; do
  sleep 2
done
sleep 5

echo "==> Installing WP-CLI..."
curl -sO https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
mv wp-cli.phar /usr/local/bin/wp

echo "==> Configuring WordPress..."
wp config create \
  --path="$WP_PATH" \
  --dbhost="$WORDPRESS_DB_HOST" \
  --dbname="$WORDPRESS_DB_NAME" \
  --dbuser="$WORDPRESS_DB_USER" \
  --dbpass="$WORDPRESS_DB_PASSWORD" \
  --allow-root \
  --skip-check 2>/dev/null || true

echo "==> Installing WordPress..."
wp core install \
  --path="$WP_PATH" \
  --url="http://localhost:8080" \
  --title="Storefront Zero CI" \
  --admin_user=admin \
  --admin_password=admin \
  --admin_email=admin@example.com \
  --allow-root

echo "==> Installing WooCommerce..."
wp plugin install woocommerce --activate --path="$WP_PATH" --allow-root

echo "==> Activating Storefront Zero..."
wp theme activate storefront-zero --path="$WP_PATH" --allow-root

echo "==> Installing theme Composer deps..."
cd "$THEME_PATH"
composer install --no-interaction --no-progress --prefer-dist 2>/dev/null || true

echo "==> Setting permalinks..."
wp rewrite structure '/%postname%/' --path="$WP_PATH" --allow-root
wp rewrite flush --path="$WP_PATH" --allow-root

echo "==> Creating sample product..."
wp wc product create \
  --path="$WP_PATH" \
  --user=admin \
  --name="Test Product" \
  --regular_price="29.99" \
  --status=publish \
  --type=simple \
  --allow-root 2>/dev/null || \
wp post create \
  --path="$WP_PATH" \
  --allow-root \
  --post_type=product \
  --post_title="Test Product" \
  --post_status=publish \
  --porcelain

echo "==> Provisioning complete!"
