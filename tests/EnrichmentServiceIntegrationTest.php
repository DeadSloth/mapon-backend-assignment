<?php

declare(strict_types=1);

namespace Tests;

use App\Domain\Mapon\EnrichmentService;
use App\Domain\Mapon\MaponClient;
use App\Domain\Mapon\MaponUnitData;
use App\Domain\Transaction\Repository\TransactionRepository;
use App\Lib\Transaction;
use PHPUnit\Framework\TestCase;

class EnrichmentServiceIntegrationTest extends TestCase
{
    public function testBatchProcessingCountsCorrectly(): void
    {
        $client = $this->createMock(MaponClient::class);
        $repository = $this->createMock(TransactionRepository::class);

        $repository->method('save');

        $client->method('fetchSingle')
            ->willReturn(new MaponUnitData(
                latitude: 1.0,
                longitude: 1.0,
                odometer: 100,
                datetime: '2025-01-01T00:00:00Z'
            ));

        $transaction1 = $this->createMock(Transaction::class);
        $transaction1->method('isEnriched')
            ->willReturnOnConsecutiveCalls(false, true);
        $transaction1->method('applyEnrichment');
        $transaction1->method('getTransactionDate')
            ->willReturn('2025-01-01T00:00:00Z');

        $transaction2 = $this->createMock(Transaction::class);
        $transaction2->method('isEnriched')->willReturn(true);
        $transaction2->method('getTransactionDate')
            ->willReturn('2025-01-01T00:00:00Z');

        $service = new EnrichmentService($client, $repository);

        $summary = $service->processBatch([$transaction1, $transaction2]);

        $this->assertEquals(1, $summary['completed']);
        $this->assertEquals(1, $summary['skipped']);
    }
}
