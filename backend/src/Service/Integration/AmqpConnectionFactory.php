<?php

declare(strict_types=1);

namespace App\Service\Integration;

/**
 * Factory for creating AMQP connection for integration events.
 * Separate from Symfony Messenger — this uses php-amqplib for direct
 * topic exchange publishing to external microservices.
 */
class AmqpConnectionFactory
{
    public static function create(string $dsn): ?\PhpAmqpLib\Connection\AMQPStreamConnection
    {
        try {
            $parts = parse_url($dsn);
            $host = $parts['host'] ?? 'rabbitmq';
            $port = $parts['port'] ?? 5672;
            $user = $parts['user'] ?? 'app';
            $pass = $parts['pass'] ?? 'app';
            $vhost = isset($parts['path']) ? urldecode(ltrim($parts['path'], '/')) : '/';
            if ($vhost === '') {
                $vhost = '/';
            }

            return new \PhpAmqpLib\Connection\AMQPStreamConnection(
                $host,
                (int) $port,
                $user,
                $pass,
                $vhost,
                false,  // insist
                'AMQPLAIN',
                null,
                'en_US',
                3.0,    // connection_timeout
                3.0,    // read_write_timeout
                null,
                false,  // keepalive
                30,     // heartbeat
            );
        } catch (\Throwable $e) {
            // Integration events are non-critical — return null and log
            return null;
        }
    }
}
