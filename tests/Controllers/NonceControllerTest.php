<?php

declare(strict_types=1);

/**
 * NonceController tests.
 *
 * Validates the NonceController class source for correct nonce
 * generation and JSON output patterns.
 */

/*
|--------------------------------------------------------------------------
| Class structure
|--------------------------------------------------------------------------
*/

it('declares strict types', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/NonceController.php');

    expect($source)->toContain('declare(strict_types=1)');
});

it('namespaces under ThemeApp\\Controllers', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/NonceController.php');

    expect($source)->toContain('namespace ThemeApp\\Controllers');
});

/*
|--------------------------------------------------------------------------
| refresh method
|--------------------------------------------------------------------------
*/

it('sets Content-Type to application/json', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/NonceController.php');

    expect($source)->toContain("Content-Type: application/json");
});

it('creates nonce for storefront_zero_htmx action', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/NonceController.php');

    expect($source)->toContain("wp_create_nonce( 'storefront_zero_htmx' )");
});

it('returns JSON with nonce key', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/NonceController.php');

    expect($source)->toContain("wp_json_encode");
    expect($source)->toContain("'nonce'");
});

it('is a static method', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/NonceController.php');

    expect($source)->toContain('public static function refresh()');
});

it('has no parameters', function () {
    $source = file_get_contents(__DIR__ . '/../../app/Controllers/NonceController.php');

    expect($source)->toContain('public static function refresh(): void');
});
