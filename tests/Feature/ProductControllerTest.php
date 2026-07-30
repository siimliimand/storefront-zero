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
    HeaderCapture::reset();
    WcNoticesStub::reset();
    WpHookStore::reset();
    // Reset Flight query params to prevent leakage between tests.
    \Flight::request()->query->setData([]);
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

/*
|--------------------------------------------------------------------------
| filterProducts — default query (no params)
|--------------------------------------------------------------------------
*/

it('calls WP_Query with product post type on default filter', function () {
    WP_Query::setPosts([1, 2]);

    $view       = new FakeProductView();
    $controller = new ProductController($view);

    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    $lastArgs = WP_Query::getLastArgs();
    expect($lastArgs['post_type'])->toBe('product');
    expect($lastArgs['post_status'])->toBe('publish');
});

/*
|--------------------------------------------------------------------------
| filterProducts — renders product-grid view
|--------------------------------------------------------------------------
*/

it('renders the product-grid view with query results', function () {
    WP_Query::setPosts([10, 20]);

    $view       = new FakeProductView();
    $controller = new ProductController($view);

    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    expect($view::$calls)->toHaveCount(1);
    expect($view::$calls[0]['view'])->toBe('product-grid');
    expect($view::$calls[0]['data']['products'])->toHaveCount(2);
});

/*
|--------------------------------------------------------------------------
| filterProducts — category filter sanitisation
|--------------------------------------------------------------------------
*/

it('applies category filter as tax_query', function () {
    WP_Query::setPosts([5]);

    $view       = new FakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['category'] = 'tshirts';
    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    $args = WP_Query::getLastArgs();
    expect($args)->toHaveKey('tax_query');
    expect($args['tax_query'])->toBe([[
        'taxonomy' => 'product_cat',
        'field'    => 'slug',
        'terms'    => 'tshirts',
    ]]);
});

/*
|--------------------------------------------------------------------------
| filterProducts — price range filter
|--------------------------------------------------------------------------
*/

it('applies min/max price filter as meta_query', function () {
    WP_Query::setPosts([3]);

    $view       = new FakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['min_price'] = '10';
    Flight::request()->query['max_price'] = '50';
    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    $args = WP_Query::getLastArgs();
    expect($args)->toHaveKey('meta_query');

    $metaQuery = $args['meta_query'];
    expect($metaQuery['relation'])->toBe('AND');
    expect($metaQuery[0]['key'])->toBe('_price');
    expect($metaQuery[0]['value'])->toBe(10);
    expect($metaQuery[0]['compare'])->toBe('>=');
    expect($metaQuery[1]['key'])->toBe('_price');
    expect($metaQuery[1]['value'])->toBe(50);
    expect($metaQuery[1]['compare'])->toBe('<=');
});

/*
|--------------------------------------------------------------------------
| filterProducts — min_price only
|--------------------------------------------------------------------------
*/

it('applies min_price filter without max_price', function () {
    WP_Query::setPosts([]);

    $view       = new FakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['min_price'] = '25';
    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    $args = WP_Query::getLastArgs();
    expect($args['meta_query'])->toHaveCount(2); // relation + one condition
    expect($args['meta_query'][0]['compare'])->toBe('>=');
});

/*
|--------------------------------------------------------------------------
| filterProducts — orderby whitelist
|--------------------------------------------------------------------------
*/

it('falls back to date orderby when invalid value is provided', function () {
    WP_Query::setPosts([]);

    $view       = new FakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['orderby'] = 'invalid_value';
    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    $args = WP_Query::getLastArgs();
    expect($args['orderby'])->toBe('date');
});

it('allows valid orderby values like price', function () {
    WP_Query::setPosts([]);

    $view       = new FakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['orderby'] = 'price';
    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    $args = WP_Query::getLastArgs();
    expect($args['orderby'])->toBe('meta_value_num');
    expect($args['meta_key'])->toBe('_price');
});

it('allows valid orderby values like popularity', function () {
    WP_Query::setPosts([]);

    $view       = new FakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['orderby'] = 'popularity';
    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    $args = WP_Query::getLastArgs();
    expect($args['orderby'])->toBe('meta_value_num');
    expect($args['meta_key'])->toBe('total_sales');
});

/*
|--------------------------------------------------------------------------
| filterProducts — attribute filter collection
|--------------------------------------------------------------------------
*/

it('collects filter_color attribute into tax_query', function () {
    WP_Query::setPosts([]);

    $view       = new FakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['filter_color'] = 'red';
    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    $args = WP_Query::getLastArgs();
    expect($args)->toHaveKey('tax_query');
    expect($args['tax_query'])->toContainEqual([
        'taxonomy' => 'filter_color',
        'field'    => 'slug',
        'terms'    => 'red',
    ]);
});

