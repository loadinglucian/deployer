<?php

declare(strict_types=1);

namespace DeployerPHP\Services\Cf;

/**
 * Cloudflare zone management service.
 *
 * Handles zone lookup with domain-to-ID resolution and caching.
 */
class CfZoneService extends BaseCfService
{
    /** @var array<string, string> Zone name to ID cache */
    private array $zoneCache = [];

    /**
     * Get zone ID by domain name.
     *
     * Supports both full zone name (example.com) and direct zone ID input.
     *
     * @param string $zoneOrDomain Zone name or zone ID
     *
     * @return string Zone ID
     *
     * @throws \RuntimeException If zone not found
     */
    public function getZoneId(string $zoneOrDomain): string
    {
        // If it looks like a zone ID (32-char hex), return as-is
        if (1 === preg_match('/^[a-f0-9]{32}$/i', $zoneOrDomain)) {
            return $zoneOrDomain;
        }

        // Check cache
        if (isset($this->zoneCache[$zoneOrDomain])) {
            return $this->zoneCache[$zoneOrDomain];
        }

        // Query API
        $response = $this->request('GET', '/zones', [
            'query' => ['name' => $zoneOrDomain],
        ]);

        /** @var array<int, array{id: string, name: string}> $zones */
        $zones = $response['result'] ?? [];

        if (0 === count($zones)) {
            throw new \RuntimeException("Zone not found: '{$zoneOrDomain}'. Ensure the domain is added to your Cloudflare account.");
        }

        $zoneId = $zones[0]['id'];
        $this->zoneCache[$zoneOrDomain] = $zoneId;

        return $zoneId;
    }

    /**
     * Get zone name by zone ID.
     *
     * @param string $zoneId Zone ID
     *
     * @return string Zone name
     *
     * @throws \RuntimeException If zone not found
     */
    public function getZoneName(string $zoneId): string
    {
        // Check cache (reverse lookup)
        $zoneName = array_search($zoneId, $this->zoneCache, true);

        if (false !== $zoneName) {
            return $zoneName;
        }

        // Query API for specific zone
        $response = $this->request('GET', "/zones/{$zoneId}");

        /** @var array{name: string}|null $zone */
        $zone = $response['result'] ?? null;

        if (null === $zone) {
            throw new \RuntimeException("Zone not found: {$zoneId}");
        }

        $this->zoneCache[$zone['name']] = $zoneId;

        return $zone['name'];
    }

    /**
     * Get all zones in account.
     *
     * @return array<string, string> Zone name => zone ID
     */
    public function getZones(): array
    {
        $response = $this->request('GET', '/zones', [
            'query' => ['per_page' => 50],
        ]);

        $zones = [];

        /** @var array<int, array{id: string, name: string}> $results */
        $results = $response['result'] ?? [];

        foreach ($results as $zone) {
            $zones[$zone['name']] = $zone['id'];
            $this->zoneCache[$zone['name']] = $zone['id'];
        }

        return $zones;
    }

    /**
     * Clear zone cache.
     */
    public function clearCache(): void
    {
        $this->zoneCache = [];
    }
}
