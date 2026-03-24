<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\Integration\IntegrationEventPublisher;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class IntegrationEventPublisherTest extends TestCase
{
    public function testPublishRetriesAfterBrokerFailure(): void
    {
        $firstChannel = $this->createMock(AMQPChannel::class);
        $firstChannel->method('is_open')->willReturn(true);
        $firstChannel->expects($this->once())->method('exchange_declare');
        $firstChannel->expects($this->once())
            ->method('basic_publish')
            ->willThrowException(new \RuntimeException('broker temporarily unavailable'));
        $firstChannel->expects($this->once())->method('close');

        $secondChannel = $this->createMock(AMQPChannel::class);
        $secondChannel->method('is_open')->willReturn(true);
        $secondChannel->expects($this->once())->method('exchange_declare');
        $secondChannel->expects($this->once())->method('basic_publish');
        $secondChannel->expects($this->never())->method('close');

        $connection = $this->createMock(AMQPStreamConnection::class);
        $connection->expects($this->exactly(2))
            ->method('channel')
            ->willReturnOnConsecutiveCalls($firstChannel, $secondChannel);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');
        $logger->expects($this->once())->method('info');
        $logger->expects($this->never())->method('error');

        $publisher = new IntegrationEventPublisher($connection, $logger);
        $publisher->publish('loan.borrowed', ['loanId' => 15]);

        $this->addToAssertionCount(1);
    }

    public function testPublishLogsErrorAfterExhaustingRetries(): void
    {
        $channel = $this->createMock(AMQPChannel::class);
        $channel->method('is_open')->willReturn(true);
        $channel->expects($this->exactly(3))->method('exchange_declare');
        $channel->expects($this->exactly(3))
            ->method('basic_publish')
            ->willThrowException(new \RuntimeException('broker down'));
        $channel->expects($this->exactly(3))->method('close');

        $connection = $this->createMock(AMQPStreamConnection::class);
        $connection->expects($this->exactly(3))->method('channel')->willReturn($channel);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))->method('warning');
        $logger->expects($this->never())->method('info');
        $logger->expects($this->once())->method('error');

        $publisher = new IntegrationEventPublisher($connection, $logger);
        $publisher->publish('loan.borrowed', ['loanId' => 15]);

        $this->addToAssertionCount(1);
    }
}