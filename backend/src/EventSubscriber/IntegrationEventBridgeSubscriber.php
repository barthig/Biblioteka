<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\BookBorrowedEvent;
use App\Event\BookCreatedEvent;
use App\Event\BookDeletedEvent;
use App\Event\BookReturnedEvent;
use App\Event\BookUpdatedEvent;
use App\Event\FavoriteAddedEvent;
use App\Event\FineCreatedEvent;
use App\Event\LoanExtendedEvent;
use App\Event\LoanOverdueEvent;
use App\Event\RatingCreatedEvent;
use App\Event\ReservationCreatedEvent;
use App\Event\ReservationExpiredEvent;
use App\Event\ReservationFulfilledEvent;
use App\Event\UserBlockedEvent;
use App\Service\Integration\IntegrationEventPublisher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Bridges Symfony domain events → RabbitMQ integration events.
 *
 * Listens for internal domain events and publishes corresponding
 * integration events to the topic exchange for external microservices.
 */
final class IntegrationEventBridgeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly IntegrationEventPublisher $publisher,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BookBorrowedEvent::class => 'onBookBorrowed',
            BookReturnedEvent::class => 'onBookReturned',
            LoanOverdueEvent::class => 'onLoanOverdue',
            LoanExtendedEvent::class => 'onLoanExtended',
            ReservationCreatedEvent::class => 'onReservationCreated',
            ReservationFulfilledEvent::class => 'onReservationFulfilled',
            ReservationExpiredEvent::class => 'onReservationExpired',
            FineCreatedEvent::class => 'onFineCreated',
            UserBlockedEvent::class => 'onUserBlocked',
            BookCreatedEvent::class => 'onBookCreated',
            BookUpdatedEvent::class => 'onBookUpdated',
            BookDeletedEvent::class => 'onBookDeleted',
            RatingCreatedEvent::class => 'onRatingCreated',
            FavoriteAddedEvent::class => 'onFavoriteAdded',
        ];
    }

    public function onBookBorrowed(BookBorrowedEvent $event): void
    {
        $loan = $event->getLoan();
        $this->publisher->publish('loan.borrowed', [
            'loan_id' => $loan->getId(),
            'user_id' => $loan->getUser()?->getId(),
            'user_email' => $loan->getUser()?->getEmail(),
            'user_name' => $loan->getUser()?->getName(),
            'book_id' => $loan->getBook()?->getId(),
            'book_title' => $loan->getBook()?->getTitle(),
            'due_date' => $loan->getDueAt()?->format('Y-m-d'),
        ]);
    }

    public function onBookReturned(BookReturnedEvent $event): void
    {
        $loan = $event->getLoan();
        $this->publisher->publish('loan.returned', [
            'loan_id' => $loan->getId(),
            'user_id' => $loan->getUser()?->getId(),
            'user_email' => $loan->getUser()?->getEmail(),
            'user_name' => $loan->getUser()?->getName(),
            'book_id' => $loan->getBook()?->getId(),
            'book_title' => $loan->getBook()?->getTitle(),
            'is_overdue' => $event->isOverdue(),
        ]);
    }

    public function onLoanOverdue(LoanOverdueEvent $event): void
    {
        $loan = $event->getLoan();
        $this->publisher->publish('loan.overdue', [
            'loan_id' => $loan->getId(),
            'user_id' => $loan->getUser()?->getId(),
            'user_email' => $loan->getUser()?->getEmail(),
            'user_name' => $loan->getUser()?->getName(),
            'book_id' => $loan->getBook()?->getId(),
            'book_title' => $loan->getBook()?->getTitle(),
            'due_date' => $loan->getDueAt()?->format('Y-m-d'),
            'days_late' => $event->getDaysOverdue(),
        ]);
    }

    public function onLoanExtended(LoanExtendedEvent $event): void
    {
        $loan = $event->getLoan();
        $this->publisher->publish('loan.extended', [
            'loan_id' => $loan->getId(),
            'user_id' => $loan->getUser()?->getId(),
            'user_email' => $loan->getUser()?->getEmail(),
            'user_name' => $loan->getUser()?->getName(),
            'book_id' => $loan->getBook()?->getId(),
            'book_title' => $loan->getBook()?->getTitle(),
            'new_due_date' => $loan->getDueAt()?->format('Y-m-d'),
        ]);
    }

    public function onReservationCreated(ReservationCreatedEvent $event): void
    {
        $reservation = $event->getReservation();
        $this->publisher->publish('reservation.created', [
            'reservation_id' => $reservation->getId(),
            'user_id' => $event->getUser()->getId(),
            'user_email' => $event->getUser()->getEmail(),
            'user_name' => $event->getUser()->getName(),
            'book_id' => $event->getBook()->getId(),
            'book_title' => $event->getBook()->getTitle(),
        ]);
    }

    public function onReservationFulfilled(ReservationFulfilledEvent $event): void
    {
        $reservation = $event->getReservation();
        $this->publisher->publish('reservation.fulfilled', [
            'reservation_id' => $reservation->getId(),
            'user_id' => $reservation->getUser()?->getId(),
            'user_email' => $reservation->getUser()?->getEmail(),
            'user_name' => $reservation->getUser()?->getName(),
            'book_id' => $reservation->getBook()?->getId(),
            'book_title' => $reservation->getBook()?->getTitle(),
            'expires_at' => $reservation->getExpiresAt()?->format('Y-m-d H:i'),
        ]);
    }

    public function onReservationExpired(ReservationExpiredEvent $event): void
    {
        $reservation = $event->getReservation();
        $this->publisher->publish('reservation.expired', [
            'reservation_id' => $reservation->getId(),
            'user_id' => $reservation->getUser()?->getId(),
            'user_email' => $reservation->getUser()?->getEmail(),
            'user_name' => $reservation->getUser()?->getName(),
            'book_id' => $reservation->getBook()?->getId(),
            'book_title' => $reservation->getBook()?->getTitle(),
        ]);
    }

    public function onFineCreated(FineCreatedEvent $event): void
    {
        $fine = $event->getFine();
        $this->publisher->publish('fine.created', [
            'fine_id' => $fine->getId(),
            'user_id' => $fine->getUser()?->getId(),
            'user_email' => $fine->getUser()?->getEmail(),
            'user_name' => $fine->getUser()?->getName(),
            'amount' => (string) $fine->getAmount(),
            'reason' => $fine->getReason(),
        ]);
    }

    public function onUserBlocked(UserBlockedEvent $event): void
    {
        $user = $event->getUser();
        $this->publisher->publish('user.blocked', [
            'user_id' => $user->getId(),
            'user_email' => $user->getEmail(),
            'reason' => $event->getReason(),
        ]);
    }

    // ─── Book events (→ Recommendation Service) ───────────────────

    public function onBookCreated(BookCreatedEvent $event): void
    {
        $book = $event->getBook();
        $categories = array_map(
            fn($c) => $c->getName(),
            $book->getCategories()->toArray()
        );

        $this->publisher->publish('book.created', [
            'book_id' => $book->getId(),
            'title' => $book->getTitle(),
            'author' => $book->getAuthor()?->getName(),
            'category' => implode(', ', $categories),
            'description' => $book->getDescription() ?? '',
            'isbn' => $book->getIsbn(),
        ]);
    }

    public function onBookUpdated(BookUpdatedEvent $event): void
    {
        $book = $event->getBook();
        $categories = array_map(
            fn($c) => $c->getName(),
            $book->getCategories()->toArray()
        );

        $this->publisher->publish('book.updated', [
            'book_id' => $book->getId(),
            'title' => $book->getTitle(),
            'author' => $book->getAuthor()?->getName(),
            'category' => implode(', ', $categories),
            'description' => $book->getDescription() ?? '',
            'isbn' => $book->getIsbn(),
        ]);
    }

    public function onBookDeleted(BookDeletedEvent $event): void
    {
        $this->publisher->publish('book.deleted', [
            'book_id' => $event->getBookId(),
            'book_title' => $event->getBookTitle(),
        ]);
    }

    // ─── User interaction events (→ Recommendation Service) ──────

    public function onRatingCreated(RatingCreatedEvent $event): void
    {
        $this->publisher->publish('rating.created', [
            'user_id' => $event->getUserId(),
            'book_id' => $event->getBookId(),
            'rating' => $event->getRatingValue(),
        ]);
    }

    public function onFavoriteAdded(FavoriteAddedEvent $event): void
    {
        $this->publisher->publish('favorite.added', [
            'user_id' => $event->getUserId(),
            'book_id' => $event->getBookId(),
        ]);
    }
}
