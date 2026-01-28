#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

use App\Lib\DB;

echo "Setting up Fuel API database...\n";

$driver = $_ENV['DB_DRIVER'] ?? 'sqlite';
echo "Using driver: {$driver}\n";

$schema = getSchema($driver);
try {
    $pdo = DB::connection();

    $statements = array_filter(
        array_map('trim', explode(';', $schema)),
        fn($s) => !empty($s)
    );

    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }

    echo "Schema created successfully.\n";

    seedVehicles();

    echo "\nSetup complete! Run 'php -S localhost:8000 -t public public/router.php' to start the development server.\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}

function getSchema(string $driver): string
{
    $autoIncrement = $driver === 'sqlite' ? 'AUTOINCREMENT' : 'AUTO_INCREMENT';
    $dateTimeType = $driver === 'sqlite' ? 'TEXT' : 'DATETIME';

    return <<<SQL
-- Vehicles table (vehicle registration -> Mapon unit ID mapping)
DROP TABLE IF EXISTS vehicles;

CREATE TABLE vehicles (
    id INTEGER PRIMARY KEY {$autoIncrement},
    vehicle_number VARCHAR(20) NOT NULL UNIQUE,
    mapon_unit_id INTEGER,
    created_at {$dateTimeType}
);

-- Transactions table
DROP TABLE IF EXISTS transactions;

CREATE TABLE transactions (
    id INTEGER PRIMARY KEY {$autoIncrement},
    vehicle_number VARCHAR(20) NOT NULL,
    card_number VARCHAR(50),
    transaction_date {$dateTimeType} NOT NULL,
    station_name VARCHAR(255),
    station_country VARCHAR(10),
    product_type VARCHAR(50) NOT NULL,
    quantity DECIMAL(10, 3) NOT NULL,
    unit VARCHAR(10) DEFAULT 'L',
    unit_price DECIMAL(10, 4),
    total_amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'EUR',
    original_currency VARCHAR(3),
    original_amount DECIMAL(10, 2),
    mapon_unit_id INTEGER,
    enrichment_status VARCHAR(20) DEFAULT 'pending',
    gps_latitude DECIMAL(10, 7),
    gps_longitude DECIMAL(10, 7),
    odometer_gps INTEGER,
    enriched_at {$dateTimeType},
    import_batch_id VARCHAR(100),
    created_at {$dateTimeType},
    updated_at {$dateTimeType}
);

-- CREATE INDEX idx_transactions_vehicle ON transactions(vehicle_number);
-- CREATE INDEX idx_transactions_date ON transactions(transaction_date);
SQL;
}

function seedVehicles(): void
{
    $pdo = DB::connection();

    $vehicles = [
        ['vehicle_number' => 'NJ-2702', 'mapon_unit_id' => 417038],
        ['vehicle_number' => 'OC-4485', 'mapon_unit_id' => 199332],
    ];

    $stmt = $pdo->prepare(
        'INSERT INTO vehicles (vehicle_number, mapon_unit_id, created_at) VALUES (:vehicle_number, :mapon_unit_id, :created_at)'
    );

    foreach ($vehicles as $v) {
        $stmt->execute([
            'vehicle_number' => $v['vehicle_number'],
            'mapon_unit_id' => $v['mapon_unit_id'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    echo "Inserted " . count($vehicles) . " vehicles.\n";
}
