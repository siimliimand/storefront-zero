<?php

declare(strict_types=1);

use ThemeApp\Controllers\ProductController;

require_once __DIR__ . '/../../app/View.php';
use ThemeApp\View;

/**
 * View test double that records render calls.
 */
class FilterFakeProductView extends View
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
    FilterFakeProductView::reset();
    WP_Query::reset();
    HeaderCapture::reset();
    WpHookStore::reset();
    \Flight::request()->query->setData([]);
});

/*
|--------------------------------------------------------------------------
| Category filter — adds product_cat tax_query
|--------------------------------------------------------------------------
*/

it('adds product_cat tax_query for category filter', function () {
    WP_Query::setPosts([5, 12]);

    $view       = new FilterFakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['category'] = 'hoodies';
    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    $args = WP_Query::getLastArgs();
    expect($args)->toHaveKey('tax_query');
    expect($args['tax_query'])->toBe([[
        'taxonomy' => 'product_cat',
        'field'    => 'slug',
        'terms'    => 'hoodies',
    ]]);
});

it('omits tax_query when category is empty', function () {
    WP_Query::setPosts([]);

    $view       = new FilterFakeProductView();
    $controller = new ProductController($view);

    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    $args = WP_Query::getLastArgs();
    expect($args)->not->toHaveKey('tax_query');
});

/*
|--------------------------------------------------------------------------
| Attribute filter — filter_color, filter_size, etc.
|--------------------------------------------------------------------------
*/

it('collects filter_color into tax_query', function () {
    WP_Query::setPosts([]);

    $view       = new FilterFakeProductView();
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

it('combines multiple attributes in tax_query', function () {
    WP_Query::setPosts([]);

    $view       = new FilterFakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['filter_color'] = 'blue';
    Flight::request()->query['filter_size']  = 'large';
    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    $args = WP_Query::getLastArgs();
    $taxQuery = $args['tax_query'];

    expect($taxQuery)->toHaveCount(2);
    expect($taxQuery[0]['taxonomy'])->toBe('filter_color');
    expect($taxQuery[1]['taxonomy'])->toBe('filter_size');
});

it('combines category and attribute with AND relation', function () {
    WP_Query::setPosts([]);

    $view       = new FilterFakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['category']     = 'hoodies';
    Flight::request()->query['filter_color'] = 'blue';
    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    $args = WP_Query::getLastArgs();
    $taxQuery = $args['tax_query'];

    expect($taxQuery['relation'])->toBe('AND');
    // relation + category + attribute = 3 entries
    expect($taxQuery)->toHaveCount(3);
});

/*
|--------------------------------------------------------------------------
| Price range filter — min_price, max_price
|--------------------------------------------------------------------------
*/

it('applies min and max price as meta_query', function () {
    WP_Query::setPosts([]);

    $view       = new FilterFakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['min_price'] = '10';
    Flight::request()->query['max_price'] = '50';
    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    $args = WP_Query::getLastArgs();
    expect($args)->toHaveKey('meta_query');

    $mq = $args['meta_query'];
    expect($mq['relation'])->toBe('AND');
    expect($mq[0])->toBe([
        'key'     => '_price',
        'value'   => 10,
        'compare' => '>=',
        'type'    => 'NUMERIC',
    ]);
    expect($mq[1])->toBe([
        'key'     => '_price',
        'value'   => 50,
        'compare' => '<=',
        'type'    => 'NUMERIC',
    ]);
});

it('applies min_price only when max_price is zero', function () {
    WP_Query::setPosts([]);

    $view       = new FilterFakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['min_price'] = '25';
    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    $mq = WP_Query::getLastArgs()['meta_query'];
    // relation + one condition
    expect($mq)->toHaveCount(2);
    expect($mq[0]['compare'])->toBe('>=');
});

it('applies max_price only when min_price is zero', function () {
    WP_Query::setPosts([]);

    $view       = new FilterFakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['max_price'] = '100';
    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    $mq = WP_Query::getLastArgs()['meta_query'];
    expect($mq)->toHaveCount(2);
    expect($mq[0]['compare'])->toBe('<=');
});

it('does not add meta_query when both prices are zero', function () {
    WP_Query::setPosts([]);

    $view       = new FilterFakeProductView();
    $controller = new ProductController($view);

    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    expect(WP_Query::getLastArgs())->not->toHaveKey('meta_query');
});

/*
|--------------------------------------------------------------------------
| Sort order — orderby and order
|--------------------------------------------------------------------------
*/

it('defaults to date DESC', function () {
    WP_Query::setPosts([]);

    $view       = new FilterFakeProductView();
    $controller = new ProductController($view);

    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    $args = WP_Query::getLastArgs();
    expect($args['orderby'])->toBe('date');
    expect($args['order'])->toBe('DESC');
});

it('maps orderby price to meta_value_num on _price', function () {
    WP_Query::setPosts([]);

    $view       = new FilterFakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['orderby'] = 'price';
    Flight::request()->query['order']   = 'ASC';
    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    $args = WP_Query::getLastArgs();
    expect($args['orderby'])->toBe('meta_value_num');
    expect($args['meta_key'])->toBe('_price');
    expect($args['order'])->toBe('ASC');
});

it('maps orderby popularity to meta_value_num on total_sales', function () {
    WP_Query::setPosts([]);

    $view       = new FilterFakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['orderby'] = 'popularity';
    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    $args = WP_Query::getLastArgs();
    expect($args['orderby'])->toBe('meta_value_num');
    expect($args['meta_key'])->toBe('total_sales');
});

it('maps orderby rating to meta_value_num on _wc_average_rating', function () {
    WP_Query::setPosts([]);

    $view       = new FilterFakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['orderby'] = 'rating';
    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    $args = WP_Query::getLastArgs();
    expect($args['orderby'])->toBe('meta_value_num');
    expect($args['meta_key'])->toBe('_wc_average_rating');
});

it('falls back to date when orderby is invalid', function () {
    WP_Query::setPosts([]);

    $view       = new FilterFakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['orderby'] = 'nonsense';
    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    expect(WP_Query::getLastArgs()['orderby'])->toBe('date');
});

it('falls back to DESC when order is invalid', function () {
    WP_Query::setPosts([]);

    $view       = new FilterFakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['order'] = 'SIDEWAYS';
    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    expect(WP_Query::getLastArgs()['order'])->toBe('DESC');
});

/*
|--------------------------------------------------------------------------
| No pre_get_posts leak — main query unaffected
|--------------------------------------------------------------------------
*/

it('does not register any pre_get_posts callbacks', function () {
    WP_Query::setPosts([1, 2]);

    $view       = new FilterFakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['category']  = 'test';
    Flight::request()->query['min_price'] = '10';
    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    // filterProducts now passes args directly — no pre_get_posts hooks registered.
    expect(WpHookStore::get('pre_get_posts'))->toBeEmpty();
});

it('passes all filter args directly to WP_Query constructor', function () {
    WP_Query::setPosts([1, 2]);

    $view       = new FilterFakeProductView();
    $controller = new ProductController($view);

    Flight::request()->query['category']  = 'shoes';
    Flight::request()->query['min_price'] = '20';
    Flight::request()->query['orderby']   = 'price';
    Flight::request()->query['order']     = 'ASC';
    \Tests\withCleanBuffer(fn () => $controller->filterProducts());

    $args = WP_Query::getLastArgs();
    expect($args)->toHaveKey('tax_query');
    expect($args)->toHaveKey('meta_query');
    expect($args['orderby'])->toBe('meta_value_num');
    expect($args['order'])->toBe('ASC');
});
