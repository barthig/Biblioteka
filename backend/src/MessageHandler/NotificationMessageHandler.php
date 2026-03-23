<?php
declare(strict_types=1);
namespace App\MessageHandler;

use App\Entity\NotificationLog;
use App\Entity\Reservation;
use App\Entity\User;
use App\Message\LoanBorrowedMessage;
use App\Message\LoanDueReminderMessage;
use App\Message\LoanOverdueMessage;
use App\Message\LoanReturnedMessage;
use App\Message\NotificationMessageInterface;
use App\Message\ReservationReadyMessage;
use App\Repository\LoanRepository;
use App\Repository\NotificationLogRepository;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use App\Service\Notification\NotificationContent;
use App\Service\Notification\NotificationContentBuilder;
use App\Service\Notification\NotificationSender;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class NotificationMessageHandler
{
    public function __construct(
        private UserRepository $users,
        private LoanRepository $loans,
        private ReservationRepository $reservations,
        private NotificationLogRepository $logs,
        private NotificationContentBuilder $contentBuilder,
        private NotificationSender $sender,
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(NotificationMessageInterface $message): void
    {
        $user = $this->users->find($message->getUserId());
        if (!$user) {
            $this->logger->warning('Notification skipped - user missing', ['userId' => $message->getUserId()]);
            return;
        }

        $content = null;
        $payload = $message->getPayload();

        switch ($message->getType()) {
            case NotificationMessageInterface::TYPE_LOAN_BORROWED:
                \assert($message instanceof LoanBorrowedMessage);
                $loan = $this->loans->find($message->getLoanId());
                if (!$loan || $loan->getReturnedAt() !== null) {
                    $this->logger->info('Skipping borrowed notification, loan missing or already returned', [
                        'loanId' => $message->getLoanId(),
                    ]);
                    return;
                }
                $content = $this->contentBuilder->buildLoanBorrowed($user, $loan);
                break;
            case NotificationMessageInterface::TYPE_LOAN_RETURNED:
                \assert($message instanceof LoanReturnedMessage);
                $loan = $this->loans->find($message->getLoanId());
                if (!$loan || $loan->getReturnedAt() === null) {
                    $this->logger->info('Skipping returned notification, loan missing or not returned', [
                        'loanId' => $message->getLoanId(),
                    ]);
                    return;
                }
                $content = $this->contentBuilder->buildLoanReturned($user, $loan, $message->isOverdue());
                break;
            case NotificationMessageInterface::TYPE_LOAN_DUE:
                \assert($message instanceof LoanDueReminderMessage);
                $loan = $this->loans->find($message->getLoanId());
                if (!$loan || $loan->getReturnedAt()) {
                    $this->logger->info('Skipping loan due reminder, loan missing or already returned', [
                        'loanId' => $message->getLoanId(),
                    ]);
                    return;
                }
                $content = $this->contentBuilder->buildLoanDue($user, $loan);
                break;
            case NotificationMessageInterface::TYPE_LOAN_OVERDUE:
                \assert($message instanceof LoanOverdueMessage);
                $loan = $this->loans->find($message->getLoanId());
                if (!$loan || $loan->getReturnedAt()) {
                    $this->logger->info('Skipping overdue reminder, loan missing or already returned', [
                        'loanId' => $message->getLoanId(),
                    ]);
                    return;
                }
                $content = $this->contentBuilder->buildLoanOverdue($user, $loan, $message->getDaysLate());
                break;
            case NotificationMessageInterface::TYPE_RESERVATION_READY:
                \assert($message instanceof ReservationReadyMessage);
                $reservation = $this->reservations->find($message->getReservationId());
                if (!$reservation || $reservation->getStatus() !== Reservation::STATUS_ACTIVE) {
                    $this->logger->info('Skipping reservation ready notification due to status change', [
                        'reservationId' => $message->getReservationId(),
                    ]);
                    return;
                }
                $content = $this->contentBuilder->buildReservationReady($user, $reservation);
                break;
            default:
                $this->logger->warning('Unknown notification message type', ['type' => $message->getType()]);
                return;
        }

        $this->deliver($message, $user, $content, $payload);
    }

    private function deliver(NotificationMessageInterface $message, User $user, NotificationContent $content, array $payload): void
    {
        $logsToFlush = false;

        foreach ($content->getChannels() as $channel) {
            if ($this->logs->existsForFingerprint($message->getFingerprint(), $channel)) {
                $this->logger->info('Notification skipped due to fingerprint deduplication', [
                    'fingerprint' => $message->getFingerprint(),
                    'channel' => $channel,
                ]);
                continue;
            }

            $result = match ($channel) {
                'email' => $this->sender->sendEmail($user, $content),
                'sms' => $this->sender->sendSms($user, $content),
                default => ['status' => 'skipped', 'error' => 'unsupported_channel'],
            };

            $status = strtoupper((string) ($result['status'] ?? 'unknown'));
            if ($status === 'FAILED') {
                $this->logger->error('Async notification delivery failed', [
                    'fingerprint' => $message->getFingerprint(),
                    'channel' => $channel,
                    'userId' => $user->getId(),
                    'error' => $result['error'] ?? null,
                ]);
            }

            $log = (new NotificationLog())
                ->setUser($user)
                ->setType($message->getType())
                ->setChannel($channel)
                ->setFingerprint($message->getFingerprint())
                ->setPayload($payload)
                ->setStatus($status)
                ->setErrorMessage($result['error'] ?? null);

            $this->em->persist($log);
            $logsToFlush = true;
        }

        if ($logsToFlush) {
            $this->em->flush();
        }
    }
}
