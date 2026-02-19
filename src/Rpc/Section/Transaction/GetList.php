<?php

declare(strict_types=1);

namespace App\Rpc\Section\Transaction;

use App\Domain\Transaction\DTO\Transaction as TransactionDTO;
use App\Domain\Transaction\Repository\TransactionRepository;
use App\Rpc\Section\Base;
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
class GetList extends Base
{
    public const AUTH = true;

    private ?string $vehicleNumber;
    private int $limit;
    private int $offset;
    private ?string $orderBy;

    public function __construct(stdClass $params)
    {
        parent::__construct($params);
        $this->vehicleNumber = $this->optionalParam('vehicle_number', 'string');
        $this->orderBy = $this->optionalParam('order_by', 'string', 'DESC');
        $this->limit = $this->optionalParam('limit', 'int', 100);
        $this->offset = $this->optionalParam('offset', 'int', 0);
    }

    public function validate(): void
    {
        if ($this->limit > 1000) {
            throw new \InvalidArgumentException('Limit cannot exceed 1000');
        }
    }

    public function process(): array
    {
        $repository = new TransactionRepository();

        if ($this->vehicleNumber !== null) {
            $transactions = $repository->getByVehicleNumber($this->vehicleNumber, $this->limit, $this->offset);
            $total = $repository->countByVehicleNumber($this->vehicleNumber);
        } else {
            $transactions = $repository->getAll($this->limit);
            $total = $repository->countAll();
        }

        $transactions = $this->sortTransactions($transactions);

        $transactions = array_map(fn(TransactionDTO $t) => $t->toArray(), $transactions);

        return [
            'items' => $transactions,
            'total' => $total,
            'limit' => $this->limit,
            'offset' => $this->offset,
        ];
    }

    private function sortTransactions(array $transactions): array
    {
        usort($transactions, function (TransactionDTO $a, TransactionDTO $b) {
            $direction = strtoupper($this->orderBy ?? 'DESC');

            $dateA = strtotime($a->transactionDate);
            $dateB = strtotime($b->transactionDate);

            return $direction === 'ASC'
                ? $dateA <=> $dateB
                : $dateB <=> $dateA;
        });
        return $transactions;
    }
}
