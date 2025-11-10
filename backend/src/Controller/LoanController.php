<?php
namespace App\Controller;

use App\Entity\Loan;
use App\Service\BookService;
use App\Service\OrderLifecycleService;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Book;
use App\Entity\User;
use App\Entity\Reservation;
use App\Entity\BookCopy;
use App\Entity\OrderRequest;

class LoanController extends AbstractController
{
    public function list(Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        // librarians see all loans; regular users see only their loans
        if ($security->hasRole($request, 'ROLE_LIBRARIAN')) {
            $repo = $doctrine->getRepository(Loan::class);
            $loans = $repo->findAll();

            return $this->json($loans, 200, [], ['groups' => ['loan:read']]);
        }

        $payload = $security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $userId = (int)$payload['sub'];
        $repo = $doctrine->getRepository(Loan::class);
        $loans = $repo->findBy(['user' => $userId]);

        return $this->json($loans, 200, [], ['groups' => ['loan:read']]);
    }

    public function getLoan(string $id, Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!ctype_digit($id) || (int)$id <= 0) return $this->json(['error' => 'Invalid id parameter'], 400);
        $repo = $doctrine->getRepository(Loan::class);
        $loan = $repo->find((int)$id);
        if (!$loan) return $this->json(['error' => 'Loan not found'], 404);

        // allow librarian or the borrower to view
        if ($security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json($loan, 200, [], ['groups' => ['loan:read']]);
        }

