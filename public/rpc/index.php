<?php

/**
 * RPC Entry Point
 *
 * All API requests go through this endpoint.
 * Request format: POST with JSON body containing method and params.
 */

declare(strict_types=1);

use App\Rpc\RPC;

require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2));
$dotenv->safeLoad();

header('Content-Type: application/json');

// Handle CORS for local development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$rpc = new RPC();
echo $rpc->handle();
