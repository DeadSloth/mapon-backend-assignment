<?php

declare(strict_types=1);

namespace App\Domain\Mapon\Exceptions;

/**
 * Thrown when enrichment data for a transaction is not found.
 */
class MaponEnrichmentFailedException extends MaponApiException
{
    public static function forTransactionId(int $id, string $reason): self
    {
        return new self("Enrichment data failed for transaction ID {$id}: {$reason}");
    }
}
