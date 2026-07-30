<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| View::render buffer level safety
|--------------------------------------------------------------------------
|
| Integration tests for View::render() that exercise real file I/O,
| output buffering, data extraction, and exception handling. These
| verify the buffer-level safety fix from task 4.1.
|
| WP_DEBUG is a PHP constant — it cannot be toggled mid-test. Tests
| that need it true call `define()` early; tests that need it false
| rely on the default (undefined / false). Tests that cannot match
| their required state are skipped.
|
*/

use ThemeApp\View;

// View.php has namespace ThemeApp but PSR-4 maps ThemeApp\ → app/.
// Composer expects app/View.php which doesn't match — require manually.
require_once __DIR__ . '/../../app/View.php';

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Capture all output produced by a callable.
 *
 * Starts its own ob_start, invokes the callable, and returns the
 * captured string. Restores the buffer level afterwards so each
| test starts with a clean slate.
 */
function captureOutput(callable $fn): string
{
    ob_start();
    $fn();
    return ob_get_clean();
}

/*
|--------------------------------------------------------------------------
| Render a valid view — verify output
|--------------------------------------------------------------------------
*/

it('renders a valid view and produces output', function () {
    $output = captureOutput(fn () => (new View())->render('mini-cart-fragment'));

    expect($output)->not->toBeEmpty();
    expect($output)->toBeString();
});

it('renders search-results view with data', function () {
    $output = captureOutput(fn () => (new View())->render('search-results', [
        'products' => [],
        'query'    => 'test',
    ]));

    expect($output)->not->toBeEmpty();
});

/*
|--------------------------------------------------------------------------
| Missing view — WP_DEBUG true → RuntimeException
|--------------------------------------------------------------------------
*/

it('throws RuntimeException for missing view when WP_DEBUG is true', function () {
    // If WP_DEBUG is already defined as false, we cannot flip it.
    if (defined('WP_DEBUG') && !WP_DEBUG) {
        $this->markTestSkipped('WP_DEBUG is false; cannot test RuntimeException path.');
    }

    if (!defined('WP_DEBUG')) {
        define('WP_DEBUG', true);
    }

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessageMatches('/View .* not found/');

    (new View())->render('this-view-does-not-exist-' . uniqid());
});

/*
|--------------------------------------------------------------------------
| Missing view — WP_DEBUG false → error comment + 500 status
|--------------------------------------------------------------------------
*/

it('outputs error comment for missing view when WP_DEBUG is false', function () {
    // If WP_DEBUG is already defined as true, we cannot flip it.
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $this->markTestSkipped('WP_DEBUG is true; cannot test production path.');
    }

    $output = captureOutput(fn () => (new View())->render('this-view-does-not-exist-' . uniqid()));

    expect($output)->toContain('<!-- View not found:');
});

/*
|--------------------------------------------------------------------------
| View that throws — cleans only its own buffers
|--------------------------------------------------------------------------
*/

it('cleans only its own buffers when view throws an exception', function () {
    if (defined('WP_DEBUG') && !WP_DEBUG) {
        $this->markTestSkipped('WP_DEBUG is false; cannot test debug detail output.');
    }

    if (!defined('WP_DEBUG')) {
        define('WP_DEBUG', true);
    }

    // Create a temporary view file that throws during include.
    $viewsDir  = __DIR__ . '/../../app/Views';
    $tempName  = '__temp_broken_view_' . uniqid();
    $tempPath  = $viewsDir . '/' . $tempName . '.php';

    file_put_contents($tempPath, '<?php throw new \RuntimeException("boom");');

    try {
        // Push an outer buffer so we can verify it survives the render.
        ob_start();
        $outerLevel = ob_get_level();

        $output = captureOutput(fn () => (new View())->render($tempName));

        // The outer buffer should still exist — render cleaned only its own.
        expect(ob_get_level())->toBeGreaterThanOrEqual($outerLevel);

        // Clean up the outer buffer we pushed for this test.
        ob_end_clean();
    } finally {
        // Always remove the temp file.
        @unlink($tempPath);
    }
});

