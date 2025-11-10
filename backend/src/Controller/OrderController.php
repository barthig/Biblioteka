<?php
namespace App\Controller;

use App\Entity\Book;
use App\Entity\BookCopy;
use App\Entity\OrderRequest;
use App\Entity\Reservation;
use App\Entity\User;
use App\Repository\OrderRequestRepository;
use App\Service\BookService;
use App\Service\OrderLifecycleService;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class OrderController extends AbstractController
{
    public function list(Request $request, ManagerRegistry $doctrine, SecurityService $security, OrderLifecycleService $orderLifecycle): JsonResponse
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
            $orderLifecycle->expireOrders($orders);
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
        $expiredCount = $orderLifecycle->expireOrders($orders);

        if (!$includeHistory && $expiredCount > 0) {
            $orders = array_values(array_filter(
                $orders,
                static fn (OrderRequest $order): bool => in_array($order->getStatus(), [OrderRequest::STATUS_PENDING, OrderRequest::STATUS_READY], true)
            ));
        }

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
        $pickupType = strtoupper(trim($data['pickupType'] ?? OrderRequest::PICKUP_STORAGE_DESK));
        $holdDays = isset($data['days']) ? max(1, (int) $data['days']) : 2;

        if (!$bookId || !ctype_digit((string) $bookId)) {
            return $this->json(['error' => 'Invalid bookId'], 400);
        }

        if (!in_array($pickupType, OrderRequest::PICKUP_TYPES, true)) {
            return $this->json(['error' => 'Invalid pickup type'], 400);
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

        $accessFilter = $pickupType === OrderRequest::PICKUP_OPEN_SHELF
            ? [BookCopy::ACCESS_OPEN_STACK]
            : [BookCopy::ACCESS_STORAGE];

        if ($pickupType === OrderRequest::PICKUP_STORAGE_DESK && $book->getStorageCopies() <= 0) {
            return $this->json(['error' => 'Brak egzemplarzy w magazynie. Spróbuj wypożyczyć z wolnego dostępu.'], 409);
        }

        if ($pickupType === OrderRequest::PICKUP_OPEN_SHELF && $book->getOpenStackCopies() <= 0) {
            return $this->json(['error' => 'Brak egzemplarzy na półce, spróbuj wypożyczyć bezpośrednio lub zamów z magazynu.'], 409);
        }

        $reservedCopy = $bookService->reserveCopy($book, $accessFilter);
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

    public function cancel(string $id, Request $request, ManagerRegistry $doctrine, SecurityService $security, OrderLifecycleService $orderLifecycle): JsonResponse
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

        if (in_array($order->getStatus(), [OrderRequest::STATUS_CANCELLED, OrderRequest::STATUS_COLLECTED, OrderRequest::STATUS_EXPIRED], true)) {
            return $this->json(['error' => 'Zamówienie jest już zamknięte'], 400);
        }

        $em = $doctrine->getManager();
        $orderLifecycle->releaseCopy($order);
        $order->cancel();
        $em->persist($order);
        $em->flush();

        return new JsonResponse(null, 204);
    }
}
