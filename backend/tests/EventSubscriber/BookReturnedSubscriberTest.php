<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Event\BookReturnedEvent;
use App\EventSubscriber\BookReturnedSubscriber;
use App\Message\LoanReturnedMessage;
use App\Repository\AuditLogRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class BookReturnedSubscriberTest extends TestCase
{
    public function testDispatchesAsyncReturnedMessageAndAuditLog(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $auditLogs = $this->createMock(AuditLogRepository::class);
        $logger = $this->createMock(LoggerInterface::class);

        $user = $this->getMockBuilder(\App\Entity\User::class)->onlyMethods(['getId'])->getMock();
        $user->method('getId')->willReturn(21);
        $book = $this->getMockBuilder(\App\Entity\Book::class)->disableOriginalConstructor()->onlyMethods(['getId', 'getTitle'])->getMock();
        $book->method('getId')->willReturn(9);
        $book->method('getTitle')->willReturn('Offline First Libraries');

        $returnedAt = new \DateTimeImmutable('2026-03-25 09:00:00');
        $loan = $this->getMockBuilder(\App\Entity\Loan::class)->disableOriginalConstructor()->onlyMethods(['getId', 'getUser', 'getBook', 'getReturnedAt', 'getDueAt'])->getMock();
        $loan->method('getId')->willReturn(18);
        $loan->method('getUser')->willReturn($user);
        $loan->method('getBook')->willReturn($book);
        $loan->method('getReturnedAt')->willReturn($returnedAt);
        $loan->method('getDueAt')->willReturn(new \DateTimeImmutable('2026-03-20 09:00:00'));

        $bus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                static fn (object $message): bool => $message instanceof LoanReturnedMessage
                    && $message->getLoanId() === 18
                    && $message->getUserId() === 21
                    && $message->isOverdue()
            ))
            ->willReturn(new Envelope(new \stdClass()));
        $auditLogs->expects($this->once())->method('save');
        $logger->expects($this->once())->method('info');

        $subscriber = new BookReturnedSubscriber($bus, $auditLogs, $logger);
        $subscriber->onBookReturned(new BookReturnedEvent($loan));
    }
}
