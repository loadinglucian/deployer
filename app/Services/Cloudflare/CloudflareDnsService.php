<?php

declare(strict_types=1);

namespace DeployerPHP\Services\Cloudflare;

/**
 * Cloudflare DNS record management service.
 *
 * Handles DNS record CRUD operations.
 */
class CloudflareDnsService extends BaseCloudflareService
{
    /**
     * Supported DNS record types.
     *
     * Note: SRV records require a nested `data` object structure with additional
     * fields (weight, port, target) which this implementation doesn't support.
     */
    public const RECORD_TYPES = ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS', 'CAA'];

    /**
     * Record types that support proxying (orange cloud).
     */
    public const PROXIABLE_TYPES = ['A', 'AAAA', 'CNAME'];

    /**
     * List DNS records in a zone.
     *
     * @param string      $zoneId Zone ID
     * @param string|null $type   Filter by record type
     * @param string|null $name   Filter by record name
     *
     * @return array<int, array{id: string, type: string, name: string, content: string, ttl: int, proxied: bool, priority?: int}>
     */
    public function listRecords(string $zoneId, ?string $type = null, ?string $name = null): array
    {
        $query = ['per_page' => 100];

        if (null !== $type) {
            $query['type'] = strtoupper($type);
        }

        if (null !== $name) {
            $query['name'] = $name;
        }

        $response = $this->request('GET', "/zones/{$zoneId}/dns_records", [
            'query' => $query,
        ]);

        $records = [];

        /** @var array<int, array{id: string, type: string, name: string, content: string, ttl: int, proxied?: bool, priority?: int}> $results */
        $results = $response['result'] ?? [];

        foreach ($results as $record) {
            $item = [
                'id' => $record['id'],
                'type' => $record['type'],
                'name' => $record['name'],
                'content' => $record['content'],
                'ttl' => $record['ttl'],
                'proxied' => $record['proxied'] ?? false,
            ];

            if (isset($record['priority'])) {
                $item['priority'] = $record['priority'];
            }

            $records[] = $item;
        }

        return $records;
    }

    /**
     * Find existing record by type and name.
     *
     * @return array{id: string, type: string, name: string, content: string, ttl: int, proxied: bool, priority?: int}|null
     */
    public function findRecord(string $zoneId, string $type, string $name): ?array
    {
        $records = $this->listRecords($zoneId, $type, $name);

        foreach ($records as $record) {
            if ($record['type'] === strtoupper($type) && $record['name'] === $name) {
                return $record;
            }
        }

        return null;
    }

    /**
     * Create a new DNS record.
     *
     * @param string   $zoneId   Zone ID
     * @param string   $type     Record type (A, AAAA, CNAME, MX, TXT, etc.)
     * @param string   $name     Record name (full domain name)
     * @param string   $content  Record value
     * @param int      $ttl      TTL in seconds (1 = auto when proxied)
     * @param bool     $proxied  Enable Cloudflare proxy (orange cloud)
     * @param int|null $priority MX/SRV priority
     *
     * @return string Created record ID
     *
     * @throws \RuntimeException On API error
     */
    public function createRecord(
        string $zoneId,
        string $type,
        string $name,
        string $content,
        int $ttl = 1,
        bool $proxied = false,
        ?int $priority = null
    ): string {
        $data = [
            'type' => strtoupper($type),
            'name' => $name,
            'content' => $content,
            'ttl' => $ttl,
        ];

        // Only include proxied for types that support it (API returns error 9004 otherwise)
        if (in_array(strtoupper($type), self::PROXIABLE_TYPES, true)) {
            $data['proxied'] = $proxied;
        }

        if (null !== $priority && 'MX' === strtoupper($type)) {
            $data['priority'] = $priority;
        }

        $response = $this->request('POST', "/zones/{$zoneId}/dns_records", [
            'json' => $data,
        ]);

        /** @var array{id: string} $result */
        $result = $response['result'];

        return $result['id'];
    }

    /**
     * Update an existing DNS record.
     *
     * @param string   $zoneId   Zone ID
     * @param string   $recordId Record ID
     * @param string   $type     Record type
     * @param string   $name     Record name
     * @param string   $content  Record value
     * @param int      $ttl      TTL in seconds
     * @param bool     $proxied  Enable proxy
     * @param int|null $priority MX/SRV priority
     *
     * @throws \RuntimeException On API error
     */
    public function updateRecord(
        string $zoneId,
        string $recordId,
        string $type,
        string $name,
        string $content,
        int $ttl = 1,
        bool $proxied = false,
        ?int $priority = null
    ): void {
        $data = [
            'type' => strtoupper($type),
            'name' => $name,
            'content' => $content,
            'ttl' => $ttl,
        ];

        // Only include proxied for types that support it (API returns error 9004 otherwise)
        if (in_array(strtoupper($type), self::PROXIABLE_TYPES, true)) {
            $data['proxied'] = $proxied;
        }

        if (null !== $priority && 'MX' === strtoupper($type)) {
            $data['priority'] = $priority;
        }

        $this->request('PUT', "/zones/{$zoneId}/dns_records/{$recordId}", [
            'json' => $data,
        ]);
    }

    /**
     * Delete a DNS record.
     *
     * @param string $zoneId   Zone ID
     * @param string $recordId Record ID
     *
     * @throws \RuntimeException On API error
     */
    public function deleteRecord(string $zoneId, string $recordId): void
    {
        $this->request('DELETE', "/zones/{$zoneId}/dns_records/{$recordId}");
    }

    /**
     * Create or update a DNS record (upsert).
     *
     * @param string   $zoneId   Zone ID
     * @param string   $type     Record type
     * @param string   $name     Record name (full domain name)
     * @param string   $content  Record value
     * @param int      $ttl      TTL in seconds
     * @param bool     $proxied  Enable proxy
     * @param int|null $priority MX/SRV priority
     *
     * @return array{action: 'created'|'updated', id: string}
     *
     * @throws \RuntimeException On API error
     */
    public function setRecord(
        string $zoneId,
        string $type,
        string $name,
        string $content,
        int $ttl = 1,
        bool $proxied = false,
        ?int $priority = null
    ): array {
        $existing = $this->findRecord($zoneId, $type, $name);

        if (null !== $existing) {
            $this->updateRecord($zoneId, $existing['id'], $type, $name, $content, $ttl, $proxied, $priority);

            return ['action' => 'updated', 'id' => $existing['id']];
        }

        $recordId = $this->createRecord($zoneId, $type, $name, $content, $ttl, $proxied, $priority);

        return ['action' => 'created', 'id' => $recordId];
    }
}
