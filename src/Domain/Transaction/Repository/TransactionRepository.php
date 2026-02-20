<?php

declare(strict_types=1);

namespace App\Domain\Transaction\Repository;

use App\Lib\Transaction as TransactionModel;
use App\Lib\DB;

/**
 * Repository for accessing fuel transactions.
 *
 * Wraps the Lib\Model\Transaction with domain-specific query methods.
 */
class TransactionRepository
{
    /**
     * Get all transactions with pagination.
     */
    public function getAll(int $limit = 100): array
    {
        return TransactionModel::getAll(
            null,
            [],
            'transaction_date DESC',
            $limit
        );
    }

    /**
     * Get transactions for a specific vehicle.
     */
    public function getByVehicleNumber(string $vehicleNumber, int $limit = 100, int $offset = 0): array
    {
        return TransactionModel::getByVehicleNumber($vehicleNumber, $limit);
    }

    /**
     * Get transactions for a specific vehicle.
     */
    public function getById(int $id): ?TransactionModel
    {
        return TransactionModel::get($id);
    }

    /**
     * Count all transactions.
     */
    public function countAll(): int
    {
        $result = DB::queryOne("SELECT COUNT(*) as count FROM transactions");

        return (int) ($result['count'] ?? 0);
    }

    /**
     * Count transactions for a vehicle.
     */
    public function countByVehicleNumber(string $vehicleNumber): int
    {
        $result = DB::queryOne(
            "SELECT COUNT(*) as count FROM transactions WHERE vehicle_number = :vehicleNumber",
            ['vehicleNumber' => $vehicleNumber]
        );

        return (int) ($result['count'] ?? 0);
    }

    /**
     * Save a transaction model.
     */
    public function save(TransactionModel $model): bool
    {
        return $model->save();
    }
}
