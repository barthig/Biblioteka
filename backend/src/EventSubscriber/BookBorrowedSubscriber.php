<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\BookBorrowedEvent;
use App\Service\User\NotificationService;
use App\Repository\AuditLogRepository;
use App\Entity\AuditLog;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles actions when a book is borrowed.
 */
final class BookBorrowedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly AuditLogRepository $auditLogRepository,
        private readonly LoggerInterface $logger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            BookBorrowedEvent::class => 'onBookBorrowed'
        ];
    }

    public function onBookBorrowed(BookBorrowedEvent $event): void
    {
        $loan = $event->getLoan();
        
        try {
            // Send notification to user
            $this->notificationService->notifyLoanCreated($loan);
            
            // Log audit trail
            $auditLog = new AuditLog();
            $auditLog->setAction('borrow');
            $auditLog->setEntityType('Loan');
            $auditLog->setEntityId($loan->getId());
            $auditLog->setUser($loan->getUser());
            $auditLog->setNewValues(json_encode([
                'bookId' => $loan->getBook()?->getId(),
                'bookTitle' => $loan->getBook()?->getTitle(),
                'dueDate' => $loan->getDueAt()?->format('Y-m-d')
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            
            $this->auditLogRepository->save($auditLog, true);
            
            $this->logger->info('Book borrowed successfully', [
                'loanId' => $loan->getId(),
                'userId' => $loan->getUser()?->getId(),
                'bookId' => $loan->getBook()?->getId()
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to handle book borrowed event', [
                'error' => $e->getMessage(),
                'loanId' => $loan->getId()
            ]);
        }
    }
}
