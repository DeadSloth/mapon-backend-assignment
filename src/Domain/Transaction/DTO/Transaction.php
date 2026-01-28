<?php

declare(strict_types=1);

namespace App\Domain\Transaction\DTO;

use App\Lib\Transaction as TransactionModel;

/**
 * Transaction Data Transfer Object.
 *
 * Immutable representation of a fuel transaction for API responses.
 */
readonly class Transaction
{
    public function __construct(
        public int $id,
        public string $vehicleNumber,
        public ?string $cardNumber,
        public string $transactionDate,
        public ?string $stationName,
        public ?string $stationCountry,
        public string $productType,
        public float $quantity,
        public string $unit,
        public ?float $unitPrice,
        public float $totalAmount,
        public string $currency,
        public ?string $originalCurrency,
        public ?float $originalAmount,
        public ?int $maponUnitId,
        public string $enrichmentStatus,
        public ?float $gpsLatitude,
        public ?float $gpsLongitude,
        public ?int $odometerGps,
        public ?string $enrichedAt,
        public ?string $importBatchId,
        public ?string $createdAt,
    ) {}

    public static function fromModel(TransactionModel $model): self
    {
        return new self(
            id: $model->id,
            vehicleNumber: $model->vehicle_number,
            cardNumber: $model->card_number,
            transactionDate: $model->transaction_date,
            stationName: $model->station_name,
            stationCountry: $model->station_country,
            productType: $model->product_type,
            quantity: (float) $model->quantity,
            unit: $model->unit ?? 'L',
            unitPrice: $model->unit_price !== null ? (float) $model->unit_price : null,
            totalAmount: (float) $model->total_amount,
            currency: $model->currency,
            originalCurrency: $model->original_currency,
            originalAmount: $model->original_amount !== null ? (float) $model->original_amount : null,
            maponUnitId: $model->mapon_unit_id !== null ? (int) $model->mapon_unit_id : null,
            enrichmentStatus: $model->enrichment_status ?? TransactionModel::ENRICHMENT_PENDING,
            gpsLatitude: $model->gps_latitude !== null ? (float) $model->gps_latitude : null,
            gpsLongitude: $model->gps_longitude !== null ? (float) $model->gps_longitude : null,
            odometerGps: $model->odometer_gps !== null ? (int) $model->odometer_gps : null,
            enrichedAt: $model->enriched_at,
            importBatchId: $model->import_batch_id,
            createdAt: $model->created_at,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'vehicle_number' => $this->vehicleNumber,
            'card_number' => $this->cardNumber,
            'transaction_date' => $this->transactionDate,
            'station_name' => $this->stationName,
            'station_country' => $this->stationCountry,
            'product_type' => $this->productType,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'unit_price' => $this->unitPrice,
            'total_amount' => $this->totalAmount,
            'currency' => $this->currency,
            'original_currency' => $this->originalCurrency,
            'original_amount' => $this->originalAmount,
            'mapon_unit_id' => $this->maponUnitId,
            'enrichment_status' => $this->enrichmentStatus,
            'gps_latitude' => $this->gpsLatitude,
            'gps_longitude' => $this->gpsLongitude,
            'odometer_gps' => $this->odometerGps,
            'enriched_at' => $this->enrichedAt,
            'import_batch_id' => $this->importBatchId,
            'created_at' => $this->createdAt,
        ];
    }
}
