<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\AuditLog;
use App\Event\BookBorrowedEvent;
use App\Message\LoanBorrowedMessage;
use App\Repository\AuditLogRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class BookBorrowedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly AuditLogRepository $auditLogRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BookBorrowedEvent::class => 'onBookBorrowed',
        ];
    }

    public function onBookBorrowed(BookBorrowedEvent $event): void
    {
        $loan = $event->getLoan();
        $book = $loan->getBook();
        $user = $loan->getUser();
        $dueAt = $loan->getDueAt();

        try {
            $this->bus->dispatch(new LoanBorrowedMessage(
                $loan->getId(),
                $user->getId(),
                $dueAt->format(DATE_ATOM)
            ));

            $auditLog = new AuditLog();
            $auditLog->setAction('borrow');
            $auditLog->setEntityType('Loan');
            $auditLog->setEntityId($loan->getId());
            $auditLog->setUser($user);
            $auditLog->setNewValues([
                'bookId' => $book->getId(),
                'bookTitle' => $book->getTitle(),
                'dueDate' => $dueAt->format('Y-m-d'),
            ]);

            $this->auditLogRepository->save($auditLog, true);

            $this->logger->info('Book borrowed successfully', [
                'loanId' => $loan->getId(),
                'userId' => $user->getId(),
                'bookId' => $book->getId(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to handle book borrowed event', [
                'error' => $e->getMessage(),
                'loanId' => $loan->getId(),
            ]);
        }
    }
}
