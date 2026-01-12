<?php

declare(strict_types=1);

namespace DeployerPHP\Services\Do;

use DigitalOceanV2\Exception\ResourceNotFoundException;

/**
 * DigitalOcean DNS record service.
 *
 * Handles DNS record CRUD operations.
 */
class DoDnsService extends BaseDoService
{
    /** @var array<int, string> Supported DNS record types */
    public const RECORD_TYPES = ['A', 'AAAA', 'CNAME'];

    //
    // Record retrieval
    // ----

    /**
     * List DNS records for a domain.
     *
     * @param string $domain Domain name
     * @param string|null $type Optional record type filter
     *
     * @return array<int, array{id: int, type: string, name: string, data: string|null, ttl: int}>
     */
    public function listRecords(string $domain, ?string $type = null): array
    {
        $client = $this->getAPI();

        try {
            $recordApi = $client->domainRecord();
            $records = $recordApi->getAll($domain);

            $result = [];
            foreach ($records as $record) {
                // Filter by type if specified
                if (null !== $type && $record->type !== $type) {
                    continue;
                }

                $result[] = [
                    'id' => $record->id,
                    'type' => $record->type,
                    'name' => $record->name,
                    'data' => $record->data,
                    'ttl' => $record->ttl,
                ];
            }

            return $result;
        } catch (\Throwable $e) {
            throw new \RuntimeException("Failed to list DNS records for '{$domain}': " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Find a specific DNS record by type and name.
     *
     * @param string $domain Domain name
     * @param string $type Record type
     * @param string $name Record name (use "@" for root)
     *
     * @return array{id: int, type: string, name: string, data: string|null, ttl: int}|null
     */
    public function findRecord(string $domain, string $type, string $name): ?array
    {
        $records = $this->listRecords($domain, $type);

        foreach ($records as $record) {
            if ($record['name'] === $name) {
                return $record;
            }
        }

        return null;
    }

    //
    // Record creation/update
    // ----

    /**
     * Create a new DNS record.
     *
     * @param string $domain Domain name
     * @param string $type Record type
     * @param string $name Record name (use "@" for root)
     * @param string $data Record value/data
     * @param int $ttl TTL in seconds
     *
     * @return int The new record ID
     */
    public function createRecord(
        string $domain,
        string $type,
        string $name,
        string $data,
        int $ttl = 1800,
    ): int {
        $client = $this->getAPI();

        try {
            $recordApi = $client->domainRecord();
            $record = $recordApi->create(
                $domain,
                $type,
                $name,
                $data,
                null,
                null,
                null,
                null,
                null,
                $ttl
            );

            return $record->id;
        } catch (\Throwable $e) {
            throw new \RuntimeException("Failed to create DNS record: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Update an existing DNS record.
     *
     * @param string $domain Domain name
     * @param int $recordId Record ID to update
     * @param string $type Record type
     * @param string $name Record name
     * @param string $data Record value/data
     * @param int $ttl TTL in seconds
     */
    public function updateRecord(
        string $domain,
        int $recordId,
        string $type,
        string $name,
        string $data,
        int $ttl = 1800,
    ): void {
        $client = $this->getAPI();

        try {
            $recordApi = $client->domainRecord();
            $recordApi->update(
                $domain,
                $recordId,
                $name,
                $data,
                null,
                null,
                null,
                null,
                null,
                $ttl
            );
        } catch (\Throwable $e) {
            throw new \RuntimeException("Failed to update DNS record: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete a DNS record.
     *
     * @param string $domain Domain name
     * @param int $recordId Record ID to delete
     */
    public function deleteRecord(string $domain, int $recordId): void
    {
        $client = $this->getAPI();

        try {
            $recordApi = $client->domainRecord();
            $recordApi->remove($domain, $recordId);
        } catch (ResourceNotFoundException) {
            // Already deleted - silently succeed
            return;
        } catch (\Throwable $e) {
            throw new \RuntimeException("Failed to delete DNS record: " . $e->getMessage(), 0, $e);
        }
    }

    //
    // Upsert operation
    // ----

    /**
     * Create or update a DNS record (upsert).
     *
     * @param string $domain Domain name
     * @param string $type Record type
     * @param string $name Record name (use "@" for root)
     * @param string $data Record value/data
     * @param int $ttl TTL in seconds
     *
     * @return array{action: 'created'|'updated', id: int}
     */
    public function setRecord(
        string $domain,
        string $type,
        string $name,
        string $data,
        int $ttl = 1800,
    ): array {
        $existing = $this->findRecord($domain, $type, $name);

        if (null !== $existing) {
            $this->updateRecord(
                $domain,
                $existing['id'],
                $type,
                $name,
                $data,
                $ttl
            );

            return ['action' => 'updated', 'id' => $existing['id']];
        }

        $id = $this->createRecord(
            $domain,
            $type,
            $name,
            $data,
            $ttl
        );

        return ['action' => 'created', 'id' => $id];
    }
}
