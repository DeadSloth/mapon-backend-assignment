<?php

/**
 * Router for PHP's built-in development server.
 *
 * Usage: php -S localhost:8000 -t public public/router.php
 */

$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// Route /rpc/* requests to /rpc/index.php
if (preg_match('#^/rpc/#', $path)) {
    require __DIR__ . '/rpc/index.php';
    return true;
}

// Serve static files normally
if (file_exists(__DIR__ . $path) && is_file(__DIR__ . $path)) {
    return false; // Let PHP's built-in server handle it
}

// Default to index.html
if ($path === '/' || !file_exists(__DIR__ . $path)) {
    require __DIR__ . '/index.html';
    return true;
}

return false;
