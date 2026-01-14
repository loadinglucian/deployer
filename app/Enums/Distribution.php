<?php

declare(strict_types=1);

namespace DeployerPHP\Enums;

/**
 * Supported Linux distributions.
 *
 * Provides centralized distribution configuration and business logic.
 */
enum Distribution: string
{
    case UBUNTU = 'ubuntu';
    case DEBIAN = 'debian';

    // ----
    // Version Support
    // ----

    /**
     * Supported Ubuntu LTS versions.
     *
     * Ubuntu interim releases (e.g., 25.04) are not supported because
     * the Ondřej PHP PPA only publishes packages for LTS releases.
     *
     * @var array<string>
     */
    private const UBUNTU_LTS_VERSIONS = ['24.04', '26.04'];

    // ----
    // Codename Mappings
    // ----

    private const UBUNTU_CODENAMES = [
        '24.04' => 'Noble Numbat',
        '26.04' => 'TBD',
    ];

    private const DEBIAN_CODENAMES = [
        '12' => 'Bookworm',
        '13' => 'Trixie',
        '14' => 'Forky',
    ];

    // ----
    // Display Methods
    // ----

    /**
     * Get human-readable display name.
     */
    public function displayName(): string
    {
        return match ($this) {
            self::UBUNTU => 'Ubuntu',
            self::DEBIAN => 'Debian',
        };
    }

    /**
     * Get codename for a version.
     */
    public function codename(string $version): string
    {
        return match ($this) {
            self::UBUNTU => self::UBUNTU_CODENAMES[$version] ?? 'LTS',
            self::DEBIAN => self::DEBIAN_CODENAMES[$version] ?? 'Stable',
        };
    }

    /**
     * Format version for display.
     */
    public function formatVersion(string $version): string
    {
        $codename = $this->codename($version);

        return match ($this) {
            self::UBUNTU => "{$this->displayName()} {$version} LTS ({$codename})",
            self::DEBIAN => "{$this->displayName()} {$version} ({$codename})",
        };
    }

    // ----
    // Server Configuration
    // ----

    /**
     * Get default SSH username for this distribution.
     */
    public function defaultSshUsername(): string
    {
        return match ($this) {
            self::UBUNTU => 'ubuntu',
            self::DEBIAN => 'admin',
        };
    }

    // ----
    // Version Validation
    // ----

    /**
     * Check if a version is supported for this distribution.
     *
     * Ubuntu only supports LTS versions (24.04, 26.04).
     * Debian supports all stable versions.
     */
    public function isValidVersion(string $version): bool
    {
        return match ($this) {
            self::UBUNTU => in_array($version, self::UBUNTU_LTS_VERSIONS, true),
            self::DEBIAN => true,
        };
    }

    /**
     * Get supported versions for this distribution.
     *
     * @return array<string>
     */
    public function supportedVersions(): array
    {
        /** @var array<string> $versions */
        $versions = match ($this) {
            self::UBUNTU => self::UBUNTU_LTS_VERSIONS,
            self::DEBIAN => array_keys(self::DEBIAN_CODENAMES),
        };

        return $versions;
    }

    // ----
    // Static Helpers
    // ----

    /**
     * Get all distribution slugs as array.
     *
     * @return array<string>
     */
    public static function slugs(): array
    {
        return array_map(fn (self $dist) => $dist->value, self::cases());
    }
}
