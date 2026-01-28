-- Fuel Transaction API Schema
-- This file is for reference only. The actual schema is created by bin/setup.php

CREATE TABLE transactions (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    vehicle_number VARCHAR(20) NOT NULL,  -- Vehicle registration number (e.g., "JR-2222")
    card_number VARCHAR(50),
    transaction_date DATETIME NOT NULL,
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

    -- Mapon integration (looked up from vehicles table)
    mapon_unit_id INTEGER,

    -- Enrichment fields (from Mapon API)
    enrichment_status VARCHAR(20) DEFAULT 'pending',
    gps_latitude DECIMAL(10, 7),
    gps_longitude DECIMAL(10, 7),
    odometer_gps INTEGER,
    enriched_at DATETIME,

    -- Metadata
    import_batch_id VARCHAR(100),
    created_at DATETIME,
    updated_at DATETIME

    -- Note: Consider adding indexes for frequently queried columns:
    -- INDEX idx_vehicle_number (vehicle_number),
    -- INDEX idx_transaction_date (transaction_date),
    -- INDEX idx_enrichment_status (enrichment_status)
);

-- Vehicle mapping table (vehicle registration -> Mapon unit ID)
CREATE TABLE vehicles (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    vehicle_number VARCHAR(20) NOT NULL UNIQUE,
    mapon_unit_id INTEGER,
    created_at DATETIME
);
