<?php

declare(strict_types=1);

/**
 * ProductController tests.
 *
 * Validates the structure and logic of ProductController by reading
 * the class source and asserting expected patterns. Full integration
 * testing against WooCommerce would require a bootstrapped WP site.
 */

/*
|--------------------------------------------------------------------------
| liveSearch source contract
|--------------------------------------------------------------------------
*/

it('declares strict types', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/ProductController.php');

    expect($source)->toContain('declare(strict_types=1)');
});

it('namespaces under ThemeApp\\Controllers', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/ProductController.php');

    expect($source)->toContain('namespace ThemeApp\\Controllers');
});

it('imports ThemeApp\\ViewInterface', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/ProductController.php');

    expect($source)->toContain('use ThemeApp\\ViewInterface');
});

it('sanitizes search input with sanitize_text_field', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/ProductController.php');

    expect($source)->toContain('sanitize_text_field( wp_unslash( Flight::request()->query[\'s\'] ) )');
});

it('returns empty output for empty search query', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/ProductController.php');

    expect($source)->toContain('empty( $query )');
    expect($source)->toContain("echo ''");
});

it('caches product IDs with a transient keyed by query hash', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/ProductController.php');

    expect($source)->toContain("sz_search_' . \$generation . '_' . hash( 'xxh3'");
    expect($source)->toContain('get_transient( $cache_key )');
    expect($source)->toContain('set_transient( $cache_key, $product_ids, 60 )');
});

it('queries only published products limited to 5 results', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/ProductController.php');

    expect($source)->toContain("'post_status'    => 'publish'");
    expect($source)->toContain("'posts_per_page' => 5");
    expect($source)->toContain("'fields'         => 'ids'");
});

it('hydrates cached IDs back to WC_Product objects', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/ProductController.php');

    expect($source)->toContain("wc_get_products");
    expect($source)->toContain("'include'");
});

it('renders search-results view with products and query', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/ProductController.php');

    expect($source)->toContain("\$this->view->render( 'search-results'");
    expect($source)->toContain("'products' => \$products");
    expect($source)->toContain("'query'    => \$query");
});

it('sets Content-Type header for HTMX HTML response', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/ProductController.php');

    expect($source)->toContain("Content-Type: text/html; charset=utf-8");
});
