<?php

declare(strict_types=1);

namespace DeployerPHP\Services\Cf;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Base class for Cloudflare API services.
 *
 * Provides common HTTP client management for all Cloudflare services.
 */
abstract class BaseCfService
{
    private const API_BASE = 'https://api.cloudflare.com/client/v4/';

    private ?Client $client = null;

    private ?string $apiToken = null;

    /**
     * Set the Cloudflare API token.
     */
    public function setApiToken(string $token): void
    {
        $this->apiToken = $token;
        $this->client = null;
    }

    /**
     * Get the configured Guzzle HTTP client.
     *
     * @throws \RuntimeException If token not configured
     */
    protected function getClient(): Client
    {
        if (null !== $this->client) {
            return $this->client;
        }

        if (null === $this->apiToken || '' === $this->apiToken) {
            throw new \RuntimeException('Cloudflare API token not configured. Call setApiToken() first.');
        }

        $this->client = new Client([
            'base_uri' => self::API_BASE,
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
            ],
        ]);

        return $this->client;
    }

    /**
     * Make an API request and handle the Cloudflare response format.
     *
     * @param string               $method   HTTP method
     * @param string               $endpoint API endpoint (relative to base)
     * @param array<string, mixed> $options  Guzzle options
     *
     * @return array<string, mixed> Response data
     *
     * @throws \RuntimeException On API error
     */
    protected function request(string $method, string $endpoint, array $options = []): array
    {
        try {
            $response = $this->getClient()->request($method, $endpoint, $options);

            /** @var array<string, mixed>|null $body */
            $body = json_decode((string) $response->getBody(), true);

            if (!is_array($body)) {
                throw new \RuntimeException('Invalid JSON response from Cloudflare API');
            }

            if (!($body['success'] ?? false)) {
                /** @var array<int, array{message?: string}> $errors */
                $errors = $body['errors'] ?? [['message' => 'Unknown error']];
                $errorMessages = array_map(fn (array $e): string => $e['message'] ?? 'Unknown', $errors);
                throw new \RuntimeException('Cloudflare API error: ' . implode(', ', $errorMessages));
            }

            return $body;
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Cloudflare API request failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
