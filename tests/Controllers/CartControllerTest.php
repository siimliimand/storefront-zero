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
| Constructor injection
|--------------------------------------------------------------------------
*/

it('has constructor with WC_Cart and View parameters', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/CartController.php');

    expect($source)->toContain('public function __construct( \\WC_Cart $cart, ViewInterface $view )');
});

it('assigns cart and view to private properties', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/CartController.php');

    expect($source)->toContain('$this->cart = $cart');
    expect($source)->toContain('$this->view = $view');
});

/*
|--------------------------------------------------------------------------
| addToCart method
|--------------------------------------------------------------------------
*/

it('sanitizes product_id with absint', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/CartController.php');

    expect($source)->toContain('absint( $data[\'product_id\'] )');
});

it('sanitizes quantity with absint', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/CartController.php');

    expect($source)->toContain('absint( $data[\'quantity\'] )');
});

it('validates product_id and product existence before adding', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/CartController.php');

    expect($source)->toContain('empty( $product_id ) || ! wc_get_product( $product_id )');
});

it('returns cart-error view on invalid product', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/CartController.php');

    expect($source)->toContain('$this->view->render( \'cart-error\'');
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

    expect($source)->toContain('$this->view->render( \'mini-cart-fragment\'');
});

/*
|--------------------------------------------------------------------------
| updateQuantity method
|--------------------------------------------------------------------------
*/

it('sanitizes cart_item_key with sanitize_text_field', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/CartController.php');

    expect($source)->toContain('sanitize_text_field( wp_unslash( Flight::request()->data[\'cart_item_key\'] ) )');
});

it('returns error comment for empty cart_item_key', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/CartController.php');

    expect($source)->toContain('<!-- Invalid cart item key -->');
});

it('removes item when quantity is zero', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/CartController.php');

    expect($source)->toContain('$this->cart->remove_cart_item( $cart_item_key )');
});

it('sets quantity via WC cart when quantity is non-zero', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/CartController.php');

    expect($source)->toContain('$this->cart->set_quantity( $cart_item_key, $quantity )');
});

/*
|--------------------------------------------------------------------------
| removeItem method
|--------------------------------------------------------------------------
*/

it('uses Flight request data for remove operation', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/CartController.php');

    // removeItem reads from Flight::request()->data, not $_POST.
    expect($source)->toContain('sanitize_text_field( wp_unslash( Flight::request()->data[\'cart_item_key\'] ) )');
});

it('returns error comment for empty key on remove', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/CartController.php');

    // The removeItem method should handle empty keys gracefully.
    $lines = explode("\n", $source);
    $inRemoveItem = false;
    $found = false;

    foreach ($lines as $line) {
        if (str_contains($line, 'public function removeItem()')) {
            $inRemoveItem = true;
        }
        if ($inRemoveItem && str_contains($line, '<!-- Invalid cart item key -->')) {
            $found = true;
            break;
        }
        if ($inRemoveItem && str_contains($line, 'public function') && !str_contains($line, 'removeItem')) {
            break;
        }
    }

    expect($found)->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Instance methods (no static)
|--------------------------------------------------------------------------
*/

it('has no static methods', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/CartController.php');

    expect($source)->not->toContain('public static function');
});
