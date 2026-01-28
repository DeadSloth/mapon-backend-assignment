<?php

declare(strict_types=1);

namespace App\Domain\Transaction\DTO;

/**
 * Result of a CSV import operation.
 */
readonly class ImportResult
{
    public function __construct(
        public int $imported,
        public int $skipped,
        public int $failed,
        public array $errors,
        public string $batchId,
    ) {}

    public function toArray(): array
    {
        return [
            'imported' => $this->imported,
            'skipped' => $this->skipped,
            'failed' => $this->failed,
            'errors' => $this->errors,
            'batch_id' => $this->batchId,
        ];
    }
}
