<?php
/**
 * Flight PHP Route Definitions
 *
 * Routes are relative to the base URL /htmx-api.
 * Middleware (nonce verification) is applied via Flight::before() in a separate step.
 *
 * @package Storefront_Zero
 */

use ThemeApp\Controllers\ProductController;
use ThemeApp\Controllers\CartController;

// GET /search — Live product search.
Flight::route( 'GET /search', function () {
	ProductController::liveSearch();
} );

// POST /cart/add — Add product to cart.
Flight::route( 'POST /cart/add', function () {
	CartController::addToCart();
} );
