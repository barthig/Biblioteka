<?php
namespace App\Controller;

use App\Entity\Loan;
use App\Service\BookService;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Book;
use App\Entity\User;

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
        $loanRepo = $doctrine->getRepository(Loan::class);
        $user = $userRepo->find((int)$userId);
        $book = $bookRepo->find((int)$bookId);
        if (!$user) return $this->json(['error' => 'User not found'], 404);
        if (!$book) return $this->json(['error' => 'Book not found'], 404);

        $openLoan = $loanRepo->findOneBy(['book' => $book, 'returnedAt' => null]);
        if ($openLoan) {
            return $this->json(['error' => 'Book already borrowed'], 409);
        }

        // only allow creating a loan on behalf of another user when librarian
        $payload = $security->getJwtPayload($request);
        $isLibrarian = $security->hasRole($request, 'ROLE_LIBRARIAN');
        if (!$isLibrarian) {
            if (!$payload || !isset($payload['sub']) || (int)$payload['sub'] !== (int)$userId) {
                return $this->json(['error' => 'Forbidden'], 403);
            }
        }

        // attempt borrow
        $ok = $bookService->borrow($book);
        if (!$ok) return $this->json(['error' => 'No copies available'], 400);

        $loan = new Loan();
        $loan->setBook($book)->setUser($user)->setDueAt((new \DateTimeImmutable())->modify("+{$days} days"));
        $em = $doctrine->getManager();
        $em->persist($loan);
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
        $bookService->restore($loan->getBook());
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
            $bookService->restore($loan->getBook());
        }
        $em = $doctrine->getManager();
        $em->remove($loan);
        $em->flush();
        return new JsonResponse(null, 204);
    }
}
