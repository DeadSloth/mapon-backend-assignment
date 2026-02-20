<?php

declare(strict_types=1);

namespace App\Domain\Mapon;

use App\Domain\Mapon\Exceptions\MaponEnrichmentFailedException;
use App\Domain\Mapon\Exceptions\MaponEnrichmentNotFoundException;
use App\Domain\Transaction\DTO\Transaction as DTOTransaction;
use App\Domain\Transaction\Repository\TransactionRepository;
use App\Lib\Transaction;
use DateTimeImmutable;
use Exception;
use RuntimeException;

class EnrichmentService
{
    
    private int $completed = 0;
    private int $failed = 0;
    private int $notFound = 0;
    private int $skipped = 0;
    private ?string $latestMessage = null;
    
    public function __construct(
        private MaponClient $provider,
        private TransactionRepository $repository = new TransactionRepository(),
    ) {
    }

    /**
     * Process enrichment for a single transaction.
     *
     * @param Transaction $transaction
     */
    public function processSingle(Transaction $transaction): Transaction
    {
        if ($transaction->isEnriched()) {
            $this->skipped++;
            return $transaction;
        }

        $unitData = null;

        try {
            $unitData = $this->fetchEnrichment($transaction);
        } catch (MaponEnrichmentNotFoundException $e) {
            $transaction->markEnrichmentNotFound($e->getMessage());
            $this->latestMessage = $e->getMessage();
            $this->repository->save($transaction);
            $this->notFound++;
        }

        try {
            $isValid = $this->validateEnrichment($unitData);
            if ($isValid) {
                $this->applyEnrichment($transaction, $unitData);
                $this->completed++;
            }
        } catch (MaponEnrichmentFailedException $e) {
            $transaction->markEnrichmentFailed($e->getMessage());
            $this->latestMessage = $e->getMessage();
            $this->repository->save($transaction);
            $this->failed++;
        }

        return $transaction;
    }

    /**
     * Batch process multiple transactions.
     *
     * @param Transaction[] $transactions
     * @return array Each item is the result of processSingle()
     */
    public function processBatch(array $transactions): array
    {
        $results = [];
        foreach ($transactions as $transaction) {
            $results[] = $this->processSingle($transaction);
        }

        return [
            'completed' => $this->completed,
            'failed' => $this->failed,
            'not_found' => $this->notFound,
            'skipped' => $this->skipped,
        ];
    }

    /**
     * Fetch enrichment data from API.
     */
    private function fetchEnrichment(Transaction $transaction): object
    {
        try {
            return $this->provider->fetchSingle(
                endpoint: 'unit_data/history_point.json',
                query: [
                    'datetime' => (new DateTimeImmutable($transaction->getTransactionDate()))
                        ->format('Y-m-d\TH:i:s\Z'),
                    'unit_id' => $transaction->mapon_unit_id,
                    'include' => ['position', 'mileage'],
                ],
            );
        } catch (RuntimeException $e) {
            throw new MaponEnrichmentNotFoundException('Enrichment not found: API error: ' . $e->getMessage());
        }
    }

    /**
     * Apply enrichment to transaction and validate.
     */
    private function applyEnrichment(Transaction $transaction, MaponUnitData $unitData): void
    {
        $transaction->applyEnrichment(
            latitude: $unitData->latitude,
            longitude: $unitData->longitude,
            odometer: (int) round($unitData->odometer),
        );

        if ($transaction->isEnriched()) {
            $this->repository->save($transaction);
        } else {
            throw new MaponEnrichmentFailedException('Failed to apply enrichment data.');
        }
    }

    private function validateEnrichment(?MaponUnitData $unitData): bool
    {
        if(null === $unitData) {
            return false;
        }
        
        if (!isset($unitData->odometer)) {
            throw new MaponEnrichmentFailedException('Mileage data missing.');
        }

        if ($unitData->latitude === null || $unitData->longitude === null) {
            throw new MaponEnrichmentFailedException('Invalid enrichment data: missing GPS coordinates.');
        }

        return true;
    }

    public function getLatestMessage(): ?string
    {
        return $this->latestMessage;
    }
}
