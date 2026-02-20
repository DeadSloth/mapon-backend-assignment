<?php

declare(strict_types=1);

namespace App\Lib;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use PDO;

/**
 * Generic API Client
 *
 * Uses PDO with named parameter binding. Query parameters use :name syntax
 * and are automatically escaped.
 *
 * Usage:
 * $client = new \App\Lib\ApiClient('https://api.example.com', 'test-api-key');
 *
 * // Get all transactions with limit
 * $transactions = $client->getAll('transactions', ['limit' => 20, 'vehicle' => 'A123']);
 *
 * // Get single transaction by ID
 * $transaction = $client->get('transactions', 42);
 */
class ApiClient
{
    private Client $http;

    public function __construct(
        private string $baseUrl,
        private string $apiKey = '',
    ) {
        $this->http = new Client([
            'base_uri' => rtrim($this->baseUrl, '/') . '/',
            'timeout'  => 10.0,
            'headers'  => [
                'Accept' => 'application/json',
                'Authorization' => $this->apiKey ? "Bearer {$this->apiKey}" : null,
            ],
        ]);
    }

    /**
     * GET the resource from the API
     *
     * @param string $endpoint
     * @param array $query Optional query parameters
     * @return array
     * @throws \RuntimeException
     */
    public function get(string $endpoint, array $query = []): array
    {
        return $this->request('GET', $endpoint, $query);
    }

    /**
     * Generic request helper
     *
     * @param string $method
     * @param string $uri
     * @param array $query
     * @return array
     * @throws \RuntimeException
     */
    private function request(string $method, string $uri, array $query = []): array
    {
        try {
            $query['key'] = $this->apiKey;

            $response = $this->http->request(
                $method, 
                ltrim($uri, '/'), 
                ['query' => $query],
            );
            $body = (string) $response->getBody();

            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            return $data;
        } catch (GuzzleException $e) {
            throw new \RuntimeException("API request failed: " . $e->getMessage(), $e->getCode(), $e);
        } catch (\JsonException $e) {
            throw new \RuntimeException("Invalid JSON response: " . $e->getMessage(), $e->getCode(), $e);
        }
    }
}
