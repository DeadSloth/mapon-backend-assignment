<?php

declare(strict_types=1);

namespace App\Lib;

/**
 * Vehicle record mapping registration numbers to Mapon unit IDs.
 *
 * @property int|null $id
 * @property string|null $vehicle_number
 * @property int|null $mapon_unit_id
 * @property string|null $created_at
 */
class Vehicle extends Model
{
    public const TABLE = 'vehicles';
    public const PRIMARY_KEY = 'id';

    public const FIELDS = [
        'vehicle_number',
        'mapon_unit_id',
        'created_at',
    ];

    /**
     * Get a vehicle by registration number.
     */
    public static function getByVehicleNumber(string $vehicleNumber): ?self
    {
        $results = self::getAll(
            'vehicle_number = :vehicleNumber',
            ['vehicleNumber' => $vehicleNumber],
            null,
            1
        );

        return $results[0] ?? null;
    }

    /**
     * Get Mapon unit ID for a vehicle number.
     * Returns null if vehicle not found or has no Mapon mapping.
     */
    public static function getMaponUnitId(string $vehicleNumber): ?int
    {
        $vehicle = self::getByVehicleNumber($vehicleNumber);

        if ($vehicle === null || $vehicle->mapon_unit_id === null) {
            return null;
        }

        return (int) $vehicle->mapon_unit_id;
    }
}
