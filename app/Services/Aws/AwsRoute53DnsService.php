<?php

declare(strict_types=1);

namespace DeployerPHP\Services\Aws;

/**
 * AWS Route53 DNS record service.
 *
 * Handles DNS record CRUD operations using Route53.
 */
class AwsRoute53DnsService extends BaseAwsService
{
    /** @var array<int, string> Supported DNS record types */
    public const RECORD_TYPES = ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS', 'SRV', 'CAA', 'PTR'];

    //
    // Record retrieval
    // ----

    /**
     * List DNS records for a hosted zone.
     *
     * @param string $zoneId Hosted zone ID
     * @param string|null $type Optional record type filter
     *
     * @return array<int, array{type: string, name: string, value: string, ttl: int}>
     */
    public function listRecords(string $zoneId, ?string $type = null): array
    {
        $route53 = $this->createRoute53Client();

        try {
            $params = ['HostedZoneId' => $zoneId];

            $records = [];
            $nextRecordName = null;
            $nextRecordType = null;

            do {
                if (null !== $nextRecordName) {
                    $params['StartRecordName'] = $nextRecordName;
                    $params['StartRecordType'] = $nextRecordType;
                }

                $result = $route53->listResourceRecordSets($params);
                /** @var array<int, mixed> $recordSets */
                $recordSets = $result->get('ResourceRecordSets') ?? [];

                /** @var array<string, mixed> $recordSet */
                foreach ($recordSets as $recordSet) {
                    /** @var string $recordType */
                    $recordType = (string) ($recordSet['Type'] ?? '');
                    /** @var string $recordName */
                    $recordName = (string) ($recordSet['Name'] ?? '');

                    // Filter by type if specified
                    if (null !== $type && $recordType !== $type) {
                        continue;
                    }

                    // Skip SOA records (system-managed)
                    if ('SOA' === $recordType) {
                        continue;
                    }

                    // Handle alias records (no ResourceRecords)
                    if (isset($recordSet['AliasTarget'])) {
                        /** @var array<string, mixed> $aliasTarget */
                        $aliasTarget = $recordSet['AliasTarget'];
                        /** @var string $aliasDnsName */
                        $aliasDnsName = (string) ($aliasTarget['DNSName'] ?? '');
                        $records[] = [
                            'type' => $recordType,
                            'name' => rtrim($recordName, '.'),
                            'value' => 'ALIAS: ' . $aliasDnsName,
                            'ttl' => 0,
                        ];

                        continue;
                    }

                    // Handle standard records
                    /** @var array<int, mixed> $resourceRecords */
                    $resourceRecords = $recordSet['ResourceRecords'] ?? [];
                    /** @var int $ttl */
                    $ttl = (int) ($recordSet['TTL'] ?? 300);
                    /** @var array<string, mixed> $resourceRecord */
                    foreach ($resourceRecords as $resourceRecord) {
                        /** @var string $recordValue */
                        $recordValue = (string) ($resourceRecord['Value'] ?? '');
                        $records[] = [
                            'type' => $recordType,
                            'name' => rtrim($recordName, '.'),
                            'value' => $recordValue,
                            'ttl' => $ttl,
                        ];
                    }
                }

                $nextRecordName = $result->get('NextRecordName');
                $nextRecordType = $result->get('NextRecordType');
            } while ($result->get('IsTruncated'));

            return $records;
        } catch (\Throwable $e) {
            throw new \RuntimeException("Failed to list DNS records: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Find a specific DNS record by type and name.
     *
     * @param string $zoneId Hosted zone ID
     * @param string $type Record type
     * @param string $name Record name (without trailing dot)
     *
     * @return array{type: string, name: string, value: string, ttl: int}|null
     */
    public function findRecord(string $zoneId, string $type, string $name): ?array
    {
        $route53 = $this->createRoute53Client();
        $fqdn = $this->ensureTrailingDot($name);

        try {
            $result = $route53->listResourceRecordSets([
                'HostedZoneId' => $zoneId,
                'StartRecordName' => $fqdn,
                'StartRecordType' => $type,
                'MaxItems' => '1',
            ]);

            /** @var array<int, mixed> $recordSets */
            $recordSets = $result->get('ResourceRecordSets') ?? [];

            /** @var array<string, mixed> $recordSet */
            foreach ($recordSets as $recordSet) {
                /** @var string $recordType */
                $recordType = (string) ($recordSet['Type'] ?? '');
                /** @var string $recordName */
                $recordName = (string) ($recordSet['Name'] ?? '');

                if ($recordType === $type && $recordName === $fqdn) {
                    // Handle alias records
                    if (isset($recordSet['AliasTarget'])) {
                        /** @var array<string, mixed> $aliasTarget */
                        $aliasTarget = $recordSet['AliasTarget'];
                        /** @var string $aliasDnsName */
                        $aliasDnsName = (string) ($aliasTarget['DNSName'] ?? '');
                        return [
                            'type' => $recordType,
                            'name' => rtrim($recordName, '.'),
                            'value' => 'ALIAS: ' . $aliasDnsName,
                            'ttl' => 0,
                        ];
                    }

                    // Return first resource record value
                    /** @var array<int, mixed> $resourceRecords */
                    $resourceRecords = $recordSet['ResourceRecords'] ?? [];
                    if ([] !== $resourceRecords) {
                        /** @var array<string, mixed> $firstRecord */
                        $firstRecord = $resourceRecords[0];
                        /** @var string $recordValue */
                        $recordValue = (string) ($firstRecord['Value'] ?? '');
                        /** @var int $ttl */
                        $ttl = (int) ($recordSet['TTL'] ?? 300);
                        return [
                            'type' => $recordType,
                            'name' => rtrim($recordName, '.'),
                            'value' => $recordValue,
                            'ttl' => $ttl,
                        ];
                    }
                }
            }

            return null;
        } catch (\Throwable $e) {
            throw new \RuntimeException("Failed to find DNS record: " . $e->getMessage(), 0, $e);
        }
    }

    //
    // Record creation/update
    // ----

    /**
     * Create or update a DNS record (upsert).
     *
     * @param string $zoneId Hosted zone ID
     * @param string $type Record type
     * @param string $name Record name (without trailing dot)
     * @param string $value Record value
     * @param int $ttl TTL in seconds
     *
     * @return array{action: 'upserted'}
     */
    public function setRecord(
        string $zoneId,
        string $type,
        string $name,
        string $value,
        int $ttl = 300,
    ): array {
        $route53 = $this->createRoute53Client();
        $fqdn = $this->ensureTrailingDot($name);

        // Format value based on record type
        $formattedValue = $this->formatRecordValue($type, $value);

        try {
            $route53->changeResourceRecordSets([
                'HostedZoneId' => $zoneId,
                'ChangeBatch' => [
                    'Changes' => [
                        [
                            'Action' => 'UPSERT',
                            'ResourceRecordSet' => [
                                'Name' => $fqdn,
                                'Type' => $type,
                                'TTL' => $ttl,
                                'ResourceRecords' => [
                                    ['Value' => $formattedValue],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

            return ['action' => 'upserted'];
        } catch (\Throwable $e) {
            throw new \RuntimeException("Failed to set DNS record: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete a DNS record.
     *
     * @param string $zoneId Hosted zone ID
     * @param string $type Record type
     * @param string $name Record name (without trailing dot)
     * @param string $value Record value (required for deletion)
     * @param int $ttl TTL (required for deletion)
     */
    public function deleteRecord(
        string $zoneId,
        string $type,
        string $name,
        string $value,
        int $ttl,
    ): void {
        $route53 = $this->createRoute53Client();
        $fqdn = $this->ensureTrailingDot($name);

        try {
            $route53->changeResourceRecordSets([
                'HostedZoneId' => $zoneId,
                'ChangeBatch' => [
                    'Changes' => [
                        [
                            'Action' => 'DELETE',
                            'ResourceRecordSet' => [
                                'Name' => $fqdn,
                                'Type' => $type,
                                'TTL' => $ttl,
                                'ResourceRecords' => [
                                    ['Value' => $value],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            throw new \RuntimeException("Failed to delete DNS record: " . $e->getMessage(), 0, $e);
        }
    }

    //
    // Private helpers
    // ----

    /**
     * Ensure a domain name has a trailing dot.
     */
    private function ensureTrailingDot(string $name): string
    {
        return str_ends_with($name, '.') ? $name : $name . '.';
    }

    /**
     * Format record value based on type.
     *
     * TXT records need to be quoted, CNAME/MX need trailing dots, etc.
     */
    private function formatRecordValue(string $type, string $value): string
    {
        return match ($type) {
            'TXT' => $this->formatTxtValue($value),
            'CNAME', 'NS', 'PTR' => $this->ensureTrailingDot($value),
            'MX' => $this->formatMxValue($value),
            'SRV' => $this->formatSrvValue($value),
            'CAA' => $this->formatCaaValue($value),
            default => $value,
        };
    }

    /**
     * Format TXT record value with quotes.
     */
    private function formatTxtValue(string $value): string
    {
        // If already quoted, return as-is
        if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
            return $value;
        }

        // Escape internal quotes and wrap
        $escaped = str_replace('"', '\\"', $value);

        return "\"{$escaped}\"";
    }

    /**
     * Format MX record value (priority + hostname with trailing dot).
     */
    private function formatMxValue(string $value): string
    {
        // If it already has a priority, just ensure trailing dot on hostname
        if (preg_match('/^(\d+)\s+(.+)$/', $value, $matches)) {
            return $matches[1] . ' ' . $this->ensureTrailingDot($matches[2]);
        }

        // Assume it's just a hostname, add default priority
        return '10 ' . $this->ensureTrailingDot($value);
    }

    /**
     * Format SRV record value (priority weight port target with trailing dot).
     */
    private function formatSrvValue(string $value): string
    {
        // SRV format: priority weight port target
        if (preg_match('/^(\d+)\s+(\d+)\s+(\d+)\s+(.+)$/', $value, $matches)) {
            return $matches[1] . ' ' . $matches[2] . ' ' . $matches[3] . ' ' . $this->ensureTrailingDot($matches[4]);
        }

        return $value;
    }

    /**
     * Format CAA record value.
     */
    private function formatCaaValue(string $value): string
    {
        // CAA format: flags tag "value"
        // If it looks complete, return as-is
        if (preg_match('/^\d+\s+(issue|issuewild|iodef)\s+".+"$/', $value)) {
            return $value;
        }

        // If just a domain (like letsencrypt.org), format it properly
        if (!str_contains($value, ' ')) {
            return '0 issue "' . $value . '"';
        }

        return $value;
    }
}
