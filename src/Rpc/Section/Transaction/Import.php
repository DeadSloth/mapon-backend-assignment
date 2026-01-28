<?php

declare(strict_types=1);

namespace App\Rpc\Section\Transaction;

use App\Domain\Transaction\Service\ImportService;
use App\Rpc\Section\Base;

/**
 * Import transactions from CSV data.
 *
 * Parameters:
 *   - csv_data (string): Raw CSV content
 *
 * Expected CSV columns:
 *   Date, Time, Card Nr., Vehicle Nr., Product, Amount, Total sum, Currency, Country, Country ISO, Fuel station
 *
 * Returns:
 *   - imported: Number of successfully imported transactions
 *   - skipped: Number of skipped rows (non-fuel products)
 *   - failed: Number of failed rows
 *   - errors: Array of error messages for failed rows
 *   - batch_id: Import batch identifier
 */
class Import extends Base
{
    public const AUTH = true;

    private string $csvData;

    public function __construct(\stdClass $params)
    {
        parent::__construct($params);
        $this->csvData = $this->requireParam('csv_data', 'string');
    }

    public function validate(): void
    {
        if (empty(trim($this->csvData))) {
            throw new \InvalidArgumentException('CSV data cannot be empty');
        }
    }

    public function process(): array
    {
        $service = new ImportService();
        $result = $service->importFromCsv($this->csvData);

        return $result->toArray();
    }
}
