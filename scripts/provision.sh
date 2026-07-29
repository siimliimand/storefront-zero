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

if ! command -v wp >/dev/null 2>&1; then
  echo "==> Installing WP-CLI..."
  curl -sSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /tmp/wp-cli.phar
  chmod +x /tmp/wp-cli.phar
  mv /tmp/wp-cli.phar /usr/local/bin/wp
fi

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

mkdir -p "$WP_PATH/wp-content/plugins" "$WP_PATH/wp-content/upgrade" "$WP_PATH/wp-content/uploads" 2>/dev/null || true
chmod -R 777 "$WP_PATH/wp-content" 2>/dev/null || true

echo "==> Installing WooCommerce (v9.6.0)..."
wp plugin install woocommerce --version=9.6.0 --activate --path="$WP_PATH" --allow-root

echo "==> Activating Storefront Zero..."
wp theme activate storefront-zero --path="$WP_PATH" --allow-root

echo "==> Installing theme Composer deps..."
cd "$THEME_PATH"
composer install --no-interaction --no-progress --prefer-dist 2>/dev/null || true

echo "==> Setting permalinks..."
wp rewrite structure '/%postname%/' --path="$WP_PATH" --allow-root
wp rewrite flush --path="$WP_PATH" --allow-root

echo "==> Creating sample products..."

# Create products that match E2E test search queries.
create_product() {
  local name="$1"
  local price="$2"

  PROD_ID=$(wp wc product create \
    --path="$WP_PATH" \
    --user=admin \
    --name="$name" \
    --regular_price="$price" \
    --status=publish \
    --type=simple \
    --porcelain \
    --allow-root 2>/dev/null) || true

  if [ -z "$PROD_ID" ]; then
    PROD_ID=$(wp post create \
      --path="$WP_PATH" \
      --post_type=product \
      --post_title="$name" \
      --post_status=publish \
      --porcelain \
      --allow-root)
    wp post meta update "$PROD_ID" _price "$price" --path="$WP_PATH" --allow-root 2>/dev/null || true
    wp post meta update "$PROD_ID" _regular_price "$price" --path="$WP_PATH" --allow-root 2>/dev/null || true
    wp post meta update "$PROD_ID" _stock_status instock --path="$WP_PATH" --allow-root 2>/dev/null || true
  fi
}

create_product "Blue Cotton Shirt" "39.99"
create_product "Graphic T-Shirt" "24.99"
create_product "Baseball Cap" "19.99"
create_product "Running Shoes" "89.99"

# Create a Cart page with the classic WooCommerce cart shortcode
CART_PAGE_ID=$(wp post create \
  --path="$WP_PATH" \
  --post_type=page \
  --post_title="Cart" \
  --post_status=publish \
  --post_content="[woocommerce_cart]" \
  --porcelain \
  --allow-root 2>/dev/null || true)
wp option update woocommerce_cart_page_id "$CART_PAGE_ID" \
  --path="$WP_PATH" --allow-root 2>/dev/null || true

echo "==> Provisioning complete!"
