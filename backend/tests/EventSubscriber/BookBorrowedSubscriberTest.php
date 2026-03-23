<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Event\BookBorrowedEvent;
use App\EventSubscriber\BookBorrowedSubscriber;
use App\Message\LoanBorrowedMessage;
use App\Repository\AuditLogRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class BookBorrowedSubscriberTest extends TestCase
{
    public function testDispatchesAsyncBorrowedMessageAndAuditLog(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $auditLogs = $this->createMock(AuditLogRepository::class);
        $logger = $this->createMock(LoggerInterface::class);

        $user = $this->getMockBuilder(\App\Entity\User::class)->onlyMethods(['getId'])->getMock();
        $user->method('getId')->willReturn(15);
        $book = $this->getMockBuilder(\App\Entity\Book::class)->disableOriginalConstructor()->onlyMethods(['getId', 'getTitle'])->getMock();
        $book->method('getId')->willReturn(7);
        $book->method('getTitle')->willReturn('Event Driven Symfony');

        $loan = $this->getMockBuilder(\App\Entity\Loan::class)->disableOriginalConstructor()->onlyMethods(['getId', 'getUser', 'getBook', 'getDueAt'])->getMock();
        $loan->method('getId')->willReturn(12);
        $loan->method('getUser')->willReturn($user);
        $loan->method('getBook')->willReturn($book);
        $loan->method('getDueAt')->willReturn(new \DateTimeImmutable('2026-03-30 10:00:00'));

        $bus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                static fn (object $message): bool => $message instanceof LoanBorrowedMessage
                    && $message->getLoanId() === 12
                    && $message->getUserId() === 15
            ))
            ->willReturn(new Envelope(new \stdClass()));
        $auditLogs->expects($this->once())->method('save');
        $logger->expects($this->once())->method('info');

        $subscriber = new BookBorrowedSubscriber($bus, $auditLogs, $logger);
        $subscriber->onBookBorrowed(new BookBorrowedEvent($loan));
    }
}
