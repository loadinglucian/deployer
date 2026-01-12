<?php

declare(strict_types=1);

namespace DeployerPHP\Services;

use DeployerPHP\Services\Cf\CfDnsService;
use DeployerPHP\Services\Cf\CfZoneService;

/**
 * Cloudflare API facade service.
 *
 * Provides access to specialized Cloudflare services through a unified interface.
 */
class CfService
{
    public function __construct(
        public readonly CfZoneService $zone,
        public readonly CfDnsService $dns,
    ) {
    }

    //
    // API Initialization
    // ----

    /**
     * Initialize the Cloudflare API with token and verify authentication.
     *
     * Must be called before making any API calls.
     *
     * @param string $apiToken The Cloudflare API token
     *
     * @throws \RuntimeException If authentication fails
     */
    public function initialize(string $apiToken): void
    {
        // Distribute token to sub-services
        $this->zone->setApiToken($apiToken);
        $this->dns->setApiToken($apiToken);

        // Verify authentication
        $this->verifyAuthentication();
    }

    /**
     * Verify API token by making a lightweight API call.
     *
     * @throws \RuntimeException If authentication fails
     */
    private function verifyAuthentication(): void
    {
        try {
            // Use zone listing as auth verification (lightweight call)
            $this->zone->getZones();
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to authenticate with Cloudflare API: ' . $e->getMessage(), 0, $e);
        }
    }
}
