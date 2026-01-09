<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\LoanRepository;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use App\Repository\BookRepository;
use App\Repository\AuditLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Statistics')]
class StatisticsController extends AbstractController
{
    public function __construct(
        private readonly LoanRepository $loanRepository,
        private readonly ReservationRepository $reservationRepository,
        private readonly UserRepository $userRepository,
        private readonly BookRepository $bookRepository,
        private readonly AuditLogRepository $auditLogRepository
    ) {}

    #[Route('/api/statistics/dashboard', methods: ['GET'])]
    #[IsGranted('ROLE_LIBRARIAN')]
    #[OA\Get(
        path: '/api/statistics/dashboard',
        summary: 'Get dashboard statistics (Librarian)',
        description: 'Returns key metrics for librarian dashboard including loans, reservations, users, and popular books',
        tags: ['Statistics'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Dashboard statistics',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'activeLoans', type: 'integer', example: 142),
                        new OA\Property(property: 'overdueLoans', type: 'integer', example: 8),
                        new OA\Property(property: 'pendingReservations', type: 'integer', example: 23),
                        new OA\Property(property: 'totalUsers', type: 'integer', example: 456),
                        new OA\Property(property: 'totalBooks', type: 'integer', example: 1250),
                        new OA\Property(property: 'availableCopies', type: 'integer', example: 3420),
                        new OA\Property(
                            property: 'popularBooks',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'title', type: 'string'),
                                    new OA\Property(property: 'borrowCount', type: 'integer')
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'recentActivity',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'action', type: 'string'),
                                    new OA\Property(property: 'timestamp', type: 'string', format: 'date-time')
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden - Librarian role required')
        ]
    )]
    public function dashboard(): JsonResponse
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
            'author' => $book->getAuthorName(),
            'borrowCount' => $book->getBorrowedCopiesCount() ?? 0
        ], $popularBooks);

        // Recent activity (audit log)
        $recentActivity = $this->auditLogRepository->findRecent(20);
        $activityData = array_map(fn($log) => [
            'id' => $log->getId(),
            'action' => $log->getAction(),
            'entity' => $log->getEntityType(),
            'entityId' => $log->getEntityId(),
            'user' => $log->getUser()?->getName() ?? 'System',
            'timestamp' => $log->getTimestamp()->format('Y-m-d H:i:s')
        ], $recentActivity);

        return $this->json([
            'activeLoans' => $activeLoans,
            'overdueLoans' => $overdueLoans,
            'pendingReservations' => $pendingReservations,
            'totalUsers' => $totalUsers,
            'totalBooks' => $totalBooks,
            'availableCopies' => $availableCopies,
            'popularBooks' => $popularBooksData,
            'recentActivity' => $activityData
        ]);
    }
}
