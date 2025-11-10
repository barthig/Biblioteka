<?php
namespace App\Controller;

use App\Entity\Book;
use App\Entity\BookCopy;
use App\Repository\BookRepository;
use App\Repository\BookCopyRepository;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class BookInventoryController extends AbstractController
{
    public function list(int $id, Request $request, BookRepository $bookRepository, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $book = $bookRepository->find($id);
        if (!$book) {
            return $this->json(['error' => 'Book not found'], 404);
        }

        $copies = [];
        foreach ($book->getInventory() as $copy) {
            if (!$copy instanceof BookCopy) {
                continue;
            }
            $copies[] = $this->serializeCopy($copy);
        }

        return $this->json(['items' => $copies]);
    }

    public function create(
        int $id,
        Request $request,
        BookRepository $bookRepository,
        BookCopyRepository $copyRepository,
        ManagerRegistry $doctrine,
        SecurityService $security
    ): JsonResponse {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $book = $bookRepository->find($id);
        if (!$book) {
            return $this->json(['error' => 'Book not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $inventoryCode = isset($data['inventoryCode']) && is_string($data['inventoryCode']) && trim($data['inventoryCode']) !== ''
            ? strtoupper(trim($data['inventoryCode']))
            : $this->generateInventoryCode($copyRepository);

        if (!preg_match('/^[A-Z0-9\-_.]+$/', $inventoryCode)) {
            return $this->json(['error' => 'Invalid inventoryCode format'], 400);
        }

        if ($copyRepository->findOneBy(['inventoryCode' => $inventoryCode])) {
            return $this->json(['error' => 'Inventory code already exists'], 409);
        }

        try {
            $copy = (new BookCopy())
                ->setInventoryCode($inventoryCode)
                ->setStatus($this->normalizeStatus($data['status'] ?? BookCopy::STATUS_AVAILABLE))
                ->setAccessType($this->normalizeAccessType($data['accessType'] ?? BookCopy::ACCESS_STORAGE));
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        if (isset($data['location'])) {
            $copy->setLocation($data['location']);
        }

        if (isset($data['condition'])) {
            $copy->setConditionState($data['condition']);
        }

        $em = $doctrine->getManager();
        $book->addInventoryCopy($copy);
        $em->persist($copy);
        $book->recalculateInventoryCounters();
        $em->flush();

        return $this->json($this->serializeCopy($copy), 201);
    }

    public function update(
        int $id,
        int $copyId,
        Request $request,
        BookRepository $bookRepository,
        BookCopyRepository $copyRepository,
        ManagerRegistry $doctrine,
        SecurityService $security
    ): JsonResponse {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $book = $bookRepository->find($id);
        if (!$book) {
            return $this->json(['error' => 'Book not found'], 404);
        }

        $copy = $copyRepository->find($copyId);
        if (!$copy || $copy->getBook()->getId() !== $book->getId()) {
            return $this->json(['error' => 'Inventory copy not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        if (isset($data['inventoryCode'])) {
            $inventoryCode = strtoupper(trim((string) $data['inventoryCode']));
            if (!preg_match('/^[A-Z0-9\-_.]+$/', $inventoryCode)) {
                return $this->json(['error' => 'Invalid inventoryCode format'], 400);
            }
            $existing = $copyRepository->findOneBy(['inventoryCode' => $inventoryCode]);
            if ($existing && $existing->getId() !== $copy->getId()) {
                return $this->json(['error' => 'Inventory code already exists'], 409);
            }
            $copy->setInventoryCode($inventoryCode);
        }

        if (isset($data['status'])) {
            try {
                $copy->setStatus($this->normalizeStatus($data['status']));
            } catch (\InvalidArgumentException $e) {
                return $this->json(['error' => $e->getMessage()], 400);
            }
        }

        if (isset($data['accessType'])) {
            try {
                $copy->setAccessType($this->normalizeAccessType($data['accessType']));
            } catch (\InvalidArgumentException $e) {
                return $this->json(['error' => $e->getMessage()], 400);
            }
        }

        if (array_key_exists('location', $data)) {
            $copy->setLocation($data['location']);
        }

        if (array_key_exists('condition', $data)) {
            $copy->setConditionState($data['condition']);
        }

        $book->recalculateInventoryCounters();
        $doctrine->getManager()->flush();

        return $this->json($this->serializeCopy($copy));
    }

    public function delete(
        int $id,
        int $copyId,
        Request $request,
        BookRepository $bookRepository,
        BookCopyRepository $copyRepository,
        ManagerRegistry $doctrine,
        SecurityService $security
    ): JsonResponse {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $book = $bookRepository->find($id);
        if (!$book) {
            return $this->json(['error' => 'Book not found'], 404);
        }

        $copy = $copyRepository->find($copyId);
        if (!$copy || $copy->getBook()->getId() !== $book->getId()) {
            return $this->json(['error' => 'Inventory copy not found'], 404);
        }

        $em = $doctrine->getManager();
    $book->removeInventoryCopy($copy);
    $em->remove($copy);
        $book->recalculateInventoryCounters();
        $em->flush();

        return new JsonResponse(null, 204);
    }

    private function serializeCopy(BookCopy $copy): array
    {
        return [
            'id' => $copy->getId(),
            'inventoryCode' => $copy->getInventoryCode(),
            'status' => $copy->getStatus(),
            'accessType' => $copy->getAccessType(),
            'location' => $copy->getLocation(),
            'condition' => $copy->getConditionState(),
            'bookId' => $copy->getBook()->getId(),
        ];
    }

    private function normalizeStatus(string $status): string
    {
        $status = strtoupper(trim($status));
        $valid = [
            BookCopy::STATUS_AVAILABLE,
            BookCopy::STATUS_RESERVED,
            BookCopy::STATUS_BORROWED,
            BookCopy::STATUS_MAINTENANCE,
        ];
        if (!in_array($status, $valid, true)) {
            throw new \InvalidArgumentException('Unsupported status: ' . $status);
        }
        return $status;
    }

    private function normalizeAccessType(string $accessType): string
    {
        $accessType = strtoupper(trim($accessType));
        $valid = [
            BookCopy::ACCESS_STORAGE,
            BookCopy::ACCESS_OPEN_STACK,
            BookCopy::ACCESS_REFERENCE,
        ];
        if (!in_array($accessType, $valid, true)) {
            throw new \InvalidArgumentException('Unsupported accessType: ' . $accessType);
        }
        return $accessType;
    }

    private function generateInventoryCode(BookCopyRepository $repository): string
    {
        do {
            $code = sprintf('AUTO-%s', strtoupper(bin2hex(random_bytes(3))));
        } while ($repository->findOneBy(['inventoryCode' => $code]));

        return $code;
    }
}
