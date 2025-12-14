<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Loan\CreateLoanCommand;
use App\Application\Command\Reservation\FulfillReservationCommand;
use App\Application\Command\Reservation\FulfillReservationWorkflowCommand;
use App\Entity\BookCopy;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'command.bus')]
class FulfillReservationWorkflowHandler
{
    public function __construct(
        private ReservationRepository $reservationRepository,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $commandBus
    ) {
    }

    public function __invoke(FulfillReservationWorkflowCommand $command): void
    {
        $reservation = $this->reservationRepository->find($command->reservationId);
        if (!$reservation) {
            throw new NotFoundHttpException('Reservation not found');
        }

        if ($reservation->getStatus() !== 'ACTIVE') {
            throw new BadRequestHttpException('Reservation is not active');
        }

        $now = new \DateTimeImmutable();
        if ($reservation->getExpiresAt() && $reservation->getExpiresAt() < $now) {
            throw new BadRequestHttpException('Reservation has expired');
        }

        $copy = $reservation->getBookCopy();
        if (!$copy) {
            throw new BadRequestHttpException('No book copy assigned to this reservation');
        }

        if ($copy->getStatus() !== BookCopy::STATUS_RESERVED) {
            throw new BadRequestHttpException('Book copy is not in RESERVED status');
        }

        $this->entityManager->beginTransaction();
        try {
            $loanEnvelope = $this->commandBus->dispatch(
                new CreateLoanCommand(
                    userId: $reservation->getUser()->getId(),
                    bookId: $copy->getBook()?->getId() ?? 0,
                    reservationId: $reservation->getId(),
                    bookCopyId: $copy->getId()
                )
            );
            $loan = $loanEnvelope->last(\Symfony\Component\Messenger\Stamp\HandledStamp::class)?->getResult();

            $this->commandBus->dispatch(
                new FulfillReservationCommand(
                    reservationId: $reservation->getId(),
                    loanId: $loan->getId()
                )
            );

            $this->entityManager->commit();
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }
}
