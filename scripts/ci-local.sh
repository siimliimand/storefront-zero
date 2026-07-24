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
docker compose exec -T wp bash -c '
  curl -sO https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar &&
  chmod +x wp-cli.phar && mv wp-cli.phar /usr/local/bin/wp &&

  wp config create --dbhost=mysql:3306 --dbname=wordpress --dbuser=root --dbpass=root --allow-root --skip-check 2>/dev/null || true &&
  wp core install --url=http://localhost:8080 --title="Storefront Zero CI" \
    --admin_user=admin --admin_password=admin --admin_email=admin@example.com --allow-root &&

  wp plugin install woocommerce --activate --allow-root &&

  wp theme activate storefront-zero --allow-root &&

  wp rewrite structure "/%postname%/" --allow-root &&
  wp rewrite flush --allow-root &&

  wp wc product create --user=admin --name="Test Product" --regular_price="29.99" \
    --status=publish --type=simple --allow-root 2>/dev/null || \
  wp post_create --post_type=product --post_title="Test Product" --post_status=publish --porcelain --allow-root
'

if curl -sf http://localhost:8080/ > /dev/null 2>&1; then
  pass "WordPress running at http://localhost:8080"
else
  fail "WordPress not responding"
  docker compose logs wp
  exit 1
fi

# ── 4. E2E Tests ────────────────────────────────────────────
step "4/4 E2E Tests (Playwright)"
if CI_BASE_URL="http://localhost:8080" npx playwright test 2>&1 | tail -25; then
  pass "E2E: all tests passed"
else
  fail "E2E tests failed"
  exit 1
fi

echo ""
step "All checks passed"
