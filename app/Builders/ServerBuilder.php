<?php

declare(strict_types=1);

namespace DeployerPHP\Builders;

use DeployerPHP\DTOs\ServerDTO;

/**
 * Builder for ServerDTO - centralizes all ServerDTO instantiation.
 *
 * Replaces ServerDTO::withInfo() for copy-with-modification patterns.
 */
final class ServerBuilder
{
    private string $name = '';
    private string $host = '';
    private int $port = 22;
    private string $username = 'root';
    private ?string $privateKeyPath = null;
    private ?string $provider = null;
    private ?int $dropletId = null;
    private ?string $instanceId = null;
    /** @var array<string, mixed>|null */
    private ?array $info = null;

    private function __construct()
    {
    }

    /**
     * Create a new ServerBuilder for fresh creation.
     */
    public static function new(): self
    {
        return new self();
    }

    /**
     * Create a ServerBuilder from an existing ServerDTO.
     */
    public static function from(ServerDTO $dto): self
    {
        return (new self())
            ->name($dto->name)
            ->host($dto->host)
            ->port($dto->port)
            ->username($dto->username)
            ->privateKeyPath($dto->privateKeyPath)
            ->provider($dto->provider)
            ->dropletId($dto->dropletId)
            ->instanceId($dto->instanceId)
            ->info($dto->info);
    }

    /**
     * Create a ServerBuilder from storage data.
     *
     * Note: `info` is transient and not stored, so it's not hydrated here.
     *
     * @param array<string, mixed> $data
     */
    public static function fromStorage(array $data): self
    {
        $name = $data['name'] ?? '';
        $host = $data['host'] ?? '';
        $port = $data['port'] ?? 22;
        $username = $data['username'] ?? 'root';
        $privateKeyPath = $data['privateKeyPath'] ?? null;
        $provider = $data['provider'] ?? null;
        $dropletId = $data['dropletId'] ?? null;
        $instanceId = $data['instanceId'] ?? null;

        return (new self())
            ->name(is_string($name) ? $name : '')
            ->host(is_string($host) ? $host : '')
            ->port(is_int($port) ? $port : 22)
            ->username(is_string($username) ? $username : 'root')
            ->privateKeyPath(is_string($privateKeyPath) ? $privateKeyPath : null)
            ->provider(is_string($provider) ? $provider : null)
            ->dropletId(is_int($dropletId) ? $dropletId : null)
            ->instanceId(is_string($instanceId) ? $instanceId : null);
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function host(string $host): self
    {
        $this->host = $host;

        return $this;
    }

    public function port(int $port): self
    {
        $this->port = $port;

        return $this;
    }

    public function username(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function privateKeyPath(?string $privateKeyPath): self
    {
        $this->privateKeyPath = $privateKeyPath;

        return $this;
    }

    public function provider(?string $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function dropletId(?int $dropletId): self
    {
        $this->dropletId = $dropletId;

        return $this;
    }

    public function instanceId(?string $instanceId): self
    {
        $this->instanceId = $instanceId;

        return $this;
    }

    /**
     * @param array<string, mixed>|null $info
     */
    public function info(?array $info): self
    {
        $this->info = $info;

        return $this;
    }

    /**
     * Build the ServerDTO.
     *
     * @throws \RuntimeException If required fields are missing.
     */
    public function build(): ServerDTO
    {
        if ('' === $this->name) {
            throw new \RuntimeException('ServerDTO requires a name');
        }
        if ('' === $this->host) {
            throw new \RuntimeException('ServerDTO requires a host');
        }

        return new ServerDTO(
            name: $this->name,
            host: $this->host,
            port: $this->port,
            username: $this->username,
            privateKeyPath: $this->privateKeyPath,
            provider: $this->provider,
            dropletId: $this->dropletId,
            instanceId: $this->instanceId,
            info: $this->info,
        );
    }
}
