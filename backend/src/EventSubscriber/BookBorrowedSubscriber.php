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

        try {
            $dueAt = $loan->getDueAt();
            $user = $loan->getUser();
            if ($dueAt && $user) {
                $this->bus->dispatch(new LoanBorrowedMessage(
                    $loan->getId(),
                    $user->getId(),
                    $dueAt->format(DATE_ATOM)
                ));
            }

            $auditLog = new AuditLog();
            $auditLog->setAction('borrow');
            $auditLog->setEntityType('Loan');
            $auditLog->setEntityId($loan->getId());
            $auditLog->setUser($loan->getUser());
            $auditLog->setNewValues([
                'bookId' => $loan->getBook()?->getId(),
                'bookTitle' => $loan->getBook()?->getTitle(),
                'dueDate' => $loan->getDueAt()?->format('Y-m-d'),
            ]);

            $this->auditLogRepository->save($auditLog, true);

            $this->logger->info('Book borrowed successfully', [
                'loanId' => $loan->getId(),
                'userId' => $loan->getUser()?->getId(),
                'bookId' => $loan->getBook()?->getId(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to handle book borrowed event', [
                'error' => $e->getMessage(),
                'loanId' => $loan->getId(),
            ]);
        }
    }
}
