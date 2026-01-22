<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Loan;
use App\Entity\Reservation;
use App\Service\Notification\NotificationContent;
use App\Service\Notification\NotificationSender;
use Psr\Log\LoggerInterface;

final class NotificationService
{
    public function __construct(
        private readonly NotificationSender $sender,
        private readonly LoggerInterface $logger
    ) {
    }

    public function notifyLoanCreated(Loan $loan): void
    {
        $bookTitle = $loan->getBook()?->getTitle() ?? 'book';
        $dueAt = $loan->getDueAt()?->format('Y-m-d') ?? 'unknown';

        $subject = sprintf('Loan created: "%s"', $bookTitle);
        $text = sprintf(
            "Hello %s,\n\nYour loan for \"%s\" has been created. Due date: %s.\n\nLibrary",
            $this->getUserName($loan),
            $bookTitle,
            $dueAt
        );
        $html = sprintf(
            '<p>Hello %s,</p><p>Your loan for <strong>%s</strong> has been created. Due date: <strong>%s</strong>.</p><p>Library</p>',
            htmlspecialchars($this->getUserName($loan), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($bookTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $dueAt
        );

        $this->send($loan, $subject, $text, $html);
    }

    public function notifyLoanReturned(Loan $loan): void
    {
        $bookTitle = $loan->getBook()?->getTitle() ?? 'book';

        $subject = sprintf('Loan returned: "%s"', $bookTitle);
        $text = sprintf(
            "Hello %s,\n\nYour loan for \"%s\" has been marked as returned.\n\nLibrary",
            $this->getUserName($loan),
            $bookTitle
        );
        $html = sprintf(
            '<p>Hello %s,</p><p>Your loan for <strong>%s</strong> has been marked as returned.</p><p>Library</p>',
            htmlspecialchars($this->getUserName($loan), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($bookTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        );

        $this->send($loan, $subject, $text, $html);
    }

    public function notifyOverdueReturn(Loan $loan): void
    {
        $bookTitle = $loan->getBook()?->getTitle() ?? 'book';
        $dueAt = $loan->getDueAt()?->format('Y-m-d') ?? 'unknown';

        $subject = sprintf('Overdue return processed: "%s"', $bookTitle);
        $text = sprintf(
            "Hello %s,\n\nYour loan for \"%s\" was returned overdue. Original due date: %s.\n\nLibrary",
            $this->getUserName($loan),
            $bookTitle,
            $dueAt
        );
        $html = sprintf(
            '<p>Hello %s,</p><p>Your loan for <strong>%s</strong> was returned overdue. Original due date: <strong>%s</strong>.</p><p>Library</p>',
            htmlspecialchars($this->getUserName($loan), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($bookTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $dueAt
        );

        $this->send($loan, $subject, $text, $html);
    }

    public function notifyReservationPrepared(Reservation $reservation): void
    {
        $user = $reservation->getUser();
        if (!$user) {
            $this->logger->warning('Notification skipped - missing user for reservation', [
                'reservationId' => $reservation->getId(),
            ]);
            return;
        }

        $bookTitle = $reservation->getBook()?->getTitle() ?? 'book';
        $expiresAt = $reservation->getExpiresAt()?->format('Y-m-d') ?? 'soon';

        $subject = sprintf('Reservation ready: "%s"', $bookTitle);
        $text = sprintf(
            "Hello %s,\n\nYour reservation for \"%s\" is ready for pickup. Please collect it by %s.\n\nLibrary",
            $user->getName() ?: 'reader',
            $bookTitle,
            $expiresAt
        );
        $html = sprintf(
            '<p>Hello %s,</p><p>Your reservation for <strong>%s</strong> is ready for pickup. Please collect it by <strong>%s</strong>.</p><p>Library</p>',
            htmlspecialchars($user->getName() ?: 'reader', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($bookTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $expiresAt
        );

        $this->sendReservationNotification($reservation, $subject, $text, $html);
    }

    private function send(Loan $loan, string $subject, string $text, ?string $html): void
    {
        $user = $loan->getUser();
        if (!$user) {
            $this->logger->warning('Notification skipped - missing user for loan', [
                'loanId' => $loan->getId(),
            ]);
            return;
        }

        $channels = ['email'];
        if ($user->getPhoneNumber()) {
            $channels[] = 'sms';
        }

        $content = new NotificationContent($subject, $text, $html, $channels);

        foreach ($channels as $channel) {
            $result = match ($channel) {
                'email' => $this->sender->sendEmail($user, $content),
                'sms' => $this->sender->sendSms($user, $content),
                default => ['status' => 'skipped', 'error' => 'unsupported_channel'],
            };

            if (($result['status'] ?? '') !== 'sent') {
                $this->logger->info('Notification not sent', [
                    'loanId' => $loan->getId(),
                    'channel' => $channel,
                    'status' => $result['status'] ?? 'unknown',
                    'error' => $result['error'] ?? null,
                ]);
            }
        }
    }

    private function getUserName(Loan $loan): string
    {
        return $loan->getUser()?->getName() ?: 'reader';
    }

    private function sendReservationNotification(Reservation $reservation, string $subject, string $text, ?string $html): void
    {
        $user = $reservation->getUser();
        if (!$user) {
            $this->logger->warning('Notification skipped - missing user for reservation', [
                'reservationId' => $reservation->getId(),
            ]);
            return;
        }

        $channels = ['email'];
        if ($user->getPhoneNumber()) {
            $channels[] = 'sms';
        }

        $content = new NotificationContent($subject, $text, $html, $channels);

        foreach ($channels as $channel) {
            $result = match ($channel) {
                'email' => $this->sender->sendEmail($user, $content),
                'sms' => $this->sender->sendSms($user, $content),
                default => ['status' => 'skipped', 'error' => 'unsupported_channel'],
            };

            if (($result['status'] ?? '') !== 'sent') {
                $this->logger->info('Notification not sent', [
                    'reservationId' => $reservation->getId(),
                    'channel' => $channel,
                    'status' => $result['status'] ?? 'unknown',
                    'error' => $result['error'] ?? null,
                ]);
            }
        }
    }
}
