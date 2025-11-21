<?php
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

        $subject = sprintf('Przypomnienie: zwrot "%s" do %s', $title, $dueAt);
        $text = sprintf(
            "Cześć %s!\n\nPrzypominamy o terminie zwrotu książki \"%s\". Termin mija %s. Jeśli potrzebujesz więcej czasu, sprawdź możliwość przedłużenia w panelu czytelnika.\n\nPozdrawiamy,\nTwoja Biblioteka",
            $user->getName(),
            $title,
            $dueAt
        );

        $html = sprintf(
            '<p>Cześć %s!</p><p>Przypominamy o terminie zwrotu książki <strong>%s</strong>. Termin mija <strong>%s</strong>. Jeśli potrzebujesz więcej czasu, sprawdź możliwość przedłużenia w panelu czytelnika.</p><p>Pozdrawiamy,<br/>Twoja Biblioteka</p>',
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

        $subject = sprintf('Pilne: przeterminowane wypożyczenie "%s"', $title);
        $text = sprintf(
            "Cześć %s!\n\nWypożyczona książka \"%s\" powinna zostać zwrócona %s i jest spóźniona o %d dni. Prosimy o pilny zwrot lub kontakt z biblioteką.\n\nPozdrawiamy,\nTwoja Biblioteka",
            $user->getName(),
            $title,
            $dueAt,
            max(1, $daysLate)
        );

        $html = sprintf(
            '<p>Cześć %s!</p><p>Wypożyczona książka <strong>%s</strong> powinna zostać zwrócona %s i jest spóźniona o <strong>%d dni</strong>. Prosimy o pilny zwrot lub kontakt z biblioteką.</p><p>Pozdrawiamy,<br/>Twoja Biblioteka</p>',
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

        $subject = sprintf('Rezerwacja "%s" gotowa do odbioru', $title);
        $text = sprintf(
            "Cześć %s!\n\nTwoja rezerwacja książki \"%s\" jest gotowa do odbioru. Odbierz egzemplarz przed %s, w przeciwnym razie rezerwacja wygaśnie.\n\nPozdrawiamy,\nTwoja Biblioteka",
            $user->getName(),
            $title,
            $deadline
        );

        $html = sprintf(
            '<p>Cześć %s!</p><p>Twoja rezerwacja książki <strong>%s</strong> jest gotowa do odbioru. Prosimy o odbiór przed <strong>%s</strong>, w przeciwnym razie rezerwacja wygaśnie.</p><p>Pozdrawiamy,<br/>Twoja Biblioteka</p>',
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
