<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Set up test environment
$dotenvEnv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../', '.env.test');
$dotenvEnv->safeLoad();

require_once __DIR__ . '/../bin/setup.php';