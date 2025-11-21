<?php
namespace App\Command;

use App\Entity\BookCopy;
use App\Entity\Reservation;
use App\Message\ReservationReadyMessage;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'reservations:expire-ready', description: 'Expire reservations that were ready for pickup but were not collected on time.')]
class ExpireReadyReservationsCommand extends Command
{
    public function __construct(
        private ReservationRepository $reservations,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $bus,
        private LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('pickup-hours', null, InputOption::VALUE_REQUIRED, 'How long the next reader has to collect the copy (in hours)', '48')
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Process at most N expired reservations per run (0 = unlimited)', '0')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Preview without modifying data');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pickupHours = max(1, (int) $input->getOption('pickup-hours'));
        $batchSize = max(0, (int) $input->getOption('batch-size'));
        $dryRun = (bool) $input->getOption('dry-run');

        $now = new \DateTimeImmutable();
        $expired = $this->reservations->findExpiredReady($now, $batchSize > 0 ? $batchSize : null);

        if (empty($expired)) {
            $output->writeln('<info>No reservations expired since the last run.</info>');
            return Command::SUCCESS;
        }

        $expiredCount = 0;
        $reassignedCount = 0;

        foreach ($expired as $reservation) {
            $copy = $reservation->getBookCopy();
            if (!$copy) {
                continue;
            }

            $book = $reservation->getBook();
            $reservation->expire()->clearBookCopy();

            if ($dryRun) {
                $output->writeln(sprintf('[DRY] Reservation #%d expired, copy %s released.', $reservation->getId(), $copy->getInventoryCode()));
            } else {
                $copy->setStatus(BookCopy::STATUS_AVAILABLE);
                $book->recalculateInventoryCounters();
                $this->entityManager->persist($copy);
                $this->entityManager->persist($book);
                $this->entityManager->persist($reservation);
            }

            ++$expiredCount;

            if ($dryRun) {
                continue;
            }

            $queue = $this->reservations->findActiveByBook($book);
            $nextReservation = null;
            foreach ($queue as $candidate) {
                if ($candidate->getId() === $reservation->getId()) {
                    continue;
                }
                $nextReservation = $candidate;
                break;
            }

            if ($nextReservation === null) {
                continue;
            }

            $copy->setStatus(BookCopy::STATUS_RESERVED);
            $book->recalculateInventoryCounters();

            $nextReservation->assignBookCopy($copy);
            $nextReservation->setExpiresAt($now->modify(sprintf('+%d hours', $pickupHours)));
            $this->entityManager->persist($nextReservation);

            try {
                $this->bus->dispatch(new ReservationReadyMessage(
                    $nextReservation->getId(),
                    $nextReservation->getUser()->getId(),
                    $nextReservation->getExpiresAt()->format(DATE_ATOM)
                ));
            } catch (\Throwable $exception) {
                $this->logger->warning('Failed to dispatch ReservationReadyMessage after expiration.', [
                    'reservationId' => $nextReservation->getId(),
                    'error' => $exception->getMessage(),
                ]);
            }

            ++$reassignedCount;
        }

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        $output->writeln(sprintf(
            '<info>Expired %d reservation(s); reassigned %d copy(ies) to the next readers.</info>',
            $expiredCount,
            $reassignedCount
        ));

        return Command::SUCCESS;
    }
}
