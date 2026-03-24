<?php

declare(strict_types=1);

namespace Symfony\Bridge\Doctrine\Middleware\IdleConnection;

use ArrayObject;
use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;

if (!class_exists(Driver::class, false)) {
    /**
     * Compatibility shim for DoctrineBundle versions that expect
     * Symfony\Bridge\Doctrine\Middleware\IdleConnection\Driver to exist.
     *
     * In this project we only need a transparent wrapper so the backend can
     * bootstrap consistently across environments with older doctrine-bridge.
     */
    final class Driver extends AbstractDriverMiddleware
    {
        /**
         * @param ArrayObject<string, int> $connectionExpiries
         */
        public function __construct(
            DriverInterface $driver,
            ArrayObject $connectionExpiries,
            int $ttl,
            string $connectionName,
        ) {
            parent::__construct($driver);
        }
    }
}
