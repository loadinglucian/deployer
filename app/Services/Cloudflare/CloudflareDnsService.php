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
    /** @var array<int, string> Supported DNS record types */
    public const RECORD_TYPES = ['A', 'AAAA', 'CNAME'];

    /**
     * Record types that support proxying (orange cloud).
     */
    public const PROXIABLE_TYPES = ['A', 'AAAA', 'CNAME'];

    /**
     * List DNS records in a zone.
     *
     * Note: Returns up to 100 records matching the filters. For zones with
     * many records of the same type/name, use filters to narrow results.
     *
     * @param string      $zoneId Zone ID
     * @param string|null $type   Filter by record type
     * @param string|null $name   Filter by record name
     *
     * @return array<int, array{id: string, type: string, name: string, content: string, ttl: int, proxied: bool}>
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

        /** @var array<int, array{id: string, type: string, name: string, content: string, ttl: int, proxied?: bool}> $results */
        $results = $response['result'] ?? [];

        foreach ($results as $record) {
            $records[] = [
                'id' => $record['id'],
                'type' => $record['type'],
                'name' => $record['name'],
                'content' => $record['content'],
                'ttl' => $record['ttl'],
                'proxied' => $record['proxied'] ?? false,
            ];
        }

        return $records;
    }

    /**
     * Find existing record by type and name.
     *
     * @return array{id: string, type: string, name: string, content: string, ttl: int, proxied: bool}|null
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
     * @param string $zoneId  Zone ID
     * @param string $type    Record type (A, AAAA, CNAME)
     * @param string $name    Record name (full domain name)
     * @param string $content Record value
     * @param int    $ttl     TTL in seconds (1 = auto when proxied)
     * @param bool   $proxied Enable Cloudflare proxy (orange cloud)
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
        bool $proxied = false
    ): string {
        $data = [
            'type' => strtoupper($type),
            'name' => $name,
            'content' => $content,
            'ttl' => $ttl,
            'proxied' => $proxied,
        ];

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
     * @param string $zoneId   Zone ID
     * @param string $recordId Record ID
     * @param string $type     Record type
     * @param string $name     Record name
     * @param string $content  Record value
     * @param int    $ttl      TTL in seconds
     * @param bool   $proxied  Enable proxy
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
        bool $proxied = false
    ): void {
        $data = [
            'type' => strtoupper($type),
            'name' => $name,
            'content' => $content,
            'ttl' => $ttl,
            'proxied' => $proxied,
        ];

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
     * @param string $zoneId  Zone ID
     * @param string $type    Record type
     * @param string $name    Record name (full domain name)
     * @param string $content Record value
     * @param int    $ttl     TTL in seconds
     * @param bool   $proxied Enable proxy
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
        bool $proxied = false
    ): array {
        $existing = $this->findRecord($zoneId, $type, $name);

        if (null !== $existing) {
            $this->updateRecord($zoneId, $existing['id'], $type, $name, $content, $ttl, $proxied);

            return ['action' => 'updated', 'id' => $existing['id']];
        }

        $recordId = $this->createRecord($zoneId, $type, $name, $content, $ttl, $proxied);

        return ['action' => 'created', 'id' => $recordId];
    }
}
