<?php

declare(strict_types=1);

namespace Symfony\Bridge\Doctrine\Middleware\IdleConnection;

use ArrayObject;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\API\ExceptionConverter;
use Doctrine\DBAL\Platforms\AbstractPlatform;

if (!class_exists(Driver::class, false)) {
    /**
     * Compatibility shim for DoctrineBundle versions that expect
     * Symfony\Bridge\Doctrine\Middleware\IdleConnection\Driver to exist.
     *
     * The wrapper intentionally avoids extending Doctrine DBAL middleware
     * internals because CI/autoload boot order can differ between environments.
     */
    final class Driver implements DriverInterface
    {
        /**
         * @param ArrayObject<string, int> $connectionExpiries
         */
        public function __construct(
            private readonly DriverInterface $driver,
            ArrayObject $connectionExpiries,
            int $ttl,
            string $connectionName,
        ) {
        }

        public function connect(array $params)
        {
            return $this->driver->connect($params);
        }

        public function getDatabasePlatform()
        {
            return $this->driver->getDatabasePlatform();
        }

        public function getSchemaManager(Connection $conn, AbstractPlatform $platform)
        {
            return $this->driver->getSchemaManager($conn, $platform);
        }

        public function getExceptionConverter(): ExceptionConverter
        {
            return $this->driver->getExceptionConverter();
        }
    }
}