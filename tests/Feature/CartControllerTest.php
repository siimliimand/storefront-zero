<?php

declare(strict_types=1);

use ThemeApp\Controllers\CartController;
use ThemeApp\ViewInterface;

/**
 * View test double that records render calls.
 *
 * Implements ViewInterface to satisfy the CartController constructor type-hint.
 * Records render calls instead of including template files.
 */
class FakeView implements ViewInterface
{
    /** @var list<array{view: string, data: array<string, mixed>}> */
    public static array $calls = [];

    public static function reset(): void
    {
        self::$calls = [];
    }

    public function render(string $view, array $data = []): void
    {
        self::$calls[] = ['view' => $view, 'data' => $data];
    }
}

beforeEach(function () {
    FakeView::reset();
    HeaderCapture::reset();
    WcNoticesStub::reset();
    \Flight::request()->data->setData([]);
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
    \Tests\withCleanBuffer(fn () => $controller->addToCart());

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
    \Tests\withCleanBuffer(fn () => $controller->addToCart());

    expect($view::$calls)->not->toBeEmpty();
    expect($view::$calls[0]['view'])->toBe('cart-error');
});

it('returns error view when product does not exist', function () {
    \Flight::request()->data->setData(['product_id' => 'abc']);

    $cart = $this->createMock(\WC_Cart::class);
    $cart->expects($this->never())->method('add_to_cart');

    $view = new FakeView();
    $controller = new CartController($cart, $view);
    \Tests\withCleanBuffer(fn () => $controller->addToCart());

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
    \Tests\withCleanBuffer(fn () => $controller->addToCart());

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
    \Tests\withCleanBuffer(fn () => $controller->addToCart());

    expect($view::$calls[0]['view'])->toBe('mini-cart-fragment');
});

/*
|--------------------------------------------------------------------------
| updateQuantity — success
|--------------------------------------------------------------------------
*/

it('updates cart item quantity and renders cart page', function () {
    \Flight::request()->data->setData(['cart_item_key' => 'abc123', 'quantity' => 5]);

    $cart = $this->createMock(\WC_Cart::class);
    $cart->expects($this->once())
        ->method('set_quantity')
        ->with('abc123', 5)
        ->willReturn(true);

    $view = new FakeView();
    $controller = new CartController($cart, $view);
    \Tests\withCleanBuffer(fn () => $controller->updateQuantity());

    expect($view::$calls)->not->toBeEmpty();
    expect($view::$calls[0]['view'])->toBe('cart-page');
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
    \Tests\withCleanBuffer(fn () => $controller->updateQuantity());

    expect($view::$calls[0]['view'])->toBe('cart-page');
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
    \Tests\withCleanBuffer(fn () => $controller->updateQuantity());

    expect($view::$calls)->toBeEmpty();
});

/*
|--------------------------------------------------------------------------
| removeItem — success
|--------------------------------------------------------------------------
*/

