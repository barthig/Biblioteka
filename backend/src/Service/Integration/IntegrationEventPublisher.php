<?php

declare(strict_types=1);

namespace App\Service\Integration;

use Psr\Log\LoggerInterface;

/**
 * Publishes integration events to RabbitMQ topic exchange for consumption
 * by external microservices (Notification Service, Recommendation Service).
 *
 * This is the bridge between Symfony's internal domain events and the
 * distributed event bus (RabbitMQ topic exchange: biblioteka.events).
 */
class IntegrationEventPublisher
{
    public function __construct(
        private readonly \PhpAmqpLib\Connection\AMQPStreamConnection|null $amqpConnection,
        private readonly LoggerInterface $logger,
        private readonly string $exchange = 'biblioteka.events',
    ) {
    }

    /**
     * Publish an integration event to the topic exchange.
     *
     * @param string $routingKey e.g. "loan.borrowed", "reservation.created"
     * @param array  $payload    JSON-serializable payload
     */
    public function publish(string $routingKey, array $payload): void
    {
        $payload['_meta'] = [
            'event_type' => $routingKey,
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'source' => 'backend',
        ];

        try {
            $channel = $this->getChannel();
            $channel->exchange_declare($this->exchange, 'topic', false, true, false);

            $message = new \PhpAmqpLib\Message\AMQPMessage(
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                [
                    'content_type' => 'application/json',
                    'delivery_mode' => 2, // persistent
                    'timestamp' => time(),
                    'app_id' => 'biblioteka-backend',
                    'type' => $routingKey,
                ]
            );

            $channel->basic_publish($message, $this->exchange, $routingKey);

            $this->logger->info('Integration event published', [
                'routing_key' => $routingKey,
                'payload_keys' => array_keys($payload),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to publish integration event', [
                'routing_key' => $routingKey,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private ?\PhpAmqpLib\Channel\AMQPChannel $channel = null;

    private function getChannel(): \PhpAmqpLib\Channel\AMQPChannel
    {
        if ($this->channel === null || !$this->channel->is_open()) {
            if ($this->amqpConnection === null) {
                throw new \RuntimeException('AMQP connection not available');
            }
            $this->channel = $this->amqpConnection->channel();
        }
        return $this->channel;
    }
}
