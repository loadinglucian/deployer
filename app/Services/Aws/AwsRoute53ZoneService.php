<?php

declare(strict_types=1);

namespace DeployerPHP\Services\Aws;

/**
 * AWS Route53 hosted zone service.
 *
 * Handles hosted zone lookup and validation.
 */
class AwsRoute53ZoneService extends BaseAwsService
{
    /** @var array<string, string> Cache of zone ID => zone name */
    private array $zoneCache = [];

    //
    // Hosted Zone operations
    // ----

    /**
     * Get all hosted zones in the account.
     *
     * @return array<string, string> Array of zone ID => zone name
     */
    public function getHostedZones(): array
    {
        $route53 = $this->createRoute53Client();

        try {
            $result = $route53->listHostedZones();
            /** @var array<int, mixed> $zones */
            $zones = $result->get('HostedZones') ?? [];

            $options = [];
            /** @var array<string, mixed> $zone */
            foreach ($zones as $zone) {
                /** @var string $zoneId */
                $zoneId = (string) ($zone['Id'] ?? '');
                /** @var string $zoneName */
                $zoneName = (string) ($zone['Name'] ?? '');

                $cleanedZoneId = $this->cleanZoneId($zoneId);
                $cleanedZoneName = rtrim($zoneName, '.');

                $options[$cleanedZoneId] = $cleanedZoneName;
                $this->zoneCache[$cleanedZoneId] = $cleanedZoneName;
            }

            asort($options);

            return $options;
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to fetch hosted zones: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Resolve a hosted zone ID from a zone name or ID.
     *
     * Accepts either a zone ID (with or without /hostedzone/ prefix) or a domain name.
     *
     * @param string $zoneOrDomain Zone ID or domain name
     *
     * @return string The hosted zone ID (without prefix)
     *
     * @throws \RuntimeException If zone not found
     */
    public function getHostedZoneId(string $zoneOrDomain): string
    {
        // If it looks like a zone ID, clean and validate it
        if ($this->looksLikeZoneId($zoneOrDomain)) {
            $zoneId = $this->cleanZoneId($zoneOrDomain);

            // Validate the zone exists
            $this->getHostedZoneName($zoneId);

            return $zoneId;
        }

        // It's a domain name - look it up
        $zones = $this->getHostedZones();
        $domain = rtrim(strtolower($zoneOrDomain), '.');

        foreach ($zones as $zoneId => $zoneName) {
            if (strtolower($zoneName) === $domain) {
                return $zoneId;
            }
        }

        throw new \RuntimeException("Hosted zone for '{$zoneOrDomain}' not found in your AWS account");
    }

    /**
     * Get the hosted zone name from a zone ID.
     *
     * @param string $zoneId Zone ID (with or without prefix)
     *
     * @return string The zone name (without trailing dot)
     *
     * @throws \RuntimeException If zone not found
     */
    public function getHostedZoneName(string $zoneId): string
    {
        $zoneId = $this->cleanZoneId($zoneId);

        // Check cache first
        if (isset($this->zoneCache[$zoneId])) {
            return $this->zoneCache[$zoneId];
        }

        $route53 = $this->createRoute53Client();

        try {
            $result = $route53->getHostedZone([
                'Id' => $zoneId,
            ]);

            /** @var array<string, mixed> $hostedZone */
            $hostedZone = $result->get('HostedZone');
            /** @var string $zoneNameFromApi */
            $zoneNameFromApi = (string) ($hostedZone['Name'] ?? '');
            $zoneName = rtrim($zoneNameFromApi, '.');

            $this->zoneCache[$zoneId] = $zoneName;

            return $zoneName;
        } catch (\Throwable $e) {
            throw new \RuntimeException("Hosted zone '{$zoneId}' not found: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Clear the zone cache.
     */
    public function clearCache(): void
    {
        $this->zoneCache = [];
    }

    //
    // Private helpers
    // ----

    /**
     * Check if a string looks like a hosted zone ID.
     */
    private function looksLikeZoneId(string $input): bool
    {
        // Zone IDs are uppercase alphanumeric, typically 14-32 chars
        // They may be prefixed with /hostedzone/
        $cleaned = $this->cleanZoneId($input);

        return (bool) preg_match('/^[A-Z0-9]{14,32}$/', $cleaned);
    }

    /**
     * Clean a zone ID by removing the /hostedzone/ prefix.
     */
    private function cleanZoneId(string $zoneId): string
    {
        return preg_replace('#^/hostedzone/#', '', (string) $zoneId) ?? $zoneId;
    }
}
