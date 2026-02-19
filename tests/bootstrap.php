<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Set up test environment
if (!getenv('CI')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../', '.env.test');
    $dotenv->load();
}

require_once __DIR__ . '/../bin/setup.php';