it('combines category and attribute filters with AND relation', function () {
    WP_Query::setPosts([]);

    $view       = new FakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['category']      = 'hoodies';
    Flight::request()->query['filter_color']  = 'blue';
    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    $args     = WP_Query::getLastArgs();
    $taxQuery = $args['tax_query'];

    // Should have 'relation' => 'AND' plus two entries.
    expect($taxQuery['relation'])->toBe('AND');
    expect($taxQuery)->toHaveCount(3); // relation + category + attribute
});

/*
|--------------------------------------------------------------------------
| filterProducts — pre_get_posts skips main query
|--------------------------------------------------------------------------
*/

it('does not register pre_get_posts callbacks (args passed directly)', function () {
    WP_Query::setPosts([]);

    $view       = new FakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['category'] = 'test';
    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    // filterProducts passes args directly to WP_Query — no hooks registered.
    expect(WpHookStore::get('pre_get_posts'))->toBeEmpty();
});

/*
|--------------------------------------------------------------------------
| filterProducts — sanitises XSS in category input
|--------------------------------------------------------------------------
*/

it('sanitises XSS from category input', function () {
    WP_Query::setPosts([]);

    $view       = new FakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['category'] = '<script>alert("xss")</script>';
    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    $args = WP_Query::getLastArgs();
    expect($args['tax_query'][0]['terms'])->not->toContain('<script>');
});

/*
|--------------------------------------------------------------------------
| filterProducts — order whitelist
|--------------------------------------------------------------------------
*/

it('falls back to DESC order when invalid value is provided', function () {
    WP_Query::setPosts([]);

    $view       = new FakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['order'] = 'SIDEWAYS';
    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    $args = WP_Query::getLastArgs();
    expect($args['order'])->toBe('DESC');
});

/*
|--------------------------------------------------------------------------
| filterProducts — WC notice bridge flush
|--------------------------------------------------------------------------
*/

it('calls wc_get_notices and wc_clear_notices during filterProducts', function () {
    WP_Query::setPosts([]);

    $view       = new FakeProductView();
    $controller = new ProductController($view);

    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    expect(WcNoticesStub::getGetCalls())->toBeGreaterThanOrEqual(1);
    expect(WcNoticesStub::getClearCalls())->toBeGreaterThanOrEqual(1);
});

/*
|--------------------------------------------------------------------------
| Session flushing — storefront_zero_flight_init existence
|--------------------------------------------------------------------------
*/

it('storefront_zero_flight_init function exists', function () {
    require_once __DIR__ . '/../../functions.php';

    expect(function_exists('storefront_zero_flight_init'))->toBeTrue();
});

it('storefront_zero_flight_init is hooked to template_redirect', function () {
    $source = file_get_contents(__DIR__ . '/../../functions.php');

    expect($source)->toContain("add_action( 'template_redirect', 'storefront_zero_flight_init', 5 )");
});

/*
|--------------------------------------------------------------------------
| Search transient purge — function existence and hook registration
|--------------------------------------------------------------------------
*/

it('storefront_zero_purge_search_transients function exists', function () {
    require_once __DIR__ . '/../../functions.php';

    expect(function_exists('storefront_zero_purge_search_transients'))->toBeTrue();
});

it('storefront_zero_purge_search_transients is hooked to save_post_product', function () {
    $source = file_get_contents(__DIR__ . '/../../functions.php');

    expect($source)->toContain("add_action( 'save_post_product', 'storefront_zero_purge_search_transients' )");
});

it('storefront_zero_purge_search_transients is hooked to woocommerce_update_product', function () {
    $source = file_get_contents(__DIR__ . '/../../functions.php');

    expect($source)->toContain("add_action( 'woocommerce_update_product', 'storefront_zero_purge_search_transients' )");
});

it('storefront_zero_purge_search_transients is hooked to delete_post', function () {
    $source = file_get_contents(__DIR__ . '/../../functions.php');

    expect($source)->toContain("add_action( 'delete_post', 'storefront_zero_purge_search_transients' )");
});

it('storefront_zero_purge_search_transients calls delete_transient', function () {
    require_once __DIR__ . '/../../functions.php';

    // Pre-populate the transient so we can verify it gets deleted.
    WpTransientStore::set('sz_search_hash', ['some', 'data']);
    expect(WpTransientStore::get('sz_search_hash'))->toBe(['some', 'data']);

    storefront_zero_purge_search_transients();

    // delete_transient sets it to false in our stub.
    expect(WpTransientStore::get('sz_search_hash'))->toBe(false);
});
