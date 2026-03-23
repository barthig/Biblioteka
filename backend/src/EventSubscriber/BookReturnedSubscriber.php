<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\AuditLog;
use App\Event\BookReturnedEvent;
use App\Message\LoanReturnedMessage;
use App\Repository\AuditLogRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class BookReturnedSubscriber implements EventSubscriberInterface
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
            BookReturnedEvent::class => 'onBookReturned',
        ];
    }

    public function onBookReturned(BookReturnedEvent $event): void
    {
        $loan = $event->getLoan();

        try {
            $returnedAt = $loan->getReturnedAt();
            $user = $loan->getUser();
            if ($returnedAt && $user) {
                $this->bus->dispatch(new LoanReturnedMessage(
                    $loan->getId(),
                    $user->getId(),
                    $returnedAt->format(DATE_ATOM),
                    $event->isOverdue()
                ));
            }

            $auditLog = new AuditLog();
            $auditLog->setAction('return');
            $auditLog->setEntityType('Loan');
            $auditLog->setEntityId($loan->getId());
            $auditLog->setUser($loan->getUser());
            $auditLog->setNewValues([
                'bookId' => $loan->getBook()?->getId(),
                'bookTitle' => $loan->getBook()?->getTitle(),
                'wasOverdue' => $event->isOverdue(),
                'returnedAt' => $loan->getReturnedAt()?->format('Y-m-d H:i:s'),
            ]);

            $this->auditLogRepository->save($auditLog, true);

            $this->logger->info('Book returned successfully', [
                'loanId' => $loan->getId(),
                'userId' => $loan->getUser()?->getId(),
                'bookId' => $loan->getBook()?->getId(),
                'overdue' => $event->isOverdue(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to handle book returned event', [
                'error' => $e->getMessage(),
                'loanId' => $loan->getId(),
            ]);
        }
    }
}
