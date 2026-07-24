<?php

declare(strict_types=1);

/**
 * View class tests.
 *
 * Tests View::render() directly for path resolution, output buffering,
 * error handling, and WP_DEBUG-conditional behavior.
 */

/*
|--------------------------------------------------------------------------
| Class structure
|--------------------------------------------------------------------------
*/

it('declares strict types', function () {
    $source = file_get_contents(__DIR__ . '/../../app/View.php');

    expect($source)->toContain('declare(strict_types=1)');
});

it('namespaces under ThemeApp', function () {
    $source = file_get_contents(__DIR__ . '/../../app/View.php');

    expect($source)->toContain('namespace ThemeApp');
});

/*
|--------------------------------------------------------------------------
| render() method — path resolution
|--------------------------------------------------------------------------
*/

it('sanitizes view name with basename', function () {
    $source = file_get_contents(__DIR__ . '/../../app/View.php');

    expect($source)->toContain('$view = basename( $view )');
});

it('constructs path relative to Views directory', function () {
    $source = file_get_contents(__DIR__ . '/../../app/View.php');

    expect($source)->toContain("__DIR__ . '/' . \$view . '.php'");
});

it('checks file existence before including', function () {
    $source = file_get_contents(__DIR__ . '/../../app/View.php');

    expect($source)->toContain('file_exists( $path )');
});

/*
|--------------------------------------------------------------------------
| render() method — missing view handling
|--------------------------------------------------------------------------
*/

it('throws RuntimeException for missing view in debug mode', function () {
    $source = file_get_contents(__DIR__ . '/../../app/View.php');

    expect($source)->toContain('throw new \\RuntimeException');
    expect($source)->toContain('View "%s" not found at %s');
});

it('outputs HTML comment for missing view in production', function () {
    $source = file_get_contents(__DIR__ . '/../../app/View.php');

    expect($source)->toContain('<!-- View not found:');
    expect($source)->toContain('esc_html( $view )');
});

it('sets 500 status for missing view in production', function () {
    $source = file_get_contents(__DIR__ . '/../../app/View.php');

    expect($source)->toContain('status_header( 500 )');
});

/*
|--------------------------------------------------------------------------
| render() method — output buffering and exception handling
|--------------------------------------------------------------------------
*/

it('wraps template include in output buffering', function () {
    $source = file_get_contents(__DIR__ . '/../../app/View.php');

    expect($source)->toContain('ob_start()');
    expect($source)->toContain('include $path');
    expect($source)->toContain('ob_get_clean()');
});

it('cleans output buffer on exception', function () {
    $source = file_get_contents(__DIR__ . '/../../app/View.php');

    expect($source)->toContain('catch ( \\Throwable $e )');
    expect($source)->toContain('ob_end_clean()');
});

it('sets 500 status on rendering exception', function () {
    $source = file_get_contents(__DIR__ . '/../../app/View.php');

    // After catching the exception, status_header(500) should be called.
    $lines = explode("\n", $source);
    $inCatch = false;

    foreach ($lines as $line) {
        if (str_contains($line, 'catch ( \\Throwable $e )')) {
            $inCatch = true;
        }
        if ($inCatch && str_contains($line, 'status_header( 500 )')) {
            expect(true)->toBeTrue();
            return;
        }
    }

    $this->fail('status_header(500) not found in catch block');
});

it('outputs debug details for rendering exception when WP_DEBUG is true', function () {
    $source = file_get_contents(__DIR__ . '/../../app/View.php');

    expect($source)->toContain('View rendering error:');
    expect($source)->toContain('$e->getMessage()');
    expect($source)->toContain('$e->getFile()');
});

it('outputs generic error comment when WP_DEBUG is false', function () {
    $source = file_get_contents(__DIR__ . '/../../app/View.php');

    expect($source)->toContain('<!-- View rendering error -->');
});

/*
|--------------------------------------------------------------------------
| render() — data extraction
|--------------------------------------------------------------------------
*/

it('extracts data array into template scope', function () {
    $source = file_get_contents(__DIR__ . '/../../app/View.php');

    expect($source)->toContain('extract( $data, EXTR_SKIP )');
});

/*
|--------------------------------------------------------------------------
| render() — functional test with a real view
|--------------------------------------------------------------------------
|
| View.php is at app/View.php with `namespace ThemeApp;` but PSR-4
| maps ThemeApp\\ to app/, so the autoloader expects app/View.php.
| We require the file manually to test rendering.
|
*/

it('renders a valid view and captures output', function () {
    require_once __DIR__ . '/../../app/View.php';

    ob_start();
    \ThemeApp\View::render('search-results', [
        'products' => [],
        'query'    => 'test',
    ]);
    $output = ob_get_clean();

    // search-results.php should produce some HTML output.
    expect($output)->not->toBeEmpty();
});

it('handles empty data array gracefully', function () {
    require_once __DIR__ . '/../../app/View.php';

    ob_start();
    \ThemeApp\View::render('mini-cart-fragment');
    $output = ob_get_clean();

    // mini-cart-fragment.php should produce some HTML output.
    expect($output)->not->toBeEmpty();
});

it('throws RuntimeException for non-existent view when WP_DEBUG is true', function () {
    require_once __DIR__ . '/../../app/View.php';

    if (!defined('WP_DEBUG')) {
        define('WP_DEBUG', true);
    }

    // Skip if WP_DEBUG is already false (can't redefine constants).
    if (!WP_DEBUG) {
        $this->markTestSkipped('WP_DEBUG is false; cannot test RuntimeException path.');
    }

    $this->expectException(\RuntimeException::class);

    \ThemeApp\View::render('this-view-does-not-exist-' . uniqid());
});
