<?php
declare(strict_types=1);
namespace App\Command;

use App\Message\LoanOverdueMessage;
use App\Repository\LoanRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'notifications:dispatch-overdue-warnings', description: 'Queue warnings for loans that are already overdue.')]
class DispatchLoanOverdueWarningsCommand extends Command
{
    public function __construct(
        private LoanRepository $loanRepository,
        private MessageBusInterface $bus
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('threshold', null, InputOption::VALUE_REQUIRED, 'Minimum number of days overdue', '1')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'List loans without dispatching notifications');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $threshold = max(0, (int) $input->getOption('threshold'));
        $dryRun = (bool) $input->getOption('dry-run');

        $now = new \DateTimeImmutable();
        $cutoff = $threshold > 0 ? $now->modify(sprintf('-%d days', $threshold)) : $now;

        $loans = $this->loanRepository->findOverdueSince($cutoff);
        if (empty($loans)) {
            $output->writeln('<info>No overdue loans that match the threshold.</info>');
            return Command::SUCCESS;
        }

        $dispatched = 0;
        foreach ($loans as $loan) {
            $interval = $loan->getDueAt()->diff($now);
            $daysLate = max(1, (int) $interval->format('%a'));
            $message = new LoanOverdueMessage(
                $loan->getId(),
                $loan->getUser()->getId(),
                $loan->getDueAt()->format(DATE_ATOM),
                $daysLate
            );

            if ($dryRun) {
                $output->writeln(sprintf('- [DRY] Loan #%d overdue by %d day(s) for user #%d', $loan->getId(), $daysLate, $loan->getUser()->getId()));
            } else {
                $this->bus->dispatch($message);
            }

            $dispatched++;
        }

        $verb = $dryRun ? 'would be dispatched' : 'dispatched';
        $output->writeln(sprintf('<info>%d overdue warning(s) %s.</info>', $dispatched, $verb));

        return Command::SUCCESS;
    }
}
