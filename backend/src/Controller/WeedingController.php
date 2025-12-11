<?php
namespace App\Controller;

use App\Controller\Traits\ValidationTrait;
use App\Entity\Book;
use App\Entity\BookCopy;
use App\Entity\WeedingRecord;
use App\Entity\User;
use App\Entity\Loan;
use App\Entity\Reservation;
use App\Repository\LoanRepository;
use App\Repository\ReservationRepository;
use App\Request\CreateWeedingRecordRequest;
use App\Service\BookService;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class WeedingController extends AbstractController
{
    use ValidationTrait;
    public function list(Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        /** @var \App\Repository\WeedingRecordRepository $repo */
        $repo = $doctrine->getRepository(WeedingRecord::class);
        $limit = $request->query->getInt('limit', 200);
        $limit = max(1, min(500, $limit));
        $records = $repo->findRecent($limit);

        return $this->json($records, 200, [], ['groups' => ['weeding:read', 'book:read', 'inventory:read']]);
    }

    public function create(Request $request, ManagerRegistry $doctrine, BookService $bookService, SecurityService $security, ValidatorInterface $validator): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        
        // Walidacja DTO
        $dto = $this->mapArrayToDto($data, new CreateWeedingRecordRequest());
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }
        
        $bookId = $dto->bookId;
        $copyId = $dto->copyId;

        $book = $doctrine->getRepository(Book::class)->find((int) $bookId);
        if (!$book) {
            return $this->json(['error' => 'Book not found'], 404);
        }

        $copy = null;
        if ($copyId !== null) {
            $copy = $doctrine->getRepository(BookCopy::class)->find((int) $copyId);
            if (!$copy || $copy->getBook()->getId() !== $book->getId()) {
                return $this->json(['error' => 'Copy does not belong to the book'], 400);
            }
            if ($copy->getStatus() === BookCopy::STATUS_BORROWED) {
                return $this->json(['error' => 'Copy is currently borrowed'], 409);
            }
            if ($copy->getStatus() === BookCopy::STATUS_RESERVED) {
                return $this->json(['error' => 'Copy is reserved'], 409);
            }
            if ($copy->getStatus() === BookCopy::STATUS_WITHDRAWN) {
                return $this->json(['error' => 'Copy already withdrawn'], 409);
            }

            /** @var LoanRepository $loanRepo */
            $loanRepo = $doctrine->getRepository(Loan::class);
            if ($loanRepo instanceof LoanRepository && $loanRepo->findActiveByInventoryCode($copy->getInventoryCode()) !== null) {
                return $this->json(['error' => 'Copy has an active loan'], 409);
            }

            /** @var ReservationRepository $reservationRepo */
            $reservationRepo = $doctrine->getRepository(Reservation::class);
            if ($reservationRepo instanceof ReservationRepository && $reservationRepo->findActiveByCopy($copy) !== null) {
                return $this->json(['error' => 'Copy has an active reservation'], 409);
            }
        }

        $record = (new WeedingRecord())
            ->setBook($book)
            ->setBookCopy($copy)
            ->setReason((string) $data['reason']);

        if (!empty($data['action'])) {
            $record->setAction((string) $data['action']);
        }
        if (isset($data['conditionState'])) {
            $record->setConditionState($data['conditionState']);
        }
        if (isset($data['notes'])) {
            $record->setNotes($data['notes']);
        }
        if (!empty($data['removedAt']) && strtotime((string) $data['removedAt'])) {
            $record->setRemovedAt(new \DateTimeImmutable((string) $data['removedAt']));
        }

        $payload = $security->getJwtPayload($request);
        if ($payload && isset($payload['sub']) && ctype_digit((string) $payload['sub'])) {
            $user = $doctrine->getRepository(User::class)->find((int) $payload['sub']);
            if ($user) {
                $record->setProcessedBy($user);
            }
        }

        $em = $doctrine->getManager();
        /** @var EntityManagerInterface $em */
        $conn = $em->getConnection();

        $conn->beginTransaction();
        try {
            if ($copy) {
                $bookService->withdrawCopy($book, $copy, $data['conditionState'] ?? null, false);
            } else {
                $book->recalculateInventoryCounters();
                $em->persist($book);
            }

            $em->persist($record);
            $em->flush();
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            return $this->json(['error' => 'Błąd podczas tworzenia rekordu selekcji'], 500);
        }

        return $this->json($record, 201, [], ['groups' => ['weeding:read', 'book:read', 'inventory:read']]);
    }
}
