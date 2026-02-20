<?php

declare(strict_types=1);

namespace App\Domain\Mapon\Exceptions;

/**
 * Thrown when enrichment data for a transaction is not found.
 */
class MaponEnrichmentNotFoundException extends MaponApiException
{
    public static function forTransactionId(int $id): self
    {
        return new self("Enrichment data not found for transaction ID {$id}");
    }
}
