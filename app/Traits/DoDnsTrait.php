<?php

declare(strict_types=1);

namespace DeployerPHP\Traits;

use DeployerPHP\Services\Do\DoDnsService;
use DeployerPHP\Services\DoService;
use DeployerPHP\Services\IoService;

/**
 * Reusable DigitalOcean DNS things.
 *
 * @property DoService $do
 * @property IoService $io
 */
trait DoDnsTrait
{
    // ----
    // Helpers
    // ----

    /**
     * Resolve and validate a domain exists in the account.
     *
     * @param string $domain Domain name to resolve
     *
     * @return string The validated domain name
     *
     * @throws \RuntimeException If domain not found
     */
    protected function resolveDoDomain(string $domain): string
    {
        return $this->io->promptSpin(
            fn () => $this->do->domain->getDomainName($domain),
            'Validating domain...'
        );
    }

    /**
     * Normalize record name.
     *
     * Converts "@" to the zone apex representation.
     * DigitalOcean uses "@" for the apex, so we just pass through.
     *
     * @param string $name Record name
     * @param string $domain Domain name (unused, kept for consistency)
     *
     * @return string Normalized record name
     */
    protected function normalizeDoRecordName(string $name, string $domain): string
    {
        // DigitalOcean uses "@" natively for the apex
        return $name;
    }

    // ----
    // Validation
    // ----

    /**
     * Validate domain input.
     *
     * @return string|null Error message if invalid, null if valid
     */
    protected function validateDoDomainInput(mixed $domain): ?string
    {
        if (!is_string($domain)) {
            return 'Domain must be a string';
        }

        if ('' === trim($domain)) {
            return 'Domain cannot be empty';
        }

        // RFC 1035: total domain length limit
        if (strlen($domain) > 253) {
            return 'Domain exceeds maximum length of 253 characters';
        }

        // RFC 1035: label length limit (63 chars per label)
        $labels = explode('.', $domain);
        foreach ($labels as $label) {
            if (strlen($label) > 63) {
                return 'Domain label exceeds maximum length of 63 characters';
            }
        }

        // Basic domain format validation
        if (!preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)*$/i', $domain)) {
            return 'Invalid domain format';
        }

