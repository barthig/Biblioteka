<?php

declare(strict_types=1);

namespace Symfony\Bridge\Doctrine\Middleware\IdleConnection;

use ArrayObject;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

if (!class_exists(Listener::class, false)) {
    /**
     * Compatibility shim for DoctrineBundle versions that expect
     * Symfony\Bridge\Doctrine\Middleware\IdleConnection\Listener to exist.
     *
     * The older bridge package shipped without this subscriber class.
     * For local/dev runtime we only need the container to boot; the
     * connection-expiry behavior is optional here, so a no-op subscriber is
     * sufficient.
     */
    final class Listener implements EventSubscriberInterface
    {
        /**
         * @param ArrayObject<string, int> $connectionExpiries
         */
        public function __construct(
            private readonly ArrayObject $connectionExpiries,
            private readonly object $container,
        ) {
        }

        public static function getSubscribedEvents(): array
        {
            return [];
        }
    }
}
