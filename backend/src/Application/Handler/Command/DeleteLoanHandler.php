<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Loan\DeleteLoanCommand;
use App\Repository\LoanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class DeleteLoanHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoanRepository $loanRepository
    ) {
    }

    public function __invoke(DeleteLoanCommand $command): void
    {
        $loan = $this->loanRepository->find($command->loanId);
        
        if (!$loan) {
            throw new NotFoundHttpException('Loan not found');
        }

        // Restore available copy when deleting loan
        $book = $loan->getBook();
        $book->setCopies($book->getCopies() + 1);

        $this->entityManager->remove($loan);
        $this->entityManager->flush();
    }
}
