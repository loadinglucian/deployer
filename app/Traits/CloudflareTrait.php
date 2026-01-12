<?php

declare(strict_types=1);

namespace DeployerPHP\Traits;

use DeployerPHP\Services\Cloudflare\CloudflareDnsService;
use DeployerPHP\Services\CloudflareService;
use DeployerPHP\Services\EnvService;
use DeployerPHP\Services\IoService;
use Symfony\Component\Console\Command\Command;

/**
 * Reusable Cloudflare things.
 *
 * @property CloudflareService $cloudflare
 * @property EnvService $env
 * @property IoService $io
 */
trait CloudflareTrait
{
    // ----
    // Helpers
    // ----

    //
    // API
    // ----

    /**
     * Initialize Cloudflare API with token from environment.
     *
     * Retrieves the Cloudflare API token from environment variables
     * (CLOUDFLARE_API_TOKEN or CF_API_TOKEN), configures the
     * Cloudflare service, and verifies authentication with a lightweight
     * API call. Displays error messages and exits on failure.
     *
     * @return int Command::SUCCESS on success, Command::FAILURE on error
     */
    protected function initializeCloudflareAPI(): int
    {
        try {
            /** @var string $apiToken */
            $apiToken = $this->env->get(['CLOUDFLARE_API_TOKEN', 'CF_API_TOKEN']);

            $this->io->promptSpin(
                fn () => $this->cloudflare->initialize($apiToken),
                'Initializing Cloudflare API...'
            );

            return Command::SUCCESS;
        } catch (\InvalidArgumentException) {
            $this->nay('Cloudflare API token not found in environment.');
            $this->nay('Set CLOUDFLARE_API_TOKEN or CF_API_TOKEN in your .env file.');

            return Command::FAILURE;
        } catch (\RuntimeException $e) {
            $this->nay($e->getMessage());
            $this->nay('Check that your Cloudflare API token is valid and has DNS edit permissions.');

            return Command::FAILURE;
        }
    }

    //
    // UI
    // ----

    /**
     * Resolve zone option - supports both zone name and zone ID.
     *
     * @param string $zoneOrDomain Zone name or zone ID
     *
     * @return string Zone ID
     *
     * @throws \RuntimeException If zone not found
     */
    protected function resolveZoneId(string $zoneOrDomain): string
    {
        return $this->io->promptSpin(
            fn () => $this->cloudflare->zone->getZoneId($zoneOrDomain),
            "Resolving zone '{$zoneOrDomain}'..."
        );
    }

    /**
     * Normalize record name - convert "@" to zone apex.
     *
     * @param string $name     Record name ("@" for root or subdomain)
     * @param string $zoneName Zone domain name
     *
     * @return string Fully qualified record name
     */
    protected function normalizeRecordName(string $name, string $zoneName): string
    {
        if ('@' === $name) {
            return $zoneName;
        }

        // If name already includes zone, return as-is
        if (str_ends_with($name, '.' . $zoneName) || $name === $zoneName) {
            return $name;
        }

        // Append zone to subdomain
        return $name . '.' . $zoneName;
    }

    // ----
    // Validation
    // ----

    /**
     * Validate zone input.
     *
     * @return string|null Error message if invalid, null if valid
     */
    protected function validateZoneInput(mixed $zone): ?string
    {
        if (!is_string($zone)) {
            return 'Zone must be a string';
        }

        if ('' === trim($zone)) {
            return 'Zone cannot be empty';
        }

        // Valid: zone ID (32-char hex) or domain name
        if (1 !== preg_match('/^[a-f0-9]{32}$/i', $zone) && 1 !== preg_match('/^[a-z0-9][a-z0-9\-\.]+\.[a-z]{2,}$/i', $zone)) {
            return "Invalid zone format: '{$zone}'. Use a domain name (example.com) or zone ID (32-char hex)";
        }

        return null;
    }

    /**
     * Validate record type input.
     *
     * @return string|null Error message if invalid, null if valid
     */
    protected function validateRecordTypeInput(mixed $type): ?string
    {
        if (!is_string($type)) {
            return 'Record type must be a string';
        }

        if ('' === trim($type)) {
            return 'Record type cannot be empty';
        }

        $validTypes = CloudflareDnsService::RECORD_TYPES;
        $upperType = strtoupper($type);

        if (!in_array($upperType, $validTypes, true)) {
            return "Invalid record type: '{$type}'. Valid types: " . implode(', ', $validTypes);
        }

        return null;
    }

    /**
     * Validate record name input.
     *
     * @return string|null Error message if invalid, null if valid
     */
    protected function validateRecordNameInput(mixed $name): ?string
    {
        if (!is_string($name)) {
            return 'Record name must be a string';
        }

        if ('' === trim($name)) {
            return 'Record name cannot be empty';
        }

        // Allow "@" for root, or valid hostname pattern
        if ('@' !== $name && 1 !== preg_match('/^[a-z0-9][a-z0-9\-\.\_]*$/i', $name)) {
            return "Invalid record name: '{$name}'. Use '@' for root or a valid hostname";
        }

        return null;
    }

    /**
     * Validate record value input.
     *
     * @return string|null Error message if invalid, null if valid
     */
    protected function validateRecordValueInput(mixed $value): ?string
    {
        if (!is_string($value)) {
            return 'Record value must be a string';
        }

        if ('' === trim($value)) {
            return 'Record value cannot be empty';
        }

        return null;
    }

    /**
     * Validate TTL input.
     *
     * @return string|null Error message if invalid, null if valid
     */
    protected function validateTtlInput(mixed $ttl): ?string
    {
        if (!is_string($ttl) && !is_int($ttl)) {
            return 'TTL must be a number';
        }

        $ttlInt = is_string($ttl) ? (int) $ttl : $ttl;

        if (1 !== $ttlInt && (60 > $ttlInt || 86400 < $ttlInt)) {
            return 'TTL must be 1 (auto) or between 60 and 86400 seconds';
        }

        return null;
    }

    /**
     * Validate MX priority input.
     *
     * @return string|null Error message if invalid, null if valid
     */
    protected function validatePriorityInput(mixed $priority): ?string
    {
        if (null === $priority || '' === $priority) {
            return null;
        }

        if (!is_string($priority) && !is_int($priority)) {
            return 'Priority must be a number';
        }

        $priorityInt = is_string($priority) ? (int) $priority : $priority;

        if (0 > $priorityInt || 65535 < $priorityInt) {
            return 'Priority must be between 0 and 65535';
        }

        return null;
    }
}
