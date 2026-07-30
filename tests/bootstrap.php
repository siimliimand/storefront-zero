<?php

declare(strict_types=1);

/**
 * Test bootstrap — loaded by phpunit.xml.dist before tests run.
 *
 * Loads the Composer autoloader first (PSR-4 classes, Pest, etc.),
 * then manually loads the WordPress stubs and helper files so they
 * are available to Pest tests without being auto-loaded by PHPStan.
 */

require __DIR__ . '/../vendor/autoload.php';

// WordPress/WooCommerce function stubs (guarded by function_exists).
require __DIR__ . '/helpers-wp-stubs.php';

// Theme test helpers.
require __DIR__ . '/helpers.php';
