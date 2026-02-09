<?php
declare(strict_types=1);
namespace App\Service\Notification;

use App\Entity\Loan;
use App\Entity\Reservation;
use App\Entity\User;

class NotificationContentBuilder
{
    public function buildLoanDue(User $user, Loan $loan): NotificationContent
    {
        $title = $loan->getBook()->getTitle();
        $dueAt = $loan->getDueAt()->format('Y-m-d');

        $subject = sprintf('Reminder: return "%s" by %s', $title, $dueAt);
        $text = sprintf(
            "Hi %s!\n\nThis is a reminder that the book \"%s\" is due for return. The deadline is %s. If you need more time, check the renewal option in your reader panel.\n\nBest regards,\nYour Library",
            $user->getName(),
            $title,
            $dueAt
        );

        $html = sprintf(
            '<p>Hi %s!</p><p>This is a reminder that the book <strong>%s</strong> is due for return. The deadline is <strong>%s</strong>. If you need more time, check the renewal option in your reader panel.</p><p>Best regards,<br/>Your Library</p>',
            htmlspecialchars($user->getName(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $dueAt
        );

        return new NotificationContent($subject, $text, $html, ['email']);
    }

    public function buildLoanOverdue(User $user, Loan $loan, int $daysLate): NotificationContent
    {
        $title = $loan->getBook()->getTitle();
        $dueAt = $loan->getDueAt()->format('Y-m-d');

        $subject = sprintf('Urgent: overdue loan "%s"', $title);
        $text = sprintf(
            "Hi %s!\n\nThe borrowed book \"%s\" was due for return on %s and is %d days overdue. Please return it promptly or contact the library.\n\nBest regards,\nYour Library",
            $user->getName(),
            $title,
            $dueAt,
            max(1, $daysLate)
        );

        $html = sprintf(
            '<p>Hi %s!</p><p>The borrowed book <strong>%s</strong> was due for return on %s and is <strong>%d days</strong> overdue. Please return it promptly or contact the library.</p><p>Best regards,<br/>Your Library</p>',
            htmlspecialchars($user->getName(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $dueAt,
            max(1, $daysLate)
        );

        $channels = ['email'];
        if ($user->getPhoneNumber()) {
            $channels[] = 'sms';
        }

        return new NotificationContent($subject, $text, $html, $channels);
    }

    public function buildReservationReady(User $user, Reservation $reservation): NotificationContent
    {
        $title = $reservation->getBook()->getTitle();
        $deadline = $reservation->getExpiresAt()->format('Y-m-d H:i');

        $subject = sprintf('Reservation "%s" ready for pickup', $title);
        $text = sprintf(
            "Hi %s!\n\nYour reservation for \"%s\" is ready for pickup. Please collect your copy before %s, otherwise the reservation will expire.\n\nBest regards,\nYour Library",
            $user->getName(),
            $title,
            $deadline
        );

        $html = sprintf(
            '<p>Hi %s!</p><p>Your reservation for <strong>%s</strong> is ready for pickup. Please collect it before <strong>%s</strong>, otherwise the reservation will expire.</p><p>Best regards,<br/>Your Library</p>',
            htmlspecialchars($user->getName(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $deadline
        );

        $channels = ['email'];
        if ($user->getPhoneNumber()) {
            $channels[] = 'sms';
        }

        return new NotificationContent($subject, $text, $html, $channels);
    }
}
