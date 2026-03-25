<?php

declare(strict_types=1);

namespace App\Service\Integration;

use App\Exception\ExternalServiceException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
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
    private const MAX_ATTEMPTS = 3;

    private ?AMQPChannel $channel = null;

    public function __construct(
        private readonly ?AMQPStreamConnection $amqpConnection,
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
        $lastError = null;
        $payload['_meta'] = [
            'event_type' => $routingKey,
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'source' => 'backend',
        ];

        $message = new AMQPMessage(
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            [
                'content_type' => 'application/json',
                'delivery_mode' => 2,
                'timestamp' => time(),
                'app_id' => 'biblioteka-backend',
                'type' => $routingKey,
            ]
        );

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                $channel = $this->getChannel();
                $channel->exchange_declare($this->exchange, 'topic', false, true, false);
                $channel->basic_publish($message, $this->exchange, $routingKey);

                $this->logger->info('Integration event published', [
                    'routing_key' => $routingKey,
                    'payload_keys' => array_keys($payload),
                    'attempt' => $attempt,
                ]);

                return;
            } catch (\Throwable $e) {
                $lastError = $e;
                $this->resetChannel();

                $context = [
                    'routing_key' => $routingKey,
                    'attempt' => $attempt,
                    'max_attempts' => self::MAX_ATTEMPTS,
                    'error' => $e->getMessage(),
                ];

                if ($attempt < self::MAX_ATTEMPTS) {
                    $this->logger->warning('Retrying integration event publish after broker error', $context);
                    usleep($attempt * 100000);
                    continue;
                }

                $this->logger->error('Failed to publish integration event after retries', $context);
            }
        }

        throw ExternalServiceException::rabbitMQError(
            sprintf(
                'Failed to publish integration event "%s" after %d attempts. Last error: %s',
                $routingKey,
                self::MAX_ATTEMPTS,
                $lastError?->getMessage() ?? 'unknown error'
            )
        );
    }

    private function getChannel(): AMQPChannel
    {
        if ($this->channel === null || !$this->channel->is_open()) {
            if ($this->amqpConnection === null) {
                throw new ExternalServiceException('AMQP connection not available');
            }
            $this->channel = $this->amqpConnection->channel();
        }

        return $this->channel;
    }

    private function resetChannel(): void
    {
        if ($this->channel !== null) {
            try {
                if ($this->channel->is_open()) {
                    $this->channel->close();
                }
            } catch (\Throwable) {
                // Ignore cleanup failures, the next publish attempt will rebuild the channel.
            }
        }

        $this->channel = null;
    }
}