<?php
namespace App\Application\Handler\Query;

use App\Application\Query\Loan\GetLoanQuery;
use App\Entity\Loan;
use App\Exception\AuthorizationException;
use App\Repository\LoanRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetLoanHandler
{
    public function __construct(
        private LoanRepository $loanRepository
    ) {
    }

    public function __invoke(GetLoanQuery $query): ?Loan
    {
        $loan = $this->loanRepository->find($query->loanId);
        
        if (!$loan) {
            return null;
        }

        // Authorization check
        if (!$query->isLibrarian && $loan->getUser()->getId() !== $query->userId) {
            throw AuthorizationException::notOwner();
        }

        return $loan;
    }
}
