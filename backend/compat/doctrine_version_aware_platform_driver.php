<?php

declare(strict_types=1);

namespace Doctrine\DBAL;

if (!interface_exists(VersionAwarePlatformDriver::class, false)) {
    /**
     * Compatibility shim for environments where Doctrine DBAL middleware
     * is loaded before the interface file is available to the autoloader.
     */
    interface VersionAwarePlatformDriver extends Driver
    {
        public function createDatabasePlatformForVersion($version);
    }
}