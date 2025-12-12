<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Loan\ExtendLoanCommand;
use App\Entity\Loan;
use App\Repository\LoanRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ExtendLoanHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoanRepository $loanRepository,
        private ReservationRepository $reservationRepository
    ) {
    }

    public function __invoke(ExtendLoanCommand $command): Loan
    {
        $loan = $this->loanRepository->find($command->loanId);
        if (!$loan) {
            throw new \RuntimeException('Loan not found');
        }

        // Authorization check happens in controller

        if ($loan->getReturnedAt() !== null) {
            throw new \RuntimeException('Cannot extend returned loan');
        }

        if ($loan->getExtensionsCount() > 0) {
            throw new \RuntimeException('Wypożyczenie zostało już przedłużone');
        }

        // Check if book is reserved by someone else
        $reservations = $this->reservationRepository->findActiveByBook($loan->getBook());
        if (!empty($reservations)) {
            throw new \RuntimeException('Nie można przedłużyć - książka jest zarezerwowana');
        }

        $currentDue = $loan->getDueAt();
        $newDue = $currentDue->modify('+14 days');
        $loan->setDueAt($newDue);
        $loan->incrementExtensions();
        $loan->setLastExtendedAt(new \DateTimeImmutable());

        $this->em->persist($loan);
        $this->em->flush();

        return $loan;
    }
}