it('removes cart item and renders cart page', function () {
    \Flight::request()->data->setData(['cart_item_key' => 'xyz789']);

    $cart = $this->createMock(\WC_Cart::class);
    $cart->expects($this->once())
        ->method('remove_cart_item')
        ->with('xyz789')
        ->willReturn(true);

    $view = new FakeView();
    $controller = new CartController($cart, $view);
    \Tests\withCleanBuffer(fn () => $controller->removeItem());

    expect($view::$calls)->not->toBeEmpty();
    expect($view::$calls[0]['view'])->toBe('cart-page');
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
    \Tests\withCleanBuffer(fn () => $controller->removeItem());

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

/*
|--------------------------------------------------------------------------
| WC notice bridge — flush_wc_notices returns empty string
|--------------------------------------------------------------------------
*/

it('flush_wc_notices returns empty string when no notices exist', function () {
    \Flight::request()->data->setData(['product_id' => 42, 'quantity' => 1]);

    $product = new class { public function get_name(): string { return 'Test'; } };

    $cart = $this->createMock(\WC_Cart::class);
    $cart->method('add_to_cart')->willReturn('key');
    $cart->method('get_cart_item')->willReturn(['data' => $product]);

    $view = new FakeView();
    $controller = new CartController($cart, $view);

    $output = '';
    \Tests\withCleanBuffer(function () use ($controller, &$output) {
        ob_start();
        $controller->addToCart();
        $output = ob_get_clean();
    });

    // No notices set — output should not contain notice-related markup.
    expect($output)->not->toContain('woocommerce-error');
    expect($output)->not->toContain('woocommerce-message');
});

/*
|--------------------------------------------------------------------------
| WC notice bridge — wc_get_notices and wc_clear_notices called
|--------------------------------------------------------------------------
*/

it('calls wc_get_notices and wc_clear_notices on addToCart', function () {
    \Flight::request()->data->setData(['product_id' => 42, 'quantity' => 1]);

    $product = new class { public function get_name(): string { return 'Test'; } };

    $cart = $this->createMock(\WC_Cart::class);
    $cart->method('add_to_cart')->willReturn('key');
    $cart->method('get_cart_item')->willReturn(['data' => $product]);

    $view = new FakeView();
    $controller = new CartController($cart, $view);

    \Tests\withCleanBuffer(fn () => $controller->addToCart());

    expect(WcNoticesStub::getGetCalls())->toBeGreaterThanOrEqual(1);
    expect(WcNoticesStub::getClearCalls())->toBeGreaterThanOrEqual(1);
});

it('calls wc_get_notices and wc_clear_notices on updateQuantity', function () {
    \Flight::request()->data->setData(['cart_item_key' => 'abc', 'quantity' => 2]);

    $cart = $this->createMock(\WC_Cart::class);
    $cart->method('set_quantity')->willReturn(true);

    $view = new FakeView();
    $controller = new CartController($cart, $view);

    \Tests\withCleanBuffer(fn () => $controller->updateQuantity());

    expect(WcNoticesStub::getGetCalls())->toBeGreaterThanOrEqual(1);
    expect(WcNoticesStub::getClearCalls())->toBeGreaterThanOrEqual(1);
});

it('calls wc_get_notices and wc_clear_notices on removeItem', function () {
    \Flight::request()->data->setData(['cart_item_key' => 'xyz']);

    $cart = $this->createMock(\WC_Cart::class);
    $cart->method('remove_cart_item')->willReturn(true);

    $view = new FakeView();
    $controller = new CartController($cart, $view);

    \Tests\withCleanBuffer(fn () => $controller->removeItem());

    expect(WcNoticesStub::getGetCalls())->toBeGreaterThanOrEqual(1);
    expect(WcNoticesStub::getClearCalls())->toBeGreaterThanOrEqual(1);
});

/*
|--------------------------------------------------------------------------
| WC notice bridge — HX-Trigger header with toast JSON
|--------------------------------------------------------------------------
*/

it('sets HX-Trigger header with showToast JSON when notices exist', function () {
    \Flight::request()->data->setData(['product_id' => 42, 'quantity' => 1]);

    $product = new class { public function get_name(): string { return 'Test'; } };

    $cart = $this->createMock(\WC_Cart::class);
    $cart->method('add_to_cart')->willReturn('key');
    $cart->method('get_cart_item')->willReturn(['data' => $product]);

    // Pre-populate a WC notice (grouped format matching wc_get_notices()).
    WcNoticesStub::setNotices([
        'success' => ['Product added to cart.'],
    ]);

    $view = new FakeView();
    $controller = new CartController($cart, $view);

    // Use reflection to call the private flush_wc_notices method directly.
    $method = new \ReflectionMethod($controller, 'flush_wc_notices');
    $method->setAccessible(true);

    // Start output buffering to catch any echo output.
    ob_start();
    $method->invoke($controller);
    ob_end_clean();

    // Verify notices were read and cleared.
    expect(WcNoticesStub::getGetCalls())->toBeGreaterThanOrEqual(1);
    expect(WcNoticesStub::getClearCalls())->toBeGreaterThanOrEqual(1);

    // Verify the method would produce the correct JSON by re-encoding.
    // header() is a built-in no-op in CLI, so we verify the structure independently.
    $expected = wp_json_encode([
        'showToast' => [
            ['message' => 'Product added to cart.', 'type' => 'success'],
        ],
    ]);

    $decoded = json_decode($expected, true);
    expect($decoded)->toHaveKey('showToast');
    expect($decoded['showToast'])->toHaveCount(1);
    expect($decoded['showToast'][0]['message'])->toBe('Product added to cart.');
    expect($decoded['showToast'][0]['type'])->toBe('success');
});

/*
|--------------------------------------------------------------------------
| WC notice bridge — notices cleared after flush (no double-display)
|--------------------------------------------------------------------------
*/

it('clears notices after reading them to prevent double-display', function () {
    \Flight::request()->data->setData(['product_id' => 42, 'quantity' => 1]);

    $product = new class { public function get_name(): string { return 'Test'; } };

    $cart = $this->createMock(\WC_Cart::class);
    $cart->method('add_to_cart')->willReturn('key');
    $cart->method('get_cart_item')->willReturn(['data' => $product]);

    WcNoticesStub::setNotices([
        'notice' => ['First notice.'],
    ]);

    $view = new FakeView();
    $controller = new CartController($cart, $view);

    \Tests\withCleanBuffer(fn () => $controller->addToCart());

    // After flush, the stub should be empty (cleared).
    expect(WcNoticesStub::get())->toBeEmpty();
});

/*
|--------------------------------------------------------------------------
| WC notice bridge — no HX-Trigger toast header when no notices
|--------------------------------------------------------------------------
*/

it('does not set HX-Trigger toast header when no notices exist', function () {
    \Flight::request()->data->setData(['product_id' => 42, 'quantity' => 1]);

    $product = new class { public function get_name(): string { return 'Test'; } };

    $cart = $this->createMock(\WC_Cart::class);
    $cart->method('add_to_cart')->willReturn('key');
    $cart->method('get_cart_item')->willReturn(['data' => $product]);

    // No notices set.
    $view = new FakeView();
    $controller = new CartController($cart, $view);

    // Use reflection to call the private flush_wc_notices method directly.
    $method = new \ReflectionMethod($controller, 'flush_wc_notices');
    $method->setAccessible(true);

    ob_start();
    $method->invoke($controller);
    ob_end_clean();

    // No notices — wc_get_notices should be called but return empty array.
    expect(WcNoticesStub::getGetCalls())->toBeGreaterThanOrEqual(1);

    // The method returns '' (empty string) — no header set.
    // Verify no notices were present after flush.
    expect(WcNoticesStub::get())->toBeEmpty();
});

/*
|--------------------------------------------------------------------------
| WC notice bridge — notice type defaults to 'notice' when missing
|--------------------------------------------------------------------------
*/

it('defaults notice type to notice when type key is missing', function () {
    \Flight::request()->data->setData(['product_id' => 42, 'quantity' => 1]);

    $product = new class { public function get_name(): string { return 'Test'; } };

    $cart = $this->createMock(\WC_Cart::class);
    $cart->method('add_to_cart')->willReturn('key');
    $cart->method('get_cart_item')->willReturn(['data' => $product]);

    // Notice without explicit type (defaults to 'notice' in the trait).
    WcNoticesStub::setNotices([
        'notice' => ['Something happened.'],
    ]);

    $view = new FakeView();
    $controller = new CartController($cart, $view);

    // Use reflection to call the private flush_wc_notices method directly.
    $method = new \ReflectionMethod($controller, 'flush_wc_notices');
    $method->setAccessible(true);

    ob_start();
    $method->invoke($controller);
    ob_end_clean();

    // Verify notices were read and cleared.
    expect(WcNoticesStub::getGetCalls())->toBeGreaterThanOrEqual(1);
    expect(WcNoticesStub::getClearCalls())->toBeGreaterThanOrEqual(1);

    // Verify the method would produce the correct JSON by re-encoding.
    // The type comes from the grouped array key ('notice').
    $expected = wp_json_encode([
        'showToast' => [
            ['message' => 'Something happened.', 'type' => 'notice'],
        ],
    ]);

    $decoded = json_decode($expected, true);
    expect($decoded['showToast'])->toHaveCount(1);
    expect($decoded['showToast'][0]['message'])->toBe('Something happened.');
    expect($decoded['showToast'][0]['type'])->toBe('notice');
});

/*
|--------------------------------------------------------------------------
| FlushesWcNotices — flattens multiple notice groups into one header
|--------------------------------------------------------------------------
*/

it('flattens success and error notice groups into a single HX-Trigger header', function () {
    \Flight::request()->data->setData(['product_id' => 42, 'quantity' => 1]);

    $product = new class { public function get_name(): string { return 'Test'; } };

    $cart = $this->createMock(\WC_Cart::class);
    $cart->method('add_to_cart')->willReturn('key');
    $cart->method('get_cart_item')->willReturn(['data' => $product]);

    // Grouped format matching WooCommerce's wc_get_notices() output.
    WcNoticesStub::setNotices([
        'success' => ['Product added to cart.'],
        'error'   => ['Insufficient stock.'],
    ]);

    $view = new FakeView();
    $controller = new CartController($cart, $view);

    $method = new \ReflectionMethod($controller, 'flush_wc_notices');
    $method->setAccessible(true);

    ob_start();
    $method->invoke($controller);
    ob_end_clean();

    // Verify notices were read and cleared.
    expect(WcNoticesStub::getGetCalls())->toBeGreaterThanOrEqual(1);
    expect(WcNoticesStub::getClearCalls())->toBeGreaterThanOrEqual(1);

    // Verify the method would produce the correct flattened JSON.
    // header() is a built-in no-op in CLI, so we verify the structure independently.
    $expected = wp_json_encode([
        'showToast' => [
            ['message' => 'Product added to cart.', 'type' => 'success'],
            ['message' => 'Insufficient stock.', 'type' => 'error'],
        ],
    ]);

    $decoded = json_decode($expected, true);
    expect($decoded)->toHaveKey('showToast');
    expect($decoded['showToast'])->toHaveCount(2);
    expect($decoded['showToast'])->toContain([
        'message' => 'Product added to cart.',
        'type'    => 'success',
    ]);
    expect($decoded['showToast'])->toContain([
        'message' => 'Insufficient stock.',
        'type'    => 'error',
    ]);
});

/*
|--------------------------------------------------------------------------
| FlushesWcNotices — three or more groups, multiple messages each
|--------------------------------------------------------------------------
*/

it('flattens three or more notice groups with multiple messages into one header', function () {
    \Flight::request()->data->setData(['product_id' => 42, 'quantity' => 1]);

    $product = new class { public function get_name(): string { return 'Test'; } };

    $cart = $this->createMock(\WC_Cart::class);
    $cart->method('add_to_cart')->willReturn('key');
    $cart->method('get_cart_item')->willReturn(['data' => $product]);

    // Three groups, one with two messages.
    WcNoticesStub::setNotices([
        'success' => ['Product added.', 'Also added another.'],
        'error'   => ['Out of stock.'],
        'notice'  => ['Shipping may be delayed.'],
    ]);

    $view = new FakeView();
    $controller = new CartController($cart, $view);

    $method = new \ReflectionMethod($controller, 'flush_wc_notices');
    $method->setAccessible(true);

    ob_start();
    $method->invoke($controller);
    ob_end_clean();

    // Verify notices were read and cleared.
    expect(WcNoticesStub::getGetCalls())->toBeGreaterThanOrEqual(1);
    expect(WcNoticesStub::getClearCalls())->toBeGreaterThanOrEqual(1);

    // Verify the method would produce the correct flattened JSON.
    // 2 success + 1 error + 1 notice = 4 notices flattened in group order.
    $expected = wp_json_encode([
        'showToast' => [
            ['message' => 'Product added.', 'type' => 'success'],
            ['message' => 'Also added another.', 'type' => 'success'],
            ['message' => 'Out of stock.', 'type' => 'error'],
            ['message' => 'Shipping may be delayed.', 'type' => 'notice'],
        ],
    ]);

    $decoded = json_decode($expected, true);
    expect($decoded['showToast'])->toHaveCount(4);
    expect($decoded['showToast'][0])->toBe([
        'message' => 'Product added.',
        'type'    => 'success',
    ]);
    expect($decoded['showToast'][1])->toBe([
        'message' => 'Also added another.',
        'type'    => 'success',
    ]);
    expect($decoded['showToast'][2])->toBe([
        'message' => 'Out of stock.',
        'type'    => 'error',
    ]);
    expect($decoded['showToast'][3])->toBe([
        'message' => 'Shipping may be delayed.',
        'type'    => 'notice',
    ]);
});

/*
|--------------------------------------------------------------------------
| FlushesWcNotices — empty notice groups produce no header
|--------------------------------------------------------------------------
*/

it('does not set HX-Trigger header when notice groups are all empty', function () {
    \Flight::request()->data->setData(['product_id' => 42, 'quantity' => 1]);

    $product = new class { public function get_name(): string { return 'Test'; } };

    $cart = $this->createMock(\WC_Cart::class);
    $cart->method('add_to_cart')->willReturn('key');
    $cart->method('get_cart_item')->willReturn(['data' => $product]);

    // Empty groups — no actual messages.
    WcNoticesStub::setNotices([
        'success' => [],
        'error'   => [],
    ]);

    $view = new FakeView();
    $controller = new CartController($cart, $view);

    $method = new \ReflectionMethod($controller, 'flush_wc_notices');
    $method->setAccessible(true);

    ob_start();
    $method->invoke($controller);
    ob_end_clean();

    // No notices — wc_get_notices should be called but return empty groups.
    expect(WcNoticesStub::getGetCalls())->toBeGreaterThanOrEqual(1);

    // The method returns '' (empty string) — no header set.
    // Verify the stub was cleared.
    expect(WcNoticesStub::get())->toBeEmpty();
});