        $payload = $security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub']) || $payload['sub'] != $loan->getUser()->getId()) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        return $this->json($loan, 200, [], ['groups' => ['loan:read']]);
    }

    public function create(Request $request, ManagerRegistry $doctrine, BookService $bookService, OrderLifecycleService $orderLifecycle, SecurityService $security): JsonResponse
    {
        $payload = $security->getJwtPayload($request);
        if ($payload === null) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $userId = $data['userId'] ?? null;
        $bookId = $data['bookId'] ?? null;
        $inventoryCode = isset($data['inventoryCode']) && is_string($data['inventoryCode']) ? strtoupper(trim($data['inventoryCode'])) : null;
        $days = isset($data['days']) ? (int) $data['days'] : 14;

        if (!$userId || !ctype_digit((string) $userId)) {
            return $this->json(['error' => 'Missing or invalid userId'], 400);
        }

        if ((!$inventoryCode || $inventoryCode === '') && (!$bookId || !ctype_digit((string) $bookId))) {
            return $this->json(['error' => 'Missing bookId or inventoryCode'], 400);
        }

        $userRepo = $doctrine->getRepository(User::class);
        $bookRepo = $doctrine->getRepository(Book::class);
        /** @var \App\Repository\BookCopyRepository $copyRepo */
        $copyRepo = $doctrine->getRepository(BookCopy::class);
        /** @var \App\Repository\ReservationRepository $reservationRepo */
        $reservationRepo = $doctrine->getRepository(Reservation::class);
        /** @var \App\Repository\OrderRequestRepository $orderRepo */
        $orderRepo = $doctrine->getRepository(OrderRequest::class);
        /** @var \App\Repository\LoanRepository $loanRepo */
        $loanRepo = $doctrine->getRepository(Loan::class);

        $user = $userRepo->find((int) $userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        if ($user->isBlocked()) {
            return $this->json(['error' => 'Konto czytelnika jest zablokowane'], 423);
        }

        $activeLoans = $loanRepo->countActiveByUser($user);
        $loanLimit = $user->getLoanLimit();
        if ($loanLimit > 0 && $activeLoans >= $loanLimit) {
            return $this->json(['error' => 'Limit wypożyczeń został osiągnięty'], 409);
        }

        $book = null;
        $preferredCopy = null;
        $assignedReservation = null;

        if ($inventoryCode) {
            $preferredCopy = $copyRepo->findOneByInventoryCode($inventoryCode);
            if (!$preferredCopy) {
                return $this->json(['error' => 'Egzemplarz o podanym kodzie nie istnieje'], 404);
            }

            if ($preferredCopy->getStatus() === BookCopy::STATUS_BORROWED) {
                $activeLoan = $loanRepo->findActiveByInventoryCode($inventoryCode);
                return $this->json([
                    'error' => 'Egzemplarz jest już wypożyczony',
                    'borrowerId' => $activeLoan ? $activeLoan->getUser()->getId() : null,
                ], 409);
            }

            $assignedReservation = $reservationRepo->findActiveByCopy($preferredCopy);
            if ($assignedReservation && $assignedReservation->getUser()->getId() !== $user->getId()) {
                return $this->json(['error' => 'Egzemplarz jest zarezerwowany dla innego czytelnika'], 409);
            }

            $book = $preferredCopy->getBook();
            if ($bookId !== null && ctype_digit((string) $bookId) && (int) $bookId !== $book->getId()) {
                return $this->json(['error' => 'Podany bookId nie pasuje do wskazanego egzemplarza'], 400);
            }
            $bookId = (string) $book->getId();
        }

        if ($book === null) {
            $book = $bookRepo->find((int) $bookId);
        }

        if (!$book) {
            return $this->json(['error' => 'Book not found'], 404);
        }

        $isLibrarian = $security->hasRole($request, 'ROLE_LIBRARIAN');
        if (!$isLibrarian) {
            if (!isset($payload['sub']) || (int) $payload['sub'] !== (int) $userId) {
                return $this->json(['error' => 'Forbidden'], 403);
            }
        }

        $reservation = $assignedReservation ?? $reservationRepo->findFirstActiveForUserAndBook($user, $book);
        $order = $orderRepo->findReadyForUserAndBook($user, $book);

        if ($order) {
            $orderLifecycle->expireOrders([$order]);
            if (in_array($order->getStatus(), [OrderRequest::STATUS_READY, OrderRequest::STATUS_PENDING], true) && $order->getBookCopy()) {
                if ($preferredCopy === null) {
                    $preferredCopy = $order->getBookCopy();
                }
            } else {
                $order = null;
            }
        }

        $copy = $bookService->borrow($book, $reservation, $preferredCopy);
        if (!$copy) {
            $queue = $reservationRepo->findActiveByBook($book);
            if (!empty($queue) && (!$isLibrarian || $queue[0]->getUser()->getId() !== $user->getId())) {
                return $this->json(['error' => 'Book reserved by another reader'], 409);
            }

            $ordersQueue = $orderRepo->findActiveByBook($book);
            if (!empty($ordersQueue) && (!$isLibrarian || $ordersQueue[0]->getUser()->getId() !== $user->getId())) {
                return $this->json(['error' => 'Book ordered by another reader'], 409);
            }

            return $this->json(['error' => 'No copies available'], 409);
        }

        $loan = (new Loan())
            ->setBook($book)
            ->setBookCopy($copy)
            ->setUser($user)
            ->setDueAt((new \DateTimeImmutable())->modify("+{$days} days"));

        $em = $doctrine->getManager();
        $em->persist($loan);
        if ($reservation) {
            $em->persist($reservation);
        }
        if ($order) {
            $order->markCollected()->setBookCopy($copy);
            $em->persist($order);
        }
        $em->flush();

        return $this->json($loan, 201, [], ['groups' => ['loan:read']]);
    }

    public function listByUser(string $id, Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!ctype_digit($id) || (int)$id <= 0) {
            return $this->json(['error' => 'Invalid id parameter'], 400);
        }

        $userId = (int)$id;
        $payload = $security->getJwtPayload($request);
        $isLibrarian = $security->hasRole($request, 'ROLE_LIBRARIAN');
        $isOwner = $payload && isset($payload['sub']) && (int)$payload['sub'] === $userId;

        if (!($isLibrarian || $isOwner)) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $repo = $doctrine->getRepository(Loan::class);
        $loans = $repo->findBy(['user' => $userId]);
        if (empty($loans)) {
            return new JsonResponse(null, 204);
        }

        return $this->json($loans, 200, [], ['groups' => ['loan:read']]);
    }

    public function returnLoan(string $id, Request $request, ManagerRegistry $doctrine, BookService $bookService, SecurityService $security): JsonResponse
    {
        if (!ctype_digit($id) || (int)$id <= 0) return $this->json(['error' => 'Invalid id parameter'], 400);
        $repo = $doctrine->getRepository(Loan::class);
        $loan = $repo->find((int)$id);
        if (!$loan) return $this->json(['error' => 'Loan not found'], 404);
        // only librarian or the user who borrowed may mark as returned
        $payload = $security->getJwtPayload($request);
        $isLibrarian = $security->hasRole($request, 'ROLE_LIBRARIAN');
        $isOwner = $payload && isset($payload['sub']) && $payload['sub'] == $loan->getUser()->getId();
        if (!($isLibrarian || $isOwner)) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        if ($loan->getReturnedAt() !== null) return $this->json(['error' => 'Loan already returned'], 400);
        $loan->setReturnedAt(new \DateTimeImmutable());
        $bookService->restore($loan->getBook(), $loan->getBookCopy());

        // check reservations waiting for this book
        /** @var \App\Repository\ReservationRepository $reservationRepo */
        $reservationRepo = $doctrine->getRepository(Reservation::class);
        $queue = $reservationRepo->findActiveByBook($loan->getBook());
        $copy = $loan->getBookCopy();
        if ($copy && !empty($queue)) {
            $nextReservation = $queue[0];
            $copy->setStatus(BookCopy::STATUS_RESERVED);
            $nextReservation->assignBookCopy($copy);
            $nextReservation->setExpiresAt((new \DateTimeImmutable())->modify('+2 days'));
            $em = $doctrine->getManager();
            $loan->getBook()->recalculateInventoryCounters();
            $em->persist($copy);
            $em->persist($nextReservation);
            $em->persist($loan->getBook());
            $em->persist($loan);
            $em->flush();
        } else {
            $em = $doctrine->getManager();
            $em->persist($loan);
            $em->flush();
        }

        return $this->json($loan, 200, [], ['groups' => ['loan:read']]);
    }

    public function extend(string $id, Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!ctype_digit($id) || (int)$id <= 0) {
            return $this->json(['error' => 'Invalid id parameter'], 400);
        }

        $loan = $doctrine->getRepository(Loan::class)->find((int)$id);
        if (!$loan) {
            return $this->json(['error' => 'Loan not found'], 404);
        }

        $payload = $security->getJwtPayload($request);
        $isLibrarian = $security->hasRole($request, 'ROLE_LIBRARIAN');
        $isOwner = $payload && isset($payload['sub']) && (int)$payload['sub'] === $loan->getUser()->getId();

        if (!($isLibrarian || $isOwner)) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        if ($loan->getReturnedAt() !== null) {
            return $this->json(['error' => 'Nie można przedłużyć zwróconego wypożyczenia'], 400);
        }

        if (!$isLibrarian && $loan->getExtensionsCount() >= 1) {
            return $this->json(['error' => 'Limit przedłużeń został wykorzystany'], 400);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $days = isset($data['days']) ? (int)$data['days'] : 14;
        $days = max(1, min(30, $days));

        /** @var \App\Repository\ReservationRepository $reservationRepo */
        $reservationRepo = $doctrine->getRepository(Reservation::class);
        $queue = $reservationRepo->findActiveByBook($loan->getBook());
        foreach ($queue as $reservation) {
            if ($reservation->getUser()->getId() !== $loan->getUser()->getId()) {
                return $this->json(['error' => 'Nie można przedłużyć: inny czytelnik oczekuje w kolejce'], 409);
            }
        }

        /** @var \App\Repository\OrderRequestRepository $orderRepo */
        $orderRepo = $doctrine->getRepository(OrderRequest::class);
        $ordersQueue = $orderRepo->findActiveByBook($loan->getBook());
        foreach ($ordersQueue as $order) {
            if ($order->getUser()->getId() !== $loan->getUser()->getId()) {
                return $this->json(['error' => 'Nie można przedłużyć: egzemplarz został zamówiony do odbioru'], 409);
            }
        }

        $dueAt = \DateTimeImmutable::createFromInterface($loan->getDueAt());
        $loan->setDueAt($dueAt->modify('+' . $days . ' days'));
        $loan->incrementExtensions()->setLastExtendedAt(new \DateTimeImmutable());

        $em = $doctrine->getManager();
        $em->persist($loan);
        $em->flush();

        return $this->json($loan, 200, [], ['groups' => ['loan:read']]);
    }

    public function delete(string $id, Request $request, ManagerRegistry $doctrine, BookService $bookService, SecurityService $security): JsonResponse
    {
        // only librarians may delete loans
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        if (!ctype_digit($id) || (int)$id <= 0) return $this->json(['error' => 'Invalid id parameter'], 400);
        $repo = $doctrine->getRepository(Loan::class);
        $loan = $repo->find((int)$id);
        if (!$loan) return $this->json(['error' => 'Loan not found'], 404);
        // if not returned, restore copy
        if ($loan->getReturnedAt() === null) {
            $bookService->restore($loan->getBook(), $loan->getBookCopy());
        }
        $em = $doctrine->getManager();
        $em->remove($loan);
        $em->flush();
        return new JsonResponse(null, 204);
    }
}
