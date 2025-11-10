<?php
namespace App\Controller;

use App\Entity\Book;
use App\Entity\Reservation;
use App\Entity\BookCopy;
use App\Entity\User;
use App\Message\ReservationQueuedNotification;
use App\Repository\ReservationRepository;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;

class ReservationController extends AbstractController
{
    public function list(Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        /** @var ReservationRepository $repo */
        $repo = $doctrine->getRepository(Reservation::class);

        if ($security->hasRole($request, 'ROLE_LIBRARIAN')) {
            $qb = $repo->createQueryBuilder('r')
                ->orderBy('r.reservedAt', 'DESC');

            $status = $request->query->get('status');
            if ($status !== null && in_array(strtoupper($status), [
                Reservation::STATUS_ACTIVE,
                Reservation::STATUS_CANCELLED,
                Reservation::STATUS_FULFILLED,
                Reservation::STATUS_EXPIRED,
            ], true)) {
                $qb->andWhere('r.status = :status')->setParameter('status', strtoupper($status));
            }

            if ($request->query->has('userId') && ctype_digit((string) $request->query->get('userId'))) {
                $qb->andWhere('r.user = :userId')->setParameter('userId', (int) $request->query->get('userId'));
            }

            $reservations = $qb->getQuery()->getResult();
            return $this->json($reservations, 200, [], ['groups' => ['reservation:read']]);
        }

        $payload = $security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $user = $doctrine->getRepository(User::class)->find((int) $payload['sub']);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $includeHistory = $request->query->getBoolean('history', false);
        $reservations = $repo->findByUser($user, $includeHistory);
        return $this->json($reservations, 200, [], ['groups' => ['reservation:read']]);
    }

    public function create(Request $request, ManagerRegistry $doctrine, SecurityService $security, MessageBusInterface $bus): JsonResponse
    {
        $payload = $security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $bookId = $data['bookId'] ?? null;
        $expiresInDays = isset($data['days']) ? (int) $data['days'] : 2;

        if (!$bookId || !ctype_digit((string) $bookId)) {
            return $this->json(['error' => 'Invalid bookId'], 400);
        }

        $userRepo = $doctrine->getRepository(User::class);
        $bookRepo = $doctrine->getRepository(Book::class);
        /** @var ReservationRepository $reservationRepo */
        $reservationRepo = $doctrine->getRepository(Reservation::class);

        $user = $userRepo->find((int) $payload['sub']);
        $book = $bookRepo->find((int) $bookId);
        if (!$user || !$book) {
            return $this->json(['error' => 'User or book not found'], 404);
        }

        if ($book->getCopies() > 0) {
            return $this->json(['error' => 'Book currently available, wypożycz zamiast rezerwować'], 400);
        }

        if ($reservationRepo->findFirstActiveForUserAndBook($user, $book)) {
            return $this->json(['error' => 'Masz już aktywną rezerwację na tę książkę'], 409);
        }

        $reservation = (new Reservation())
            ->setBook($book)
            ->setUser($user)
            ->setExpiresAt((new \DateTimeImmutable())->modify("+{$expiresInDays} days"));

        $em = $doctrine->getManager();
        $em->persist($reservation);
        $em->flush();

        $bus->dispatch(new ReservationQueuedNotification(
            $reservation->getId(),
            $book->getId(),
            $user->getEmail()
        ));

        return $this->json($reservation, 201, [], ['groups' => ['reservation:read']]);
    }

    public function cancel(string $id, Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid reservation id'], 400);
        }

        /** @var ReservationRepository $repo */
        $repo = $doctrine->getRepository(Reservation::class);
        $reservation = $repo->find((int) $id);
        if (!$reservation) {
            return $this->json(['error' => 'Reservation not found'], 404);
        }

        $payload = $security->getJwtPayload($request);
        $isLibrarian = $security->hasRole($request, 'ROLE_LIBRARIAN');
        $isOwner = $payload && isset($payload['sub']) && (int) $payload['sub'] === $reservation->getUser()->getId();

        if (!($isLibrarian || $isOwner)) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        if ($reservation->getStatus() === Reservation::STATUS_FULFILLED) {
            return $this->json(['error' => 'Reservation already fulfilled'], 400);
        }

        $reservation->cancel();
        $copy = $reservation->getBookCopy();
        if ($copy) {
            $copy->setStatus(BookCopy::STATUS_AVAILABLE);
            $reservation->clearBookCopy();
            $reservation->getBook()->recalculateInventoryCounters();
        }

        $em = $doctrine->getManager();
        $em->persist($reservation);
        if ($copy) {
            $em->persist($copy);
            $em->persist($reservation->getBook());
        }
        $em->flush();

        return new JsonResponse(null, 204);
    }
}
