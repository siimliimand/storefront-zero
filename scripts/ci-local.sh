#!/usr/bin/env bash
# Local CI: mirrors GitHub Actions pipeline exactly
# Usage: ./scripts/ci-local.sh
set -euo pipefail

THEME_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$THEME_DIR"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

pass() { echo -e "${GREEN}✓ $1${NC}"; }
fail() { echo -e "${RED}✗ $1${NC}"; }
step() { echo -e "\n${YELLOW}━━━ $1 ━━━${NC}"; }

WP_PATH="/var/www/html"

cleanup() {
  echo ""
  echo "Tearing down Docker containers..."
  docker compose down -v 2>/dev/null || true
}
trap cleanup EXIT

docker compose down -v 2>/dev/null || true

# ── 1. PHP Checks ──────────────────────────────────────────
step "1/4 PHP Checks (PHPStan Level 8 + Pest)"
docker compose run --rm php-checks 2>&1 | tail -15
if [ "${PIPESTATUS[0]:-$?}" -eq 0 ]; then
  pass "PHPStan: 0 errors, Pest: all passed"
else
  fail "PHP checks failed"
  exit 1
fi

# ── 2. JS Build ─────────────────────────────────────────────
step "2/4 JS Build"
if npm run build 2>/dev/null; then
  pass "CSS + JS bundles generated"
else
  fail "Build failed"
  exit 1
fi

# ── 3. Start WordPress + Provision ──────────────────────────
step "3/4 Provision WordPress + WooCommerce"
docker compose up -d wp

echo "Waiting for WordPress..."
for i in $(seq 1 30); do
  if curl -sf http://localhost:8080/ > /dev/null 2>&1; then
    break
  fi
  if [ "$i" -eq 30 ]; then
    fail "WordPress failed to start"
    docker compose logs wp
    exit 1
  fi
  sleep 2
done

# Install WP-CLI inside the container and provision
echo "Installing WP-CLI + provisioning..."
docker compose exec -T wp bash -c "
  curl -sO https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar &&
  chmod +x wp-cli.phar && mv wp-cli.phar /usr/local/bin/wp &&

  wp config create \
    --path=$WP_PATH \
    --dbhost=mysql:3306 \
    --dbname=wordpress \
    --dbuser=root \
    --dbpass=root \
    --allow-root \
    --skip-check 2>/dev/null || true &&

  wp core install \
    --path=$WP_PATH \
    --url=http://localhost:8080 \
    --title='Storefront Zero CI' \
    --admin_user=admin \
    --admin_password=admin \
    --admin_email=admin@example.com \
    --allow-root &&

  wp plugin install woocommerce --version=9.6.0 --activate \
    --path=$WP_PATH --allow-root &&

  wp theme activate storefront-zero \
    --path=$WP_PATH --allow-root &&

  wp rewrite structure '/%postname%/' \
    --path=$WP_PATH --allow-root &&
  wp rewrite flush \
    --path=$WP_PATH --allow-root &&

  wp wc product create \
    --path=$WP_PATH \
    --user=admin \
    --name='Test Product' \
    --regular_price='29.99' \
    --status=publish \
    --type=simple \
    --allow-root 2>/dev/null || \
  wp post create \
    --path=$WP_PATH \
    --post_type=product \
    --post_title='Test Product' \
    --post_status=publish \
    --porcelain \
    --allow-root &&

  # Create a Cart page with the classic WooCommerce cart shortcode.
  CART_PAGE_ID=\$(wp post create \
    --path=$WP_PATH \
    --post_type=page \
    --post_title='Cart' \
    --post_status=publish \
    --post_content='[woocommerce_cart]' \
    --porcelain \
    --allow-root) &&
  wp option update woocommerce_cart_page_id "\$CART_PAGE_ID" \
    --path=$WP_PATH --allow-root
"

# Post-provisioning smoke check: verify the storefront actually renders
echo "Verifying storefront renders correctly..."
SMOKE_HTML=$(curl -sf http://localhost:8080/ 2>/dev/null || true)
if echo "$SMOKE_HTML" | grep -qi 'storefront-zero\|woocommerce\|wp-content'; then
  pass "WordPress running at http://localhost:8080 (storefront verified)"
else
  fail "Post-provisioning smoke check failed — site is not rendering the storefront"
  echo "--- Page snippet (first 60 lines) ---"
  echo "$SMOKE_HTML" | head -60
  echo "--- End snippet ---"
  exit 1
fi

# ── 4. E2E Tests ────────────────────────────────────────────
step "4/4 E2E Tests (Playwright)"
PLAYWRIGHT_LOG=$(mktemp)
if CI_BASE_URL="http://localhost:8080" npx playwright test 2>&1 | tee "$PLAYWRIGHT_LOG"; then
  pass "E2E: all tests passed"
else
  fail "E2E tests failed — last 40 lines of output:"
  tail -40 "$PLAYWRIGHT_LOG"
  rm -f "$PLAYWRIGHT_LOG"
  exit 1
fi
rm -f "$PLAYWRIGHT_LOG"

echo ""
step "All checks passed"
