<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Loan\ExtendLoanCommand;
use App\Entity\Loan;
use App\Event\LoanExtendedEvent;
use App\Exception\BusinessLogicException;
use App\Exception\NotFoundException;
use App\Repository\LoanRepository;
use App\Repository\ReservationRepository;
use App\Service\System\SystemSettingsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
class ExtendLoanHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoanRepository $loanRepository,
        private ReservationRepository $reservationRepository,
        private SystemSettingsService $settingsService,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(ExtendLoanCommand $command): Loan
    {
        $loan = $this->loanRepository->find($command->loanId);
        if (!$loan) {
            throw NotFoundException::forLoan($command->loanId);
        }

        // Authorization check happens in controller

        if ($loan->getReturnedAt() !== null) {
            throw BusinessLogicException::cannotExtendLoan('loan already returned');
        }

        if ($loan->getExtensionsCount() > 0) {
            throw BusinessLogicException::cannotExtendLoan('loan already extended');
        }

        // Check if book is reserved by someone else
        $reservations = $this->reservationRepository->findActiveByBook($loan->getBook());
        foreach ($reservations as $reservation) {
            if ($reservation->getUser()->getId() !== $loan->getUser()->getId()) {
                throw BusinessLogicException::cannotExtendLoan('book reserved by another reader');
            }
        }

        $loanDurationDays = $this->settingsService->getLoanDurationDays();

        $currentDue = $loan->getDueAt();
        $dueBase = $currentDue instanceof \DateTimeImmutable
            ? $currentDue
            : \DateTimeImmutable::createFromMutable($currentDue);
        $newDue = $dueBase->modify("+{$loanDurationDays} days");
        $loan->setDueAt($newDue);
        $loan->incrementExtensions();
        $loan->setLastExtendedAt(new \DateTimeImmutable());

        $this->entityManager->persist($loan);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new LoanExtendedEvent(
            $loan,
            $dueBase,
            $newDue,
            $loan->getExtensionsCount(),
        ));

        return $loan;
    }
}

