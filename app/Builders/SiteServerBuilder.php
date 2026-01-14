<?php

declare(strict_types=1);

namespace DeployerPHP\Builders;

use DeployerPHP\DTOs\ServerDTO;
use DeployerPHP\DTOs\SiteDTO;
use DeployerPHP\DTOs\SiteServerDTO;

/**
 * Builder for SiteServerDTO - centralizes all SiteServerDTO instantiation.
 *
 * This is a composition wrapper for SiteDTO + ServerDTO, used for playbook execution.
 * No `from()` or `fromStorage()` since it's never modified or stored directly.
 */
final class SiteServerBuilder
{
    private ?SiteDTO $site = null;
    private ?ServerDTO $server = null;

    private function __construct()
    {
    }

    /**
     * Create a new SiteServerBuilder.
     */
    public static function new(): self
    {
        return new self();
    }

    public function site(SiteDTO $site): self
    {
        $this->site = $site;

        return $this;
    }

    public function server(ServerDTO $server): self
    {
        $this->server = $server;

        return $this;
    }

    /**
     * Build the SiteServerDTO.
     *
     * @throws \RuntimeException If required fields are missing.
     */
    public function build(): SiteServerDTO
    {
        if (null === $this->site) {
            throw new \RuntimeException('SiteServerDTO requires a site');
        }
        if (null === $this->server) {
            throw new \RuntimeException('SiteServerDTO requires a server');
        }

        return new SiteServerDTO(
            site: $this->site,
            server: $this->server,
        );
    }
}
