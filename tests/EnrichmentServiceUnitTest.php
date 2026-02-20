<?php

declare(strict_types=1);

namespace Tests;

use App\Domain\Mapon\EnrichmentService;
use App\Domain\Mapon\MaponClient;
use App\Domain\Mapon\MaponUnitData;
use App\Domain\Mapon\Exceptions\MaponEnrichmentNotFoundException;
use App\Domain\Transaction\Repository\TransactionRepository;
use App\Lib\Transaction;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EnrichmentServiceUnitTest extends TestCase
{
    private MaponClient&MockObject $client;
    private TransactionRepository&MockObject $repository;

    protected function setUp(): void
    {
        $this->client = $this->createMock(MaponClient::class);
        $this->repository = $this->createMock(TransactionRepository::class);
    }

    public function testSkipsAlreadyEnrichedTransaction(): void
    {
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('isEnriched')->willReturn(true);

        $service = new EnrichmentService($this->client, $this->repository);

        $result = $service->processSingle($transaction);

        $this->assertSame($transaction, $result);

        $summary = $service->processBatch([$transaction]);

        $this->assertEquals(2, $summary['skipped']);
    }

    public function testMarksNotFoundWhenApiFails(): void
    {
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('isEnriched')->willReturn(false);
        $transaction->expects($this->once())
            ->method('markEnrichmentNotFound');

        $this->client->method('fetchSingle')
            ->willThrowException(new MaponEnrichmentNotFoundException('Not found'));

        $this->repository->expects($this->once())
            ->method('save');

        $service = new EnrichmentService($this->client, $this->repository);

        $service->processSingle($transaction);

        $summary = $service->processBatch([]);

        $this->assertEquals(1, $summary['not_found']);
    }

    public function testMarksFailedWhenValidationFails(): void
    {
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('isEnriched')->willReturn(false);
        $transaction->expects($this->once())
            ->method('markEnrichmentFailed');

        $this->client->method('fetchSingle')
            ->willReturn(new MaponUnitData(
                latitude: null,
                longitude: null,
                odometer: null,
                datetime: '2025-01-01T00:00:00Z'
            ));

        $this->repository->expects($this->once())
            ->method('save');

        $service = new EnrichmentService($this->client, $this->repository);

        $service->processSingle($transaction);

        $summary = $service->processBatch([]);

        $this->assertEquals(1, $summary['failed']);
    }

    public function testSuccessfulEnrichment(): void
    {
        // Real transaction instance
        $transaction = new Transaction();

        // Set required fields used by the service
        $transaction->mapon_unit_id = 123;
        $transaction->setTransactionDate('2025-01-01 00:00:00');

        // Mock API client
        $this->client->method('fetchSingle')
            ->willReturn(new MaponUnitData(
                latitude: 56.95,
                longitude: 24.10,
                odometer: 100000,
                datetime: '2025-01-01T00:00:00Z'
            ));

        // Repository should save once after successful enrichment
        $this->repository->expects($this->once())
            ->method('save')
            ->with($transaction);

        $service = new EnrichmentService($this->client, $this->repository);

        // IMPORTANT: call batch directly (resets logic cleanly)
        $summary = $service->processBatch([$transaction]);

        $this->assertSame(1, $summary['completed']);
        $this->assertSame(0, $summary['failed']);
        $this->assertSame(0, $summary['not_found']);
        $this->assertSame(0, $summary['skipped']);

        // And assert that transaction was actually enriched
        $this->assertTrue($transaction->isEnriched());
    }
}
