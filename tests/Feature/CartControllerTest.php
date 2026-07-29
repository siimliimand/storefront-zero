<?php

declare(strict_types=1);

use ThemeApp\Controllers\CartController;

// View lives at app/View.php — PSR-4 maps ThemeApp\ to app/.
// The namespace-file mismatch means Composer can't autoload it — require manually.
require_once __DIR__ . '/../../app/View.php';

use ThemeApp\View;

/**
 * View test double that records render calls.
 *
 * Extends View to satisfy the CartController constructor type-hint.
 * Overrides the static render method to capture calls instead of
 * including template files.
 */
class FakeView extends View
{
    /** @var list<array{view: string, data: array<string, mixed>}> */
    public static array $calls = [];

    public static function reset(): void
    {
        self::$calls = [];
    }

    public static function render(string $view, array $data = []): void
    {
        self::$calls[] = ['view' => $view, 'data' => $data];
    }
}

beforeEach(function () {
    FakeView::reset();
    \Flight::request()->data->setData([]);
    ob_start();
});

afterEach(function () {
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
});

/*
|--------------------------------------------------------------------------
| addToCart — success path
|--------------------------------------------------------------------------
*/

it('adds valid product to cart and renders mini-cart fragment', function () {
    \Flight::request()->data->setData(['product_id' => 42, 'quantity' => 2]);

    $product = new class { public function get_name(): string { return 'Test Product'; } };

    $cart = $this->createMock(\WC_Cart::class);
    $cart->expects($this->once())
        ->method('add_to_cart')
        ->with(42, 2, 0, [])
        ->willReturn('item_key_abc');
    $cart->method('get_cart_item')
        ->with('item_key_abc')
        ->willReturn(['data' => $product]);

    $view = new FakeView();
    $controller = new CartController($cart, $view);
    $controller->addToCart();

    expect($view::$calls)->not->toBeEmpty();
    expect($view::$calls[0]['view'])->toBe('mini-cart-fragment');
});

/*
|--------------------------------------------------------------------------
| addToCart — invalid product
|--------------------------------------------------------------------------
*/

it('returns error view when product_id is missing', function () {
    \Flight::request()->data->setData([]);

    $cart = $this->createMock(\WC_Cart::class);
    $cart->expects($this->never())->method('add_to_cart');

    $view = new FakeView();
    $controller = new CartController($cart, $view);
    $controller->addToCart();

    expect($view::$calls)->not->toBeEmpty();
    expect($view::$calls[0]['view'])->toBe('cart-error');
});

it('returns error view when product does not exist', function () {
    \Flight::request()->data->setData(['product_id' => 'abc']);

    $cart = $this->createMock(\WC_Cart::class);
    $cart->expects($this->never())->method('add_to_cart');

    $view = new FakeView();
    $controller = new CartController($cart, $view);
    $controller->addToCart();

    expect($view::$calls)->not->toBeEmpty();
    expect($view::$calls[0]['view'])->toBe('cart-error');
});

/*
|--------------------------------------------------------------------------
| addToCart — cart add fails
|--------------------------------------------------------------------------
*/

it('returns error view when cart rejects the product', function () {
    \Flight::request()->data->setData(['product_id' => 42, 'quantity' => 1]);

    $cart = $this->createMock(\WC_Cart::class);
    $cart->method('add_to_cart')->willReturn(false);

    $view = new FakeView();
    $controller = new CartController($cart, $view);
    $controller->addToCart();

    expect($view::$calls)->not->toBeEmpty();
    expect($view::$calls[0]['view'])->toBe('cart-error');
});

/*
|--------------------------------------------------------------------------
| addToCart — variation data forwarded
|--------------------------------------------------------------------------
*/

