<?php
declare(strict_types=1);
namespace App\Command;

use App\Entity\Reservation;
use App\Message\ReservationReadyMessage;
use App\Repository\ReservationRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'notifications:dispatch-reservation-ready', description: 'Queue notifications for reservations that are ready for pickup.')]
class DispatchReservationReadyCommand extends Command
{
    public function __construct(
        private ReservationRepository $reservationRepository,
        private MessageBusInterface $bus
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'List reservations without dispatching notifications');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun = (bool) $input->getOption('dry-run');
        $now = new \DateTimeImmutable();

        $reservations = $this->reservationRepository->findReadyForPickup();
        if (empty($reservations)) {
            $output->writeln('<info>No reservations waiting for pickup.</info>');
            return Command::SUCCESS;
        }

        $dispatched = 0;
        foreach ($reservations as $reservation) {
            if ($reservation->getBookCopy() === null || $reservation->getStatus() !== Reservation::STATUS_ACTIVE) {
                continue;
            }

            if ($reservation->getExpiresAt() <= $now) {
                continue;
            }

            $message = new ReservationReadyMessage(
                $reservation->getId(),
                $reservation->getUser()->getId(),
                $reservation->getExpiresAt()->format(DATE_ATOM)
            );

            if ($dryRun) {
                $output->writeln(sprintf('- [DRY] Reservation #%d ready until %s for user #%d', $reservation->getId(), $reservation->getExpiresAt()->format('Y-m-d H:i'), $reservation->getUser()->getId()));
            } else {
                $this->bus->dispatch($message);
            }

            $dispatched++;
        }

        if ($dispatched === 0) {
            $output->writeln('<comment>No eligible reservations to notify.</comment>');
            return Command::SUCCESS;
        }

        $verb = $dryRun ? 'would be dispatched' : 'dispatched';
        $output->writeln(sprintf('<info>%d reservation notification(s) %s.</info>', $dispatched, $verb));

        return Command::SUCCESS;
    }
}
