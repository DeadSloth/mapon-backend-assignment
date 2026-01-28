<?php

declare(strict_types=1);

namespace App\Lib;

/**
 * Fuel transaction record from card provider imports.
 *
 * Represents a single fuel purchase transaction linked to a vehicle.
 *
 * @property int|null $id
 * @property string|null $vehicle_number
 * @property string|null $card_number
 * @property string|null $transaction_date
 * @property string|null $station_name
 * @property string|null $station_country
 * @property string|null $product_type
 * @property float|null $quantity
 * @property string|null $unit
 * @property float|null $unit_price
 * @property float|null $total_amount
 * @property string|null $currency
 * @property string|null $original_currency
 * @property float|null $original_amount
 * @property int|null $mapon_unit_id
 * @property string|null $enrichment_status
 * @property float|null $gps_latitude
 * @property float|null $gps_longitude
 * @property int|null $odometer_gps
 * @property string|null $enriched_at
 * @property string|null $import_batch_id
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class Transaction extends Model
{
    public const TABLE = 'transactions';
    public const PRIMARY_KEY = 'id';

    public const FIELDS = [
        'vehicle_number',
        'card_number',
        'transaction_date',
        'station_name',
        'station_country',
        'product_type',
        'quantity',
        'unit',
        'unit_price',
        'total_amount',
        'currency',
        'original_currency',
        'original_amount',
        // Mapon integration
        'mapon_unit_id',
        // Enrichment fields (from Mapon API)
        'enrichment_status',
        'gps_latitude',
        'gps_longitude',
        'odometer_gps',
        'enriched_at',
        // Metadata
        'import_batch_id',
        'created_at',
        'updated_at',
    ];

    // Enrichment status constants
    public const ENRICHMENT_PENDING = 'pending';
    public const ENRICHMENT_COMPLETED = 'completed';
    public const ENRICHMENT_FAILED = 'failed';
    public const ENRICHMENT_NOT_FOUND = 'not_found';

    /**
     * Get transactions for a specific vehicle.
     */
    public static function getByVehicleNumber(string $vehicleNumber, ?int $limit = null): array
    {
        return self::getAll(
            'vehicle_number = :vehicleNumber',
            ['vehicleNumber' => $vehicleNumber],
            'transaction_date DESC',
            $limit
        );
    }

    /**
     * Get transactions by import batch.
     */
    public static function getByBatchId(string $batchId): array
    {
        return self::getAll(
            'import_batch_id = :batchId',
            ['batchId' => $batchId],
            'transaction_date DESC'
        );
    }

    /**
     * Get transactions pending enrichment.
     */
    public static function getPendingEnrichment(int $limit = 100): array
    {
        return self::getAll(
            'enrichment_status = :status',
            ['status' => self::ENRICHMENT_PENDING],
            'created_at ASC',
            $limit
        );
    }

    /**
     * Check if this transaction has been enriched with GPS data.
     */
    public function isEnriched(): bool
    {
        return $this->enrichment_status === self::ENRICHMENT_COMPLETED;
    }

    /**
     * Mark as enrichment failed.
     */
    public function markEnrichmentFailed(?string $reason = null): void
    {
        $this->enrichment_status = self::ENRICHMENT_FAILED;
        $this->enriched_at = date('Y-m-d H:i:s');
    }

    /**
     * Apply enrichment data from Mapon API.
     */
    public function applyEnrichment(float $latitude, float $longitude, ?int $odometer): void
    {
        $this->gps_latitude = $latitude;
        $this->gps_longitude = $longitude;
        $this->odometer_gps = $odometer;
        $this->enrichment_status = self::ENRICHMENT_COMPLETED;
        $this->enriched_at = date('Y-m-d H:i:s');
    }
}
