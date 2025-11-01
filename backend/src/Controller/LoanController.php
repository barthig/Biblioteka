<?php
namespace App\Controller;

use App\Entity\Loan;
use App\Repository\BookRepository;
use App\Repository\UserRepository;
use App\Service\BookService;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Book;
use App\Entity\User;

class LoanController extends AbstractController
{
    #[Route('/api/loans', name: 'api_loans_list', methods: ['GET'])]
    public function list(ManagerRegistry $doctrine): JsonResponse
    {
        $repo = $doctrine->getRepository(Loan::class);
        $loans = $repo->findAll();
        return $this->json($loans, 200);
    }

    #[Route('/api/loans/{id}', name: 'api_loans_get', methods: ['GET'])]
    public function getLoan(string $id, ManagerRegistry $doctrine): JsonResponse
    {
        if (!ctype_digit($id) || (int)$id <= 0) return $this->json(['error' => 'Invalid id parameter'], 400);
        $repo = $doctrine->getRepository(Loan::class);
        $loan = $repo->find((int)$id);
        if (!$loan) return $this->json(['error' => 'Loan not found'], 404);
        return $this->json($loan, 200);
    }

    #[Route('/api/loans', name: 'api_loans_create', methods: ['POST'])]
    public function create(Request $request, ManagerRegistry $doctrine, BookService $bookService, SecurityService $security): JsonResponse
    {
        // require an authenticated user (JWT) or API secret
        $payload = $security->getJwtPayload($request);
        if ($payload === null) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }
        $data = json_decode($request->getContent(), true) ?: [];
        $userId = $data['userId'] ?? null;
        $bookId = $data['bookId'] ?? null;
        $days = isset($data['days']) ? (int)$data['days'] : 14;

        if (!$userId || !$bookId || !ctype_digit((string)$userId) || !ctype_digit((string)$bookId)) {
            return $this->json(['error' => 'Missing or invalid userId/bookId'], 400);
        }

        $userRepo = $doctrine->getRepository(User::class);
        $bookRepo = $doctrine->getRepository(Book::class);
        $user = $userRepo->find((int)$userId);
        $book = $bookRepo->find((int)$bookId);
        if (!$user) return $this->json(['error' => 'User not found'], 404);
        if (!$book) return $this->json(['error' => 'Book not found'], 404);

        // attempt borrow
        $ok = $bookService->borrow($book);
        if (!$ok) return $this->json(['error' => 'No copies available'], 400);

        $loan = new Loan();
        $loan->setBook($book)->setUser($user)->setDueAt((new \DateTimeImmutable())->modify("+{$days} days"));
        $em = $doctrine->getManager();
        $em->persist($loan);
        $em->flush();
        return $this->json($loan, 201);
    }

    #[Route('/api/loans/{id}/return', name: 'api_loans_return', methods: ['PUT'])]
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
        $bookService->restore($loan->getBook());
        $em = $doctrine->getManager();
        $em->persist($loan);
        $em->flush();
        return $this->json($loan, 200);
    }

    #[Route('/api/loans/{id}', name: 'api_loans_delete', methods: ['DELETE'])]
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
            $bookService->restore($loan->getBook());
        }
        $em = $doctrine->getManager();
        $em->remove($loan);
        $em->flush();
        return $this->json(null, 204);
    }
}
