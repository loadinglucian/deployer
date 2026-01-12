<?php

declare(strict_types=1);

namespace DeployerPHP\Services\Do;

use DigitalOceanV2\Exception\ResourceNotFoundException;

/**
 * DigitalOcean domain service.
 *
 * Handles domain (zone) lookup and validation.
 */
class DoDomainService extends BaseDoService
{
    /** @var array<string, string> Cache of domain name => domain name (DO uses names as IDs) */
    private array $domainCache = [];

    //
    // Domain operations
    // ----

    /**
     * Get all domains in the account.
     *
     * @return array<string, string> Array of domain name => domain name
     */
    public function getDomains(): array
    {
        $client = $this->getAPI();

        try {
            $domainApi = $client->domain();
            $domains = $domainApi->getAll();

            $options = [];
            foreach ($domains as $domain) {
                $options[$domain->name] = $domain->name;
                $this->domainCache[$domain->name] = $domain->name;
            }

            asort($options);

            return $options;
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to fetch domains: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Validate a domain exists in the account.
     *
     * @param string $domain Domain name to validate
     *
     * @return string The validated domain name
     *
     * @throws \RuntimeException If domain not found
     */
    public function getDomainName(string $domain): string
    {
        // Check cache first
        if (isset($this->domainCache[$domain])) {
            return $this->domainCache[$domain];
        }

        $client = $this->getAPI();

        try {
            $domainApi = $client->domain();
            $domainEntity = $domainApi->getByName($domain);

            $this->domainCache[$domainEntity->name] = $domainEntity->name;

            return $domainEntity->name;
        } catch (ResourceNotFoundException) {
            throw new \RuntimeException("Domain '{$domain}' not found in your DigitalOcean account");
        } catch (\Throwable $e) {
            throw new \RuntimeException("Failed to validate domain '{$domain}': " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Clear the domain cache.
     */
    public function clearCache(): void
    {
        $this->domainCache = [];
    }
}
