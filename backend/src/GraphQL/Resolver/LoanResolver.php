<?php

namespace App\GraphQL\Resolver;

use App\Entity\Loan;
use App\Entity\User;
use App\Repository\LoanRepository;
use App\Repository\BookRepository;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LoanResolver
{
    public function __construct(
        private LoanRepository $loanRepository,
        private BookRepository $bookRepository,
        private Security $security
    ) {
    }

    /**
     * Get current user's loans
     */
    public function getMyLoans(): array
    {
        $user = $this->security->getUser();
        
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Not authenticated');
        }

        $loans = $this->loanRepository->findBy(['user' => $user], ['borrowedAt' => 'DESC']);

        return array_map(fn(Loan $loan) => $this->loanToArray($loan), $loans);
    }

    /**
     * Create a new loan - simplified without LoanService
     */
    public function createLoan(int $bookId): array
    {
        $user = $this->security->getUser();
        
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Not authenticated');
        }

        $book = $this->bookRepository->find($bookId);
        
        if (!$book) {
            throw new NotFoundHttpException('Book not found');
        }

        // Note: This is a simplified version. In production, use LoanService
        throw new \RuntimeException('LoanService not implemented. Use REST API endpoint instead.');
    }

    /**
     * Return a loan - simplified without LoanService
     */
    public function returnLoan(int $loanId): array
    {
        $loan = $this->loanRepository->find($loanId);
        
        if (!$loan) {
            throw new NotFoundHttpException('Loan not found');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || $loan->getUser()->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException('Not authorized to return this loan');
        }

        // Note: This is a simplified version. In production, use LoanService
        throw new \RuntimeException('LoanService not implemented. Use REST API endpoint instead.');
    }

    /**
     * Convert Loan entity to array for GraphQL
     */
    private function loanToArray(Loan $loan): array
    {
        return [
            'id' => $loan->getId(),
            'book' => [
                'id' => $loan->getBook()->getId(),
                'title' => $loan->getBook()->getTitle(),
                'author' => $loan->getBook()->getAuthor(),
                'isbn' => $loan->getBook()->getIsbn(),
            ],
            'user' => [
                'id' => $loan->getUser()->getId(),
                'email' => $loan->getUser()->getEmail(),
                'name' => $loan->getUser()->getName(),
            ],
            'loanDate' => $loan->getBorrowedAt()?->format('c'),
            'dueDate' => $loan->getDueDate()?->format('c'),
            'returnDate' => $loan->getReturnedAt()?->format('c'),
            'status' => $loan->isReturned() ? 'returned' : 'active',
            'renewalCount' => 0,
        ];
    }
}
