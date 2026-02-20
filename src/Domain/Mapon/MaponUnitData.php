<?php

declare(strict_types=1);

namespace App\Domain\Mapon;

/**
 * Data transfer object for Mapon unit GPS data.
 */
final readonly class MaponUnitData
{
    public function __construct(
        public ?float $latitude,
        public ?float $longitude,
        public ?int $odometer,
        public ?string $datetime,
    ) {}

    /**
     * Map API unit array to UnitData DTO
     */
    public static function fromApiResponse(array $unit): self
    {
        return new self(
            odometer: is_numeric($unit['mileage']['value']) ? (int) $unit['mileage']['value'] : null,
            latitude: $unit['position']['value']['lat'] ?? null,
            longitude: $unit['position']['value']['lng'] ?? null,
            datetime: $unit['position']['gmt'] ?? null,
        );
    }
}
