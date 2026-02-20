<?php

declare(strict_types=1);

namespace App\Rpc\Section\Transaction;

use App\Domain\Mapon\EnrichmentService;
use App\Domain\Mapon\MaponClient;
use App\Domain\Transaction\DTO\Transaction as TransactionDTO;
use App\Domain\Transaction\Repository\TransactionRepository;
use App\Lib\ApiClient;
use App\Rpc\Section\Base;
use DateTime;
use stdClass;

/**
 * Get a list of fuel transactions.
 *
 * Parameters:
 *   - vehicle_number (string, optional): Filter by vehicle registration number
 *   - limit (int, optional): Max results, default 100
 *   - offset (int, optional): Pagination offset, default 0
 *
 * Returns:
 *   - items: Array of transaction objects
 *   - total: Total count (for pagination)
 */
class EnrichAll extends Base
{
    public const AUTH = true;

    private int $limit;

    public function __construct(stdClass $params)
    {
        parent::__construct($params);
        $this->limit = $this->requireParam('limit', 'int');
    }

    public function validate(): void
    {
        if ($this->limit <= 0) {
            throw new \InvalidArgumentException("Parameter 'id' must be a positive integer.");
        }
    }

    public function process(): array
    {
        $repository = new TransactionRepository();

        $enrichmentService = new EnrichmentService(
            new MaponClient(
                new ApiClient(
                    $_ENV['MAPON_API_URL'], 
                    $_ENV['MAPON_API_KEY'],
                    )
            ),
        );

        return $enrichmentService->processBatch(
            $repository->getAll($this->limit)
        );
    }
}
