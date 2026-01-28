# Fuel Transaction API - Take-Home Assignment

> **Start here:** Read [ASSIGNMENT.md](ASSIGNMENT.md) for the task description and requirements.

A minimal API for managing fuel card transactions with GPS enrichment capabilities.

---

## Quick Start

### Option 1: Docker (Recommended)

**Requirements:** Docker Desktop (includes Docker Compose)

If you don't have Docker installed, download it from https://www.docker.com/products/docker-desktop/

```bash
docker compose up
```

Open http://localhost:8000

<details>
<summary>Troubleshooting</summary>

**Database connection errors** (e.g., `getaddrinfo for db failed: Temporary failure in name resolution`):

This is usually caused by stale Docker network state. Fix it with a clean restart:

```bash
docker compose down
docker compose up
```

</details>

### Option 2: Manual Setup

**Requirements:** PHP 8.1+, Composer

<details>
<summary>Install PHP and Composer</summary>

**macOS (Homebrew):**
```bash
brew install php composer
```

**Linux (Ubuntu/Debian):**
```bash
sudo apt update
sudo apt install software-properties-common -y
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install php8.2 php8.2-cli php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-sqlite3 composer -y
sudo update-alternatives --set php /usr/bin/php8.2
```

**Windows:**

Download and run the installer from https://windows.php.net/download/ and https://getcomposer.org/download/

</details>

**Set up the project:**

```bash
composer install
cp .env.example .env
php bin/setup.php
```

**Start the development server:**

```bash
php -S localhost:8000 -t public public/router.php
```

Open http://localhost:8000

---

## Project Structure

```
fuel-api-assignment/
├── public/               # Web root
│   ├── index.html        # Frontend
│   ├── app.js            # Frontend logic
│   ├── style.css         # Styles
│   └── rpc/index.php     # API entry point
├── src/
│   ├── Rpc/              # RPC layer (API endpoints)
│   │   ├── RPC.php       # Request dispatcher
│   │   └── Section/      # Endpoint handlers
│   │       └── Transaction/
│   │           ├── GetList.php
│   │           └── Import.php
│   ├── Domain/           # Business logic
│   │   ├── Transaction/
│   │   │   ├── DTO/
│   │   │   ├── Repository/
│   │   │   └── Service/
│   │   └── Mapon/        # Mapon API integration
│   │       ├── MaponClient.php
│   │       ├── MaponUnitData.php
│   │       └── MaponApiException.php
│   └── Lib/              # Data access layer
│       ├── DB.php        # Database wrapper
│       ├── Model.php     # Base model class
│       ├── Transaction.php
│       └── Vehicle.php
├── tests/                # PHPUnit tests
└── sample-data/          # Sample CSV for testing
```

---

## Architecture Overview

### RPC Layer (`src/Rpc/`)

API calls use REST-style URLs:

```
POST /rpc/{section}/{action}
```

Example request:
```bash
curl -X POST http://localhost:8000/rpc/transaction/getList \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer test-api-key-12345" \
  -d '{ "limit": 50 }'
```

**Creating a new endpoint:**

1. Add a class file in `src/Rpc/Section/{Section}/` (e.g., `Transaction/Enrich.php`)
2. The endpoint URL will be `/rpc/{section}/{action}` (e.g., `/rpc/transaction/enrich`)
3. Implement `process()` method returning the result
4. Use `requireParam()` / `optionalParam()` for input validation

```php
// src/Rpc/Section/Transaction/Example.php
namespace App\Rpc\Section\Transaction;

class Example extends Base
{
    public const AUTH = true;  // Requires API key

    private int $limit;

    public function __construct(\stdClass $params)
    {
        parent::__construct($params);
        $this->limit = $this->optionalParam('limit', 'int', 100);
    }

    public function process(): array
    {
        // Your logic here
        return ['items' => [...]];
    }
}
```

### Domain Layer (`src/Domain/`)

Business logic organized by domain:

- **DTO/** - Immutable data transfer objects (readonly classes)
- **Service/** - Business logic orchestration

### Lib Layer (`src/Lib/`)

Simple active record pattern for database access:

```php
// Get a record
$transaction = Transaction::get(123);

// Query records
$transactions = Transaction::getByVehicleNumber('NJ-2702', limit: 50);

// Create/update
$transaction = new Transaction();
$transaction->vehicle_number = 'NJ-2702';
$transaction->total_amount = 50.00;
$transaction->save();

// Change tracking
$transaction->total_amount = 75.00;
$transaction->getChangedFields(); // ['total_amount']
$transaction->save();
```

---

## API Reference

### Authentication

Include API key in Authorization header:

```
Authorization: Bearer test-api-key-12345
```

### Existing Endpoints

#### `POST /rpc/transaction/getList`

Get list of transactions.

**Parameters:**
| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| vehicle_number | string | No | null | Filter by vehicle registration |
| limit | int | No | 100 | Max results (max 1000) |
| offset | int | No | 0 | Pagination offset |

#### `POST /rpc/transaction/import`

Import transactions from CSV (fuel card provider format).

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| csv_data | string | Yes | Raw CSV content |

**Expected CSV format:**
```
Date,Time,Card Nr.,Vehicle Nr.,Product,Amount,Total sum,Currency,Country,Country ISO,Fuel station
03.06.2023,10:35:03,2653636375240774,NJ-2702,Diesel,"51,41","49,87",EUR,Latvia,LV,"Circle K, Riga"
```

**Notes:**
- Date format: `DD.MM.YYYY` (European)
- Numbers use comma as decimal separator: `51,41`
- Non-fuel products (Coffee, etc.) are automatically skipped
- Vehicle's `mapon_unit_id` is looked up from the `vehicles` table

---

## Mapon API Reference

The Mapon telematics API provides GPS tracking data for vehicles.

**Documentation:** https://mapon.com/api/

**API Key:** Provided in `.env` file

### Relevant Endpoint

**Historical data (single point):** `GET /unit_data/history_point.json`

Gets vehicle position and odometer at a specific point in time.

**Parameters:**
- `key` - API key
- `unit_id` - Vehicle unit ID (available in `transaction.mapon_unit_id`)
- `datetime` - ISO 8601 timestamp with Z suffix (e.g., `2025-01-15T08:30:00Z`)
- `include[]` - Data to include: `position`, `mileage`

**Example Request:**
```
GET https://mapon.com/api/v1/unit_data/history_point.json?key=YOUR_API_KEY&unit_id=199332&datetime=2025-01-20T10:00:00Z&include[]=position&include[]=mileage
```

**Available Test Vehicles:**
| Vehicle Nr. | Mapon Unit ID |
|-------------|---------------|
| NJ-2702 | 417038 |
| OC-4485 | 199332 |

**Possible Errors:**
- `401` - Invalid API key
- `404` - Unit not found or no data for requested time
- `429` - Rate limit exceeded

---

## Running Tests

```bash
composer test
```

## Sample Data

A sample CSV file is provided in `sample-data/fuel_transactions.csv` for testing imports.
