<?php

declare(strict_types=1);

namespace App\Domain\Mapon;

use App\Domain\Mapon\Exceptions\MaponEnrichmentNotFoundException;
use App\Domain\Mapon\MaponUnitData;
use App\Lib\ApiClient;
use App\Domain\Transaction\DTO\Transaction;
use App\Rpc\Section\Transaction\Enrich;

class MaponClient
{
    private ApiClient $client;

    public function __construct(ApiClient $client)
    {
        $this->client = $client;
    }

    /**
     * Fetch all resources from an endpoint and convert to DTOs.
     *
     * @param string $endpoint
     * @param array $query Optional query params
     * @return MaponUnitData[]
     */
    public function fetchAll(string $endpoint, array $query = []): array
    {
        try {
            $items = $this->client->get($endpoint, $query);
        } catch (\RuntimeException $e) {
            throw new \RuntimeException("Failed to fetch all MaponUnitDatas");
        }

        return array_map(fn(array $data) => MaponUnitData::fromApiResponse($data), $items);
    }

    /**
     * Fetch single resource and convert to DTO.
     *
     * @param string $endpoint
     * @param array $query Optional query params
     * @return MaponUnitData
     */
    public function fetchSingle(string $endpoint, array $query = []): MaponUnitData
    {
        try {
            $data = $this->client->get($endpoint, $query);
        } catch (\RuntimeException $e) {
            throw new MaponEnrichmentNotFoundException("Failed to fetch MaponUnitData");
        }
        if (isset($data['data']) && isset($data['data']['units']) && count($data['data']['units']) === 1) {
            return MaponUnitData::fromApiResponse($data['data']['units'][0]);
        }

        throw new MaponEnrichmentNotFoundException("MaponUnitData data not found for the given ID.");
    }
}