it('preserves surrounding output when view throws an exception', function () {
    if (defined('WP_DEBUG') && !WP_DEBUG) {
        $this->markTestSkipped('WP_DEBUG is false; cannot test debug detail output.');
    }

    if (!defined('WP_DEBUG')) {
        define('WP_DEBUG', true);
    }

    $viewsDir = __DIR__ . '/../../app/Views';
    $tempName = '__temp_broken_view_' . uniqid();
    $tempPath = $viewsDir . '/' . $tempName . '.php';

    file_put_contents($tempPath, '<?php throw new \RuntimeException("test error");');

    try {
        $output = captureOutput(function () use ($tempName) {
            echo 'before';
            (new View())->render($tempName);
            echo 'after';
        });

        expect($output)->toContain('before');
        expect($output)->toContain('after');
        expect($output)->toContain('View rendering error');
    } finally {
        @unlink($tempPath);
    }
});

/*
|--------------------------------------------------------------------------
| Data extraction — variables available in template scope
|--------------------------------------------------------------------------
*/

it('extracts data variables into the template scope', function () {
    // Create a temporary view that echoes extracted variables.
    $viewsDir = __DIR__ . '/../../app/Views';
    $tempName = '__temp_data_view_' . uniqid();
    $tempPath = $viewsDir . '/' . $tempName . '.php';

    file_put_contents($tempPath, '<?php echo $greeting . " " . $target;');

    try {
        $output = captureOutput(fn () => (new View())->render($tempName, [
            'greeting' => 'hello',
            'target'   => 'world',
        ]));

        expect($output)->toBe('hello world');
    } finally {
        @unlink($tempPath);
    }
});

it('ignores data keys that collide with local variables (EXTR_SKIP)', function () {
    // extract() with EXTR_SKIP should not overwrite $path or $view.
    $viewsDir = __DIR__ . '/../../app/Views';
    $tempName = '__temp_skip_view_' . uniqid();
    $tempPath = $viewsDir . '/' . $tempName . '.php';

    // The template tries to use 'path' and 'view' which are local
    // variables inside render(). EXTR_SKIP means our data won't clobber them.
    file_put_contents($tempPath, '<?php echo "rendered";');

    try {
        $output = captureOutput(fn () => (new View())->render($tempName, [
            'path' => 'SHOULD_NOT_APPEAR',
            'view' => 'SHOULD_NOT_APPEAR',
        ]));

        expect($output)->toBe('rendered');
    } finally {
        @unlink($tempPath);
    }
});

/*
|--------------------------------------------------------------------------
| Buffer level safety — the core fix from task 4.1
|--------------------------------------------------------------------------
*/

it('restores buffer level after successful render', function () {
    $levelBefore = ob_get_level();

    captureOutput(fn () => (new View())->render('mini-cart-fragment'));

    expect(ob_get_level())->toBe($levelBefore);
});

it('restores buffer level after failed render', function () {
    $viewsDir = __DIR__ . '/../../app/Views';
    $tempName = '__temp_buffer_test_' . uniqid();
    $tempPath = $viewsDir . '/' . $tempName . '.php';

    file_put_contents($tempPath, '<?php throw new \RuntimeException("buffer test");');

    try {
        $levelBefore = ob_get_level();

        captureOutput(fn () => (new View())->render($tempName));

        expect(ob_get_level())->toBe($levelBefore);
    } finally {
        @unlink($tempPath);
    }
});

it('handles nested ob_start calls inside a view that throws', function () {
    if (defined('WP_DEBUG') && !WP_DEBUG) {
        $this->markTestSkipped('WP_DEBUG is false; cannot test debug detail output.');
    }

    if (!defined('WP_DEBUG')) {
        define('WP_DEBUG', true);
    }

    $viewsDir = __DIR__ . '/../../app/Views';
    $tempName = '__temp_nested_buffer_' . uniqid();
    $tempPath = $viewsDir . '/' . $tempName . '.php';

    // Template opens 3 extra buffers then throws — render() must clean all of them.
    file_put_contents($tempPath, <<<'PHP'
<?php
ob_start();
ob_start();
ob_start();
throw new \RuntimeException("nested buffer boom");
PHP
    );

    try {
        $levelBefore = ob_get_level();

        $output = captureOutput(fn () => (new View())->render($tempName));

        // All buffers should be cleaned — level must return to baseline.
        expect(ob_get_level())->toBe($levelBefore);
        expect($output)->toContain('View rendering error');
    } finally {
        @unlink($tempPath);
    }
});