it('forwards variation attributes for variable products', function () {
    \Flight::request()->data->setData([
        'product_id'      => 10,
        'quantity'        => 1,
        'variation_id'    => 20,
        'attribute_color' => 'red',
        'attribute_size'  => 'large',
    ]);

    $product = new class { public function get_name(): string { return 'Variable Product'; } };

    $cart = $this->createMock(\WC_Cart::class);
    $cart->expects($this->once())
        ->method('add_to_cart')
        ->with(
            10,
            1,
            20,
            ['attribute_color' => 'red', 'attribute_size' => 'large']
        )
        ->willReturn('item_key_xyz');
    $cart->method('get_cart_item')
        ->with('item_key_xyz')
        ->willReturn(['data' => $product]);

    $view = new FakeView();
    $controller = new CartController($cart, $view);
    $controller->addToCart();

    expect($view::$calls[0]['view'])->toBe('mini-cart-fragment');
});

/*
|--------------------------------------------------------------------------
| updateQuantity — success
|--------------------------------------------------------------------------
*/

it('updates cart item quantity and renders mini-cart', function () {
    \Flight::request()->data->setData(['cart_item_key' => 'abc123', 'quantity' => 5]);

    $cart = $this->createMock(\WC_Cart::class);
    $cart->expects($this->once())
        ->method('set_quantity')
        ->with('abc123', 5)
        ->willReturn(true);

    $view = new FakeView();
    $controller = new CartController($cart, $view);
    $controller->updateQuantity();

    expect($view::$calls)->not->toBeEmpty();
    expect($view::$calls[0]['view'])->toBe('mini-cart-fragment');
});

/*
|--------------------------------------------------------------------------
| updateQuantity — zero removes item
|--------------------------------------------------------------------------
*/

it('removes item when quantity is set to zero', function () {
    \Flight::request()->data->setData(['cart_item_key' => 'abc123', 'quantity' => 0]);

    $cart = $this->createMock(\WC_Cart::class);
    $cart->expects($this->once())
        ->method('remove_cart_item')
        ->with('abc123')
        ->willReturn(true);
    $cart->expects($this->never())->method('set_quantity');

    $view = new FakeView();
    $controller = new CartController($cart, $view);
    $controller->updateQuantity();

    expect($view::$calls[0]['view'])->toBe('mini-cart-fragment');
});

/*
|--------------------------------------------------------------------------
| updateQuantity — empty key
|--------------------------------------------------------------------------
*/

it('returns error output for empty cart_item_key on update', function () {
    \Flight::request()->data->setData(['cart_item_key' => '', 'quantity' => 2]);

    $cart = $this->createMock(\WC_Cart::class);
    $cart->expects($this->never())->method('set_quantity');
    $cart->expects($this->never())->method('remove_cart_item');

    $view = new FakeView();
    $controller = new CartController($cart, $view);
    $controller->updateQuantity();

    expect($view::$calls)->toBeEmpty();
});

/*
|--------------------------------------------------------------------------
| removeItem — success
|--------------------------------------------------------------------------
*/

it('removes cart item and renders mini-cart', function () {
    \Flight::request()->data->setData(['cart_item_key' => 'xyz789']);

    $cart = $this->createMock(\WC_Cart::class);
    $cart->expects($this->once())
        ->method('remove_cart_item')
        ->with('xyz789')
        ->willReturn(true);

    $view = new FakeView();
    $controller = new CartController($cart, $view);
    $controller->removeItem();

    expect($view::$calls)->not->toBeEmpty();
    expect($view::$calls[0]['view'])->toBe('mini-cart-fragment');
});

/*
|--------------------------------------------------------------------------
| removeItem — empty key
|--------------------------------------------------------------------------
*/

it('returns error output for empty cart_item_key on remove', function () {
    \Flight::request()->data->setData(['cart_item_key' => '']);

    $cart = $this->createMock(\WC_Cart::class);
    $cart->expects($this->never())->method('remove_cart_item');

    $view = new FakeView();
    $controller = new CartController($cart, $view);
    $controller->removeItem();

    expect($view::$calls)->toBeEmpty();
});

/*
|--------------------------------------------------------------------------
| renderMiniCart
|--------------------------------------------------------------------------
*/

it('renders the mini-cart-fragment view', function () {
    $cart = $this->createMock(\WC_Cart::class);
    $view = new FakeView();
    $controller = new CartController($cart, $view);

    $controller->renderMiniCart();

    expect($view::$calls)->toHaveCount(1);
    expect($view::$calls[0]['view'])->toBe('mini-cart-fragment');
});
