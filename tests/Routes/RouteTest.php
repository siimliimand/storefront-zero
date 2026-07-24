<?php

declare(strict_types=1);

/**
 * Route definition tests for the Storefront Zero Flight PHP routes.
 *
 * These tests verify that routes.php loads without fatal errors and
 * that every expected route pattern is present in the file source.
 * Full Flight dispatch integration would require a WP environment;
 * here we verify the contract at the source level.
 */

use function Tests\load_route_definitions;

/*
|--------------------------------------------------------------------------
| Route source contract
|--------------------------------------------------------------------------
*/

it('defines all expected GET routes', function () {
    $source = load_route_definitions();

    expect($source)->toContain('GET /htmx-api/search');
    expect($source)->toContain('GET /htmx-api/nonce');
    expect($source)->toContain('GET /htmx-api/cart/mini');
});

it('defines all expected mutating routes', function () {
    $source = load_route_definitions();

    expect($source)->toContain('POST /htmx-api/cart/add');
    expect($source)->toContain('POST /htmx-api/cart/update-qty');
    expect($source)->toContain('DELETE /htmx-api/cart/remove');
});

it('includes nonce verification middleware via Flight::before', function () {
    $source = load_route_definitions();

    expect($source)->toContain("Flight::before( 'start'");
    expect($source)->toContain('wp_verify_nonce');
});

it('protects mutating requests with X-WP-NONCE header check', function () {
    $source = load_route_definitions();

    expect($source)->toContain('HTTP_X_WP_NONCE');
    expect($source)->toContain("storefront_zero_htmx'");
});

it('halts with 403 on invalid nonce', function () {
    $source = load_route_definitions();

    expect($source)->toContain('Flight::halt( 403');
});

it('maps GET /search to ProductController::liveSearch', function () {
    $source = load_route_definitions();

    expect($source)->toContain("ProductController::class )->liveSearch()");
});

it('maps cart routes to CartController instance methods', function () {
    $source = load_route_definitions();

    expect($source)->toContain("CartController::class )->addToCart()");
    expect($source)->toContain("CartController::class )->renderMiniCart()");
    expect($source)->toContain("CartController::class )->updateQuantity()");
    expect($source)->toContain("CartController::class )->removeItem()");
});

it('returns JSON content type on GET /nonce', function () {
    $source = load_route_definitions();

    expect($source)->toContain("Content-Type: application/json");
});
