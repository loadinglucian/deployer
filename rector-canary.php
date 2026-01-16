<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;

// ---- //
// CI Canary Rector Config
// ---- //
// This config is used by the CI Canary workflow to verify that Rector correctly
// detects issues in tests/CICanary.php. Unlike the main rector.php config, this
// one does NOT skip the CICanary file.

return RectorConfig::configure()
    ->withCache(
        '/tmp/rector',
        FileCacheStorage::class,
    )
    ->withPhpSets();
