<?php
declare(strict_types=1);

/**
 * Nonce Controller
 *
 * Handles nonce-related operations for the HTMX API.
 *
 * @package Storefront_Zero
 */

namespace ThemeApp\Controllers;

use ThemeApp\View;

class NonceController
{
	public function __construct(private View $view)
	{
	}

	/**
	 * Return a fresh nonce for cache-safe HTMX requests.
	 *
	 * @return void
	 */
	public function refresh(): void
	{
		header( 'Content-Type: application/json' );
		echo wp_json_encode( [
			'nonce' => wp_create_nonce( 'storefront_zero_htmx' ),
		] );
	}
}
