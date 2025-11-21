<?php
namespace App\MessageHandler;

use App\Entity\NotificationLog;
use App\Entity\Reservation;
use App\Entity\User;
use App\Message\LoanDueReminderMessage;
use App\Message\LoanOverdueMessage;
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
    private const DEDUPLICATION_WINDOW_HOURS = 6;

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
            $this->logger->warning('Notification skipped – user missing', ['userId' => $message->getUserId()]);
            return;
        }

        $dedupSince = (new \DateTimeImmutable(sprintf('-%d hours', self::DEDUPLICATION_WINDOW_HOURS)));
        if ($this->logs->wasSentSince($message->getFingerprint(), $dedupSince)) {
            $this->logger->info('Notification skipped due to deduplication', [
                'fingerprint' => $message->getFingerprint(),
            ]);
            return;
        }

        $content = null;
        $payload = $message->getPayload();

        switch ($message->getType()) {
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
        foreach ($content->getChannels() as $channel) {
            $result = match ($channel) {
                'email' => $this->sender->sendEmail($user, $content),
                'sms' => $this->sender->sendSms($user, $content),
                default => ['status' => 'skipped', 'error' => 'unsupported_channel'],
            };

            $log = (new NotificationLog())
                ->setUser($user)
                ->setType($message->getType())
                ->setChannel($channel)
                ->setFingerprint($message->getFingerprint())
                ->setPayload($payload)
                ->setStatus(strtoupper($result['status'] ?? 'sent'))
                ->setErrorMessage($result['error'] ?? null);

            $this->em->persist($log);
        }

        $this->em->flush();
    }
}
