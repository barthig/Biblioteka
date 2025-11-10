<?php
namespace App\Controller;

use App\Entity\Book;
use App\Entity\OrderRequest;
use App\Entity\Reservation;
use App\Entity\User;
use App\Repository\OrderRequestRepository;
use App\Service\BookService;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class OrderController extends AbstractController
{
    public function list(Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        /** @var OrderRequestRepository $repo */
        $repo = $doctrine->getRepository(OrderRequest::class);

        if ($security->hasRole($request, 'ROLE_LIBRARIAN')) {
            $qb = $repo->createQueryBuilder('o')
                ->orderBy('o.createdAt', 'DESC')
                ->leftJoin('o.book', 'b')->addSelect('b')
                ->leftJoin('o.bookCopy', 'c')->addSelect('c')
                ->leftJoin('o.user', 'u')->addSelect('u');

            $status = $request->query->get('status');
            if ($status) {
                $status = strtoupper((string) $status);
                if (in_array($status, [OrderRequest::STATUS_PENDING, OrderRequest::STATUS_READY, OrderRequest::STATUS_CANCELLED, OrderRequest::STATUS_COLLECTED], true)) {
                    $qb->andWhere('o.status = :status')->setParameter('status', $status);
                }
            }

            if ($request->query->has('userId') && ctype_digit((string) $request->query->get('userId'))) {
                $qb->andWhere('o.user = :userId')->setParameter('userId', (int) $request->query->get('userId'));
            }

            $orders = $qb->getQuery()->getResult();
            return $this->json($orders, 200, [], ['groups' => ['order:read', 'book:read']]);
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
        $qb = $repo->createQueryBuilder('o')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.createdAt', 'DESC')
            ->leftJoin('o.book', 'b')->addSelect('b')
            ->leftJoin('o.bookCopy', 'c')->addSelect('c');

        if (!$includeHistory) {
            $qb->andWhere('o.status IN (:statuses)')->setParameter('statuses', [OrderRequest::STATUS_PENDING, OrderRequest::STATUS_READY]);
        }

        $orders = $qb->getQuery()->getResult();
        return $this->json($orders, 200, [], ['groups' => ['order:read', 'book:read']]);
    }

    public function create(Request $request, ManagerRegistry $doctrine, SecurityService $security, BookService $bookService): JsonResponse
    {
        $payload = $security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $bookId = $data['bookId'] ?? null;
        $pickupType = $data['pickupType'] ?? 'SHELF';
        $holdDays = isset($data['days']) ? max(1, (int) $data['days']) : 2;

        if (!$bookId || !ctype_digit((string) $bookId)) {
            return $this->json(['error' => 'Invalid bookId'], 400);
        }

    $userRepo = $doctrine->getRepository(User::class);
    $bookRepo = $doctrine->getRepository(Book::class);
        /** @var OrderRequestRepository $orderRepo */
        $orderRepo = $doctrine->getRepository(OrderRequest::class);
    /** @var \App\Repository\ReservationRepository $reservationRepo */
    $reservationRepo = $doctrine->getRepository(Reservation::class);

        $user = $userRepo->find((int) $payload['sub']);
        $book = $bookRepo->find((int) $bookId);
        if (!$user || !$book) {
            return $this->json(['error' => 'User or book not found'], 404);
        }

        if ($orderRepo->findActiveForUserAndBook($user, $book)) {
            return $this->json(['error' => 'Masz już aktywne zamówienie na tę książkę'], 409);
        }

        if ($reservationRepo->findFirstActiveForUserAndBook($user, $book)) {
            return $this->json(['error' => 'Masz już aktywną rezerwację na tę książkę'], 409);
        }

        if ($book->getCopies() <= 0) {
            return $this->json(['error' => 'Brak dostępnych egzemplarzy do zamówienia'], 409);
        }

        $reservedCopy = $bookService->reserveCopy($book);
        if (!$reservedCopy) {
            return $this->json(['error' => 'Nie udało się zabezpieczyć egzemplarza, spróbuj ponownie'], 409);
        }

        $order = (new OrderRequest())
            ->setBook($book)
            ->setUser($user)
            ->setBookCopy($reservedCopy)
            ->setPickupType($pickupType)
            ->markReady((new \DateTimeImmutable())->modify('+' . $holdDays . ' days'));

        $em = $doctrine->getManager();
        $em->persist($order);
        $em->flush();

        return $this->json($order, 201, [], ['groups' => ['order:read', 'book:read']]);
    }

    public function cancel(string $id, Request $request, ManagerRegistry $doctrine, SecurityService $security, BookService $bookService): JsonResponse
    {
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid order id'], 400);
        }

        /** @var OrderRequestRepository $repo */
        $repo = $doctrine->getRepository(OrderRequest::class);
        $order = $repo->find((int) $id);
        if (!$order) {
            return $this->json(['error' => 'Order not found'], 404);
        }

        $payload = $security->getJwtPayload($request);
        $isLibrarian = $security->hasRole($request, 'ROLE_LIBRARIAN');
        $isOwner = $payload && isset($payload['sub']) && (int) $payload['sub'] === $order->getUser()->getId();

        if (!($isLibrarian || $isOwner)) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        if ($order->getStatus() === OrderRequest::STATUS_CANCELLED || $order->getStatus() === OrderRequest::STATUS_COLLECTED) {
            return $this->json(['error' => 'Zamówienie jest już zamknięte'], 400);
        }

        $copy = $order->getBookCopy();
        $order->cancel();

        $em = $doctrine->getManager();
        $em->persist($order);
        $em->flush();

        if ($copy && $copy->getStatus() === \App\Entity\BookCopy::STATUS_RESERVED) {
            $bookService->releaseReservedCopy($order->getBook(), $copy);
        }

        return new JsonResponse(null, 204);
    }
}
