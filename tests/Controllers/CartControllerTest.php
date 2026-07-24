<?php

declare(strict_types=1);

/**
 * CartController tests.
 *
 * Validates the CartController class source for correct WooCommerce
 * cart operations, input sanitization, and error handling patterns.
 */

/*
|--------------------------------------------------------------------------
| Class structure
|--------------------------------------------------------------------------
*/

it('declares strict types', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/CartController.php');

    expect($source)->toContain('declare(strict_types=1)');
});

it('namespaces under ThemeApp\\Controllers', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/CartController.php');

    expect($source)->toContain('namespace ThemeApp\\Controllers');
});

/*
|--------------------------------------------------------------------------
| addToCart method
|--------------------------------------------------------------------------
*/

it('sanitizes product_id with absint', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/CartController.php');

    expect($source)->toContain('absint( $_POST[\'product_id\'] )');
});

it('sanitizes quantity with absint', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/CartController.php');

    expect($source)->toContain('absint( $_POST[\'quantity\'] )');
});

it('validates product_id and product existence before adding', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/CartController.php');

    expect($source)->toContain('empty( $product_id ) || ! wc_get_product( $product_id )');
});

it('returns cart-error view on invalid product', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/CartController.php');

    expect($source)->toContain("View::render( 'cart-error'");
});

it('sets HX-Trigger header on successful add', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/CartController.php');

    expect($source)->toContain('HX-Trigger: cartUpdated');
});

it('returns 400 status on failed add-to-cart', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/CartController.php');

    expect($source)->toContain('status_header( 400 )');
});

/*
|--------------------------------------------------------------------------
| renderMiniCart method
|--------------------------------------------------------------------------
*/

it('renders mini-cart-fragment view', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/CartController.php');

    expect($source)->toContain("View::render( 'mini-cart-fragment' )");
});

/*
|--------------------------------------------------------------------------
| updateQuantity method
|--------------------------------------------------------------------------
*/

it('sanitizes cart_item_key with sanitize_text_field', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/CartController.php');

    expect($source)->toContain('sanitize_text_field( wp_unslash( $_POST[\'cart_item_key\'] ) )');
});

it('returns error comment for empty cart_item_key', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/CartController.php');

    expect($source)->toContain('<!-- Invalid cart item key -->');
});

it('removes item when quantity is zero', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/CartController.php');

    expect($source)->toContain('WC()->cart->remove_cart_item( $cart_item_key )');
});

it('sets quantity via WC cart when quantity is non-zero', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/CartController.php');

    expect($source)->toContain('WC()->cart->set_quantity( $cart_item_key, $quantity )');
});

/*
|--------------------------------------------------------------------------
| removeItem method
|--------------------------------------------------------------------------
*/

it('uses POST cart_item_key for remove operation', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/CartController.php');

    // removeItem reads from $_POST, not $_DELETE — HTMX sends body with DELETE.
    expect($source)->toContain('sanitize_text_field( wp_unslash( $_POST[\'cart_item_key\'] ) )');
});

it('returns error comment for empty key on remove', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/CartController.php');

    // The removeItem method should handle empty keys gracefully.
    $lines = explode("\n", $source);
    $inRemoveItem = false;
    $found = false;

    foreach ($lines as $line) {
        if (str_contains($line, 'public static function removeItem()')) {
            $inRemoveItem = true;
        }
        if ($inRemoveItem && str_contains($line, '<!-- Invalid cart item key -->')) {
            $found = true;
            break;
        }
        if ($inRemoveItem && str_contains($line, 'public static function') && !str_contains($line, 'removeItem')) {
            break;
        }
    }

    expect($found)->toBeTrue();
});
