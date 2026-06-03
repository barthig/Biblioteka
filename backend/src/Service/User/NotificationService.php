<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\Announcement;
use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\NotificationLog;
use App\Entity\Rating;
use App\Entity\Reservation;
use App\Entity\Review;
use App\Entity\User;
use App\Repository\NotificationLogRepository;
use App\Repository\UserRepository;
use App\Service\Notification\NotificationContent;
use App\Service\Notification\NotificationSender;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class NotificationService
{
    public function __construct(
        private readonly NotificationSender $sender,
        private readonly LoggerInterface $logger,
        private readonly UserRepository $userRepository,
        private readonly NotificationLogRepository $notificationLogs,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function notifyReservationPrepared(Reservation $reservation): void
    {
        $user = $reservation->getUser();
        $bookTitle = $reservation->getBook()->getTitle();
        $expiresAt = $reservation->getExpiresAt()->format('Y-m-d');

        $title = 'Rezerwacja gotowa do odbioru';
        $message = sprintf(
            'Twoja rezerwacja na tytuł "%s" jest gotowa do odbioru. Odbierz ją do %s.',
            $bookTitle,
            $expiresAt
        );

        $this->storeInAppNotification(
            $user,
            'reservation_prepared',
            $title,
            $message,
            '/reservations',
            ['reservationId' => $reservation->getId()],
            sprintf('reservation:%d', $reservation->getId() ?? 0)
        );

        $this->entityManager->flush();
    }

    public function notifyReservationQueued(Reservation $reservation, bool $sendExternal = true): void
    {
        $user = $reservation->getUser();
        $bookTitle = $reservation->getBook()->getTitle();
        $expiresAt = $reservation->getExpiresAt()->format('Y-m-d');

        $title = 'Rezerwacja dodana do kolejki';
        $message = sprintf(
            'Twoja rezerwacja na tytuł "%s" została zarejestrowana. Powiadomimy Cię, gdy egzemplarz będzie gotowy do odbioru. Aktualna data wygaśnięcia: %s.',
            $bookTitle,
            $expiresAt
        );

        $this->storeInAppNotification(
            $user,
            'reservation_queued',
            $title,
            $message,
            '/reservations',
            ['reservationId' => $reservation->getId()],
            sprintf('reservation-queued:%d', $reservation->getId() ?? 0)
        );

        $subject = sprintf('Rezerwacja w kolejce: "%s"', $bookTitle);
        $text = sprintf(
            "Witaj %s,\n\nTwoja rezerwacja na tytuł \"%s\" została dodana do kolejki. Powiadomimy Cię, gdy egzemplarz będzie gotowy do odbioru. Aktualna data wygaśnięcia: %s.\n\nBiblioteka",
            $user->getName() ?: 'czytelniku',
            $bookTitle,
            $expiresAt
        );
        $html = sprintf(
            '<p>Witaj %s,</p><p>Twoja rezerwacja na tytuł <strong>%s</strong> została dodana do kolejki. Powiadomimy Cię, gdy egzemplarz będzie gotowy do odbioru.</p><p>Aktualna data wygaśnięcia: <strong>%s</strong>.</p><p>Biblioteka</p>',
            htmlspecialchars($user->getName() ?: 'czytelniku', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($bookTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $expiresAt
        );

        if ($sendExternal) {
            $this->sendReservationNotification($reservation, $subject, $text, $html);
        }
        $this->entityManager->flush();
    }

    public function notifyLoanCreated(Loan $loan): void
    {
        $book = $loan->getBook();
        $dueAt = $loan->getDueAt()->format('Y-m-d');

        $this->storeInAppNotification(
            $loan->getUser(),
            'loan_created',
            'Książka wypożyczona',
            sprintf('Wypożyczono "%s". Termin zwrotu: %s.', $book->getTitle(), $dueAt),
            '/my-loans',
            ['loanId' => $loan->getId(), 'bookId' => $book->getId(), 'dueAt' => $dueAt],
            sprintf('loan-created:%d', $loan->getId() ?? 0)
        );

        $this->entityManager->flush();
    }

    public function notifyLoanReturned(Loan $loan): void
    {
        $book = $loan->getBook();
        $returnedAt = $loan->getReturnedAt()?->format('Y-m-d') ?? (new \DateTimeImmutable())->format('Y-m-d');

        $this->storeInAppNotification(
            $loan->getUser(),
            'loan_returned',
            'Zwrot książki przyjęty',
            sprintf('Przyjęto zwrot książki "%s" w dniu %s.', $book->getTitle(), $returnedAt),
            '/my-loans',
            ['loanId' => $loan->getId(), 'bookId' => $book->getId(), 'returnedAt' => $returnedAt],
            sprintf('loan-returned:%d', $loan->getId() ?? 0)
        );

        $this->entityManager->flush();
    }

    public function notifyReviewCreated(Review $review): void
    {
        $book = $review->getBook();

        $this->storeInAppNotification(
            $review->getUser(),
            'review_created',
            'Opinia została dodana',
            sprintf('Dodano Twoją opinię o książce "%s" z oceną %d/5.', $book->getTitle(), $review->getRating()),
            sprintf('/books/%d', $book->getId()),
            ['reviewId' => $review->getId(), 'bookId' => $book->getId(), 'rating' => $review->getRating()],
            sprintf('review-created:%d', $review->getId() ?? 0)
        );

        $this->entityManager->flush();
    }

    public function notifyRatingCreated(Rating $rating): void
    {
        $book = $rating->getBook();

        $this->storeInAppNotification(
            $rating->getUser(),
            'rating_created',
            'Ocena została zapisana',
            sprintf('Zapisano Twoją ocenę %d/5 dla książki "%s".', $rating->getRating(), $book->getTitle()),
            sprintf('/books/%d', $book->getId()),
            ['ratingId' => $rating->getId(), 'bookId' => $book->getId(), 'rating' => $rating->getRating()],
            sprintf('rating-created:%d:%d', $rating->getId() ?? 0, $rating->getRating())
        );

        $this->entityManager->flush();
    }

    public function notifyWelcome(User $user): void
    {
        $this->storeInAppNotification(
            $user,
            'welcome',
            'Witamy w Smart Library',
            sprintf('Cześć %s, Twoje konto zostało utworzone. Możesz korzystać z katalogu, rezerwacji i wypożyczeń.', $user->getName() ?: 'czytelniku'),
            '/',
            ['userId' => $user->getId()],
            sprintf('welcome:%d', $user->getId() ?? 0)
        );

        $this->entityManager->flush();
    }

    public function notifyAnnouncementPublished(Announcement $announcement): void
    {
        $recipients = $this->userRepository->findAnnouncementRecipients();
        $recipients = $this->filterRecipientsByAudience($recipients, $announcement->getTargetAudience());

        if ($recipients === []) {
            $this->logger->info('Announcement notification skipped - no recipients', [
                'announcementId' => $announcement->getId(),
            ]);
            return;
        }

        foreach ($recipients as $user) {
            $isEvent = $announcement->getType() === 'event' || $announcement->getEventAt() !== null;
            $title = $isEvent ? 'Nowe wydarzenie biblioteczne' : 'Nowe ogłoszenie biblioteczne';
            $message = $this->buildAnnouncementMessage($announcement, $isEvent);

            $this->storeInAppNotification(
                $user,
                $isEvent ? 'event_published' : 'announcement_published',
                $title,
                $message,
                '/announcements',
                ['announcementId' => $announcement->getId()],
                sprintf('announcement:%d', $announcement->getId() ?? 0)
            );
        }

        $this->entityManager->flush();
    }

    public function notifyNewBookAvailable(Book $book): void
    {
        $recipients = $this->userRepository->findNewsletterRecipients();
        if ($recipients === []) {
            $this->logger->info('New book notification skipped - no recipients', [
                'bookId' => $book->getId(),
            ]);
            return;
        }

        foreach ($recipients as $user) {
            $title = 'Nowa pozycja w katalogu';
            $message = $this->buildNewBookMessage($book);

            $this->storeInAppNotification(
                $user,
                'catalog_new_book',
                $title,
                $message,
                sprintf('/books/%d', $book->getId()),
                ['bookId' => $book->getId()],
                sprintf('book:%d', $book->getId() ?? 0)
            );
        }

        $this->entityManager->flush();
    }

    private function sendReservationNotification(Reservation $reservation, string $subject, string $text, ?string $html): void
    {
        $user = $reservation->getUser();

        $channels = ['email'];
        if ($user->getPhoneNumber()) {
            $channels[] = 'sms';
        }

        $content = new NotificationContent($subject, $text, $html, $channels);

        foreach ($channels as $channel) {
            $result = $channel === 'email'
                ? $this->sender->sendEmail($user, $content)
                : $this->sender->sendSms($user, $content);

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

    private function storeInAppNotification(
        User $user,
        string $type,
        string $title,
        string $message,
        ?string $link,
        array $meta,
        ?string $fingerprintSeed = null
    ): void {
        $seed = $fingerprintSeed ?? (string) microtime(true);
        $fingerprint = substr(
            hash('sha256', sprintf('in_app|%s|%d|%s', $type, $user->getId(), $seed)),
            0,
            64
        );

        if ($this->notificationLogs->existsForFingerprint($fingerprint, 'in_app')) {
            return;
        }

        $payload = array_merge($meta, [
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'type' => $type,
        ]);

        $log = (new NotificationLog())
            ->setUser($user)
            ->setType($type)
            ->setChannel('in_app')
            ->setFingerprint($fingerprint)
            ->setPayload($payload)
            ->setStatus('DELIVERED');

        $this->entityManager->persist($log);
    }

    private function buildAnnouncementMessage(Announcement $announcement, bool $isEvent): string
    {
        $title = $announcement->getTitle();
        $content = $this->truncate($this->normalizeWhitespace($announcement->getContent()), 320);

        $base = $isEvent
            ? sprintf('Join us for the event: "%s".', $title)
            : sprintf('A new announcement has been posted: "%s".', $title);

        $parts = [$base];
        if ($content !== '') {
            $parts[] = $content;
        }

        $eventDetails = $this->formatEventDetails($announcement);
        if ($eventDetails !== null) {
            $parts[] = $eventDetails;
        }

        return implode(' ', $parts);
    }

    private function buildNewBookMessage(Book $book): string
    {
        $title = $book->getTitle();
        $author = $book->getAuthor()->getName();
        $available = $book->getCopies();

        $parts = [
            sprintf('A new item has been added to the catalog: "%s" by %s.', $title, $author),
        ];

        if ($available > 0) {
            $parts[] = sprintf('Available copies: %d.', $available);
        }

        $parts[] = 'Reserve or borrow it now before it disappears from the shelf.';

        return implode(' ', $parts);
    }

    /**
     * @param User[] $recipients
     * @param string[]|null $targetAudience
     * @return User[]
     */
    private function filterRecipientsByAudience(array $recipients, ?array $targetAudience): array
    {
        if (!$targetAudience || in_array('all', $targetAudience, true)) {
            return $recipients;
        }

        $filtered = [];
        foreach ($recipients as $user) {
            $roles = $user->getRoles();
            if (in_array('librarians', $targetAudience, true) && in_array('ROLE_LIBRARIAN', $roles, true)) {
                $filtered[] = $user;
                continue;
            }
            if (in_array('users', $targetAudience, true) && in_array('ROLE_USER', $roles, true)) {
                $filtered[] = $user;
            }
        }

        return $filtered;
    }

    private function formatEventDetails(Announcement $announcement): ?string
    {
        $eventAt = $announcement->getEventAt();
        $location = $announcement->getLocation();

        if (!$eventAt && !$location) {
            return null;
        }

        $parts = [];
        if ($eventAt) {
            $parts[] = sprintf('Date: %s', $eventAt->format('Y-m-d H:i'));
        }
        if ($location) {
            $parts[] = sprintf('Location: %s', $location);
        }

        return implode(' | ', $parts);
    }

    private function normalizeWhitespace(string $text): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($text));
        return $normalized ?? trim($text);
    }

    private function truncate(string $text, int $limit): string
    {
        if ($limit <= 0 || $text === '') {
            return $text;
        }

        $length = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
        if ($length <= $limit) {
            return $text;
        }

        $slice = function_exists('mb_substr') ? mb_substr($text, 0, $limit - 3) : substr($text, 0, $limit - 3);
        return rtrim($slice) . '...';
    }
}

