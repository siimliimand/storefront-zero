<?php

declare(strict_types=1);

use ThemeApp\Controllers\ProductController;

// View lives at app/View.php — PSR-4 maps ThemeApp\ to app/.
// The namespace-file mismatch means Composer can't autoload it — require manually.
require_once __DIR__ . '/../../app/View.php';

use ThemeApp\View;

/**
 * View test double that records render calls.
 *
 * Extends View to satisfy the ProductController constructor type-hint.
 * Overrides the static render method to capture calls instead of
 * including template files.
 */
class FakeProductView extends View
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
    FakeProductView::reset();
    WpTransientStore::reset();
    WcProductsStub::reset();
    WP_Query::reset();
    ob_start();
});

afterEach(function () {
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
});

/*
|--------------------------------------------------------------------------
| liveSearch — empty query
|--------------------------------------------------------------------------
*/

it('returns empty output when query is empty', function () {
    $view       = new FakeProductView();
    $controller = new ProductController($view);

    $controller->liveSearch();

    expect($view::$calls)->toBeEmpty();
    expect(ob_get_contents())->toBe('');
});

/*
|--------------------------------------------------------------------------
| liveSearch — valid query returns matching products
|--------------------------------------------------------------------------
*/

it('queries products and renders search-results view', function () {
    $product1 = new class {
        public int $id = 10;
    };
    $product2 = new class {
        public int $id = 20;
    };

    // WP_Query returns IDs; wc_get_products hydrates to objects.
    WP_Query::setPosts([10, 20]);
    WcProductsStub::setCallback(function (array $args) use ($product1, $product2) {
        return [$product1, $product2];
    });

    $view       = new FakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['s'] = 'hoodie';
    $controller->liveSearch();

    expect($view::$calls)->toHaveCount(1);
    expect($view::$calls[0]['view'])->toBe('search-results');
    expect($view::$calls[0]['data']['query'])->toBe('hoodie');
    expect($view::$calls[0]['data']['products'])->toHaveCount(2);
});

/*
|--------------------------------------------------------------------------
| liveSearch — no matching products
|--------------------------------------------------------------------------
*/

it('renders search-results with empty array when no products match', function () {
    // WP_Query returns no IDs, so no hydration happens.
    WP_Query::setPosts([]);

    $view       = new FakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['s'] = 'nonexistent';
    $controller->liveSearch();

    expect($view::$calls)->toHaveCount(1);
    expect($view::$calls[0]['view'])->toBe('search-results');
    expect($view::$calls[0]['data']['products'])->toHaveCount(0);
});

/*
|--------------------------------------------------------------------------
| liveSearch — transient caching
|--------------------------------------------------------------------------
*/

it('stores product IDs in transient after first query', function () {
    $product = new class {
        public int $id = 42;
    };

    // WP_Query returns the IDs; wc_get_products hydrates.
    WP_Query::setPosts([42]);
    WcProductsStub::setCallback(function (array $args) use ($product) {
        return [$product];
    });

    $view       = new FakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['s'] = 'cap';
    $controller->liveSearch();

    // Verify transient was set with the product IDs from WP_Query
    $cacheKey = 'sz_search_' . hash('xxh3', 'cap');
    expect(WpTransientStore::get($cacheKey))->toBe([42]);
});

/*
|--------------------------------------------------------------------------
| liveSearch — cached results returned on subsequent calls
|--------------------------------------------------------------------------
*/

it('returns cached product IDs from transient on subsequent calls', function () {
    $product = new class {
        public int $id = 55;
    };

    // Pre-populate the transient cache
    $cacheKey = 'sz_search_' . hash('xxh3', 'cap');
    WpTransientStore::set($cacheKey, [55]);

    $hydrationCount = 0;
    WcProductsStub::setCallback(function (array $args) use ($product, &$hydrationCount) {
        if (($args['return'] ?? '') === 'objects') {
            $hydrationCount++;
            return [$product];
        }

        // This should NOT be called when cache hits
        return [55];
    });

    $view       = new FakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['s'] = 'cap';
    $controller->liveSearch();

    // Only hydration call (return => objects), no ID query
    expect($hydrationCount)->toBe(1);
    expect($view::$calls)->toHaveCount(1);
    expect($view::$calls[0]['view'])->toBe('search-results');
});

/*
|--------------------------------------------------------------------------
| liveSearch — query sanitisation
|--------------------------------------------------------------------------
*/

it('sanitises the query input before searching', function () {
    // WP_Query captures the sanitized query in its constructor args.
    WP_Query::setPosts([]);

    $view       = new FakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['s'] = '  <script>alert("xss")</script>  ';
    $controller->liveSearch();

    $lastArgs = WP_Query::getLastArgs();

    // sanitize_text_field strips tags and trims
    expect($lastArgs['s'])->not->toContain('<script>');
    expect($lastArgs['s'])->toBe('alert("xss")');
});

/*
|--------------------------------------------------------------------------
| liveSearch — products are filtered for nulls
|--------------------------------------------------------------------------
*/

it('filters out null products from hydration results', function () {
    // WP_Query returns IDs; wc_get_products hydrates with one null.
    WP_Query::setPosts([10, 99]);
    WcProductsStub::setCallback(function (array $args) {
        // ID 99 doesn't exist — returns null
        return [
            new class {
                public int $id = 10;
            },
            null,
        ];
    });

    $view       = new FakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['s'] = 'mix';
    $controller->liveSearch();

    expect($view::$calls[0]['data']['products'])->toHaveCount(1);
});