        return null;
    }

    /**
     * Validate record type input.
     *
     * @return string|null Error message if invalid, null if valid
     */
    protected function validateDoRecordTypeInput(mixed $type): ?string
    {
        if (!is_string($type)) {
            return 'Record type must be a string';
        }

        $type = strtoupper(trim($type));

        if (!in_array($type, DoDnsService::RECORD_TYPES, true)) {
            return sprintf(
                "Invalid record type '%s'. Valid types: %s",
                $type,
                implode(', ', DoDnsService::RECORD_TYPES)
            );
        }

        return null;
    }

    /**
     * Validate record name input.
     *
     * @return string|null Error message if invalid, null if valid
     */
    protected function validateDoRecordNameInput(mixed $name): ?string
    {
        if (!is_string($name)) {
            return 'Record name must be a string';
        }

        if ('' === trim($name)) {
            return 'Record name cannot be empty';
        }

        // Allow "@" for root/apex
        if ('@' === $name) {
            return null;
        }

        // RFC 1035: total name length limit
        if (strlen($name) > 253) {
            return 'Record name exceeds maximum length of 253 characters';
        }

        // RFC 1035: label length limit (63 chars per label)
        $checkName = str_starts_with($name, '*.') ? substr($name, 2) : $name;
        $labels = explode('.', $checkName);
        foreach ($labels as $label) {
            if (strlen($label) > 63) {
                return 'Record name label exceeds maximum length of 63 characters';
            }
        }

        // Basic hostname validation (allows wildcards like *)
        if (!preg_match('/^(\*\.)?[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)*$/i', $name)) {
            return 'Invalid record name format. Use "@" for root or a valid hostname';
        }

        return null;
    }

    /**
     * Validate record value/data input.
     *
     * @return string|null Error message if invalid, null if valid
     */
    protected function validateDoRecordValueInput(mixed $value): ?string
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
    protected function validateDoTtlInput(mixed $ttl): ?string
    {
        if (!is_string($ttl) && !is_int($ttl)) {
            return 'TTL must be a number';
        }

        if (is_string($ttl) && !is_numeric($ttl)) {
            return 'TTL must be a number';
        }

        $ttlInt = is_int($ttl) ? $ttl : (int) $ttl;

        // DigitalOcean minimum TTL is 30 seconds
        if ($ttlInt < 30 || $ttlInt > 86400) {
            return 'TTL must be between 30 and 86400 seconds';
        }

        return null;
    }

    /**
     * Validate priority input (for MX/SRV records).
     *
     * @return string|null Error message if invalid, null if valid
     */
    protected function validateDoPriorityInput(mixed $priority): ?string
    {
        if (null === $priority || '' === $priority) {
            return null; // Optional
        }

        if (!is_string($priority) && !is_int($priority)) {
            return 'Priority must be a number';
        }

        if (is_string($priority) && !is_numeric($priority)) {
            return 'Priority must be a number';
        }

        $priorityInt = is_int($priority) ? $priority : (int) $priority;

        if ($priorityInt < 0 || $priorityInt > 65535) {
            return 'Priority must be between 0 and 65535';
        }

        return null;
    }

    /**
     * Validate port input (for SRV records).
     *
     * @return string|null Error message if invalid, null if valid
     */
    protected function validateDoPortInput(mixed $port): ?string
    {
        if (null === $port || '' === $port) {
            return null; // Optional
        }

        if (!is_string($port) && !is_int($port)) {
            return 'Port must be a number';
        }

        if (is_string($port) && !is_numeric($port)) {
            return 'Port must be a number';
        }

        $portInt = is_int($port) ? $port : (int) $port;

        if ($portInt < 0 || $portInt > 65535) {
            return 'Port must be between 0 and 65535';
        }

        return null;
    }

    /**
     * Validate weight input (for SRV records).
     *
     * @return string|null Error message if invalid, null if valid
     */
    protected function validateDoWeightInput(mixed $weight): ?string
    {
        if (null === $weight || '' === $weight) {
            return null; // Optional
        }

        if (!is_string($weight) && !is_int($weight)) {
            return 'Weight must be a number';
        }

        if (is_string($weight) && !is_numeric($weight)) {
            return 'Weight must be a number';
        }

        $weightInt = is_int($weight) ? $weight : (int) $weight;

        if ($weightInt < 0 || $weightInt > 65535) {
            return 'Weight must be between 0 and 65535';
        }

        return null;
    }

    /**
     * Validate flags input (for CAA records).
     *
     * @return string|null Error message if invalid, null if valid
     */
    protected function validateDoFlagsInput(mixed $flags): ?string
    {
        if (null === $flags || '' === $flags) {
            return null; // Optional
        }

        if (!is_string($flags) && !is_int($flags)) {
            return 'Flags must be a number';
        }

        if (is_string($flags) && !is_numeric($flags)) {
            return 'Flags must be a number';
        }

        $flagsInt = is_int($flags) ? $flags : (int) $flags;

        if ($flagsInt < 0 || $flagsInt > 255) {
            return 'Flags must be between 0 and 255';
        }

        return null;
    }

    /**
     * Validate tag input (for CAA records).
     *
     * @return string|null Error message if invalid, null if valid
     */
    protected function validateDoTagInput(mixed $tag): ?string
    {
        if (null === $tag || '' === $tag) {
            return null; // Optional
        }

        if (!is_string($tag)) {
            return 'Tag must be a string';
        }

        $validTags = ['issue', 'issuewild', 'iodef'];
        if (!in_array(strtolower($tag), $validTags, true)) {
            return sprintf("Invalid CAA tag '%s'. Valid tags: %s", $tag, implode(', ', $validTags));
        }

        return null;
    }
}
