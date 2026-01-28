<?php

declare(strict_types=1);

namespace DeployerPHP\Traits;

use DeployerPHP\Services\IoService;

/**
 * Shared DNS command functionality.
 *
 * Provides common methods for DNS record management commands
 * across all providers (AWS, DigitalOcean, Cloudflare).
 *
 * @property IoService $io
 */
trait DnsCommandTrait
{
    // ----
    // Prompts
    // ----

    /**
     * Get placeholder text for record value based on type.
     */
    protected function getValuePlaceholder(string $type): string
    {
        return match ($type) {
            'A' => '192.0.2.1',
            'AAAA' => '2001:db8::1',
            'CNAME' => 'target.example.com',
            default => '',
        };
    }

    /**
     * Get hint text for record value based on type.
     */
    protected function getValueHint(string $type): string
    {
        return match ($type) {
            'A' => 'IPv4 address',
            'AAAA' => 'IPv6 address',
            'CNAME' => 'Target hostname',
            default => 'Record value',
        };
    }

    /**
     * Confirm deletion with type-to-confirm and yes/no prompt.
     *
     * @param string $recordName The record name to confirm
     * @param bool   $forceSkip  Skip the type-to-confirm step
     *
     * @return bool|null True if confirmed, false if cancelled, null if validation failed
     */
    protected function confirmDnsDeletion(string $recordName, bool $forceSkip): ?bool
    {
        if (!$forceSkip) {
            $typedName = $this->io->promptText(
                label: "Type the record name '{$recordName}' to confirm deletion:",
                required: true
            );

            if ($typedName !== $recordName) {
                $this->nay('Record name does not match. Deletion cancelled.');

                return null;
            }
        }

        return $this->io->getBooleanOptionOrPrompt(
            'yes',
            fn (): bool => $this->io->promptConfirm(
                label: 'Are you absolutely sure?',
                default: false
            )
        );
    }

    // ----
    // Display
    // ----

    /**
     * Truncate long values for display.
     */
    protected function truncateValue(string $value, int $maxLength = 60): string
    {
        if (strlen($value) > $maxLength) {
            return substr($value, 0, $maxLength - 3) . '...';
        }

        return $value;
    }

    /**
     * Display DNS records in a formatted list.
     *
     * @param array<int, array{type: string, name: string, value: string, ttl: int|string}> $records
     */
    protected function displayDnsRecords(array $records): void
    {
        foreach ($records as $index => $record) {
            $this->displayDeets([
                'Type' => $record['type'],
                'Name' => $record['name'],
                'Value' => $this->truncateValue($record['value']),
                'TTL' => (string) $record['ttl'],
            ]);

            $this->out('───');
        }
    }
}
