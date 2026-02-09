<?php
declare(strict_types=1);

namespace App\Application\QueryHandler\Statistics;

use App\Application\Query\Statistics\GetLibraryStatisticsQuery;
use App\Repository\LoanRepository;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use App\Repository\BookRepository;
use App\Repository\AuditLogRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetLibraryStatisticsQueryHandler
{
    public function __construct(
        private readonly LoanRepository $loanRepository,
        private readonly ReservationRepository $reservationRepository,
        private readonly UserRepository $userRepository,
        private readonly BookRepository $bookRepository,
        private readonly AuditLogRepository $auditLogRepository
    ) {
    }

    public function __invoke(GetLibraryStatisticsQuery $query): array
    {
        // Count active loans (not returned yet)
        $activeLoans = $this->loanRepository->countActiveLoans();

        // Count overdue loans
        $overdueLoans = $this->loanRepository->countOverdueLoans();

        // Count pending reservations
        $pendingReservations = $this->reservationRepository->countPendingReservations();

        // Total users
        $totalUsers = $this->userRepository->count([]);

        // Total books
        $totalBooks = $this->bookRepository->count([]);

        // Available copies across all books
        $availableCopies = $this->bookRepository->countTotalAvailableCopies();

        // Most popular books (by borrow count)
        $popularBooks = $this->bookRepository->findMostPopular(10);
        $popularBooksData = array_map(fn($book) => [
            'id' => $book->getId(),
            'title' => $book->getTitle(),
            'author' => $book->getAuthor()->getName(),
            'borrowCount' => max(0, $book->getTotalCopies() - $book->getCopies()),
        ], $popularBooks);

        // Recent activity (audit log)
        $recentActivity = $this->auditLogRepository->findRecent(20);
        $activityData = array_map(fn($log) => [
            'id' => $log->getId(),
            'action' => $log->getAction(),
            'entity' => $log->getEntityType(),
            'entityId' => $log->getEntityId(),
            'user' => $log->getUser()?->getName() ?? 'System',
            'timestamp' => $log->getCreatedAt()->format('Y-m-d H:i:s')
        ], $recentActivity);

        return [
            'activeLoans' => $activeLoans,
            'overdueLoans' => $overdueLoans,
            'pendingReservations' => $pendingReservations,
            'totalUsers' => $totalUsers,
            'totalBooks' => $totalBooks,
            'availableCopies' => $availableCopies,
            'popularBooks' => $popularBooksData,
            'recentActivity' => $activityData
        ];
    }
}
