<?php

declare(strict_types=1);

namespace Tests;

/**
 * Load the route definitions source code as a string.
 *
 * This reads the raw routes.php file so tests can assert on route
 * patterns without bootstrapping Flight PHP or WordPress.
 */
function load_route_definitions(): string
{
    $path = dirname(__DIR__) . '/app/routes.php';

    $source = file_get_contents($path);

    if (false === $source) {
        throw new \RuntimeException("Could not read routes.php at {$path}");
    }

    return $source;
}

/**
 * Run a callable and clean up any output buffers it opens.
 *
 * Controller methods (addToCart, updateQuantity, removeItem, filterProducts)
 * call ob_start() without ob_end_clean(). This helper wraps the call so
 * output buffers are properly restored after each test.
 */
function withCleanBuffer(callable $fn): void
{
    $level = ob_get_level();
    ob_start();
    $fn();
    while (ob_get_level() > $level) {
        ob_end_clean();
    }
}
