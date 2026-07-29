<?php
/**
 * Router script for PHP built-in server.
 *
 * Handles WordPress rewrites and static file serving.
 * Usage: php -S 0.0.0.0:8080 router.php
 */

$root = $_SERVER['DOCUMENT_ROOT'];
$path = '/' . ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

// Serve static files directly
if (file_exists($root . $path)) {
	if (is_dir($root . $path)) {
		// Redirect directory requests to index
		header('Location: ' . $path . '/index.php');
		exit;
	}

	// Determine MIME type
	$ext = pathinfo($path, PATHINFO_EXTENSION);
	$mimes = [
		'css'  => 'text/css',
		'js'   => 'application/javascript',
		'json' => 'application/json',
		'png'  => 'image/png',
		'jpg'  => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'gif'  => 'image/gif',
		'svg'  => 'image/svg+xml',
		'woff' => 'font/woff',
		'woff2'=> 'font/woff2',
		'ttf'  => 'font/ttf',
		'ico'  => 'image/x-icon',
		'webp' => 'image/webp',
	];

	if (isset($mimes[$ext])) {
		header('Content-Type: ' . $mimes[$ext]);
		readfile($root . $path);
		exit;
	}

	return false;
}

// Route everything else through WordPress
require_once $root . '/index.php';
