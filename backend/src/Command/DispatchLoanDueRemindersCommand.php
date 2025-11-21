<?php
namespace App\Command;

use App\Message\LoanDueReminderMessage;
use App\Repository\LoanRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'notifications:dispatch-due-reminders', description: 'Queue reminders for loans that are due soon.')]
class DispatchLoanDueRemindersCommand extends Command
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
            ->addOption('days', null, InputOption::VALUE_REQUIRED, 'Number of days ahead to look for due dates', '2')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'List loans without dispatching notifications');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $daysAhead = max(1, (int) $input->getOption('days'));
        $dryRun = (bool) $input->getOption('dry-run');

        $now = new \DateTimeImmutable();
        $rangeEnd = $now->modify(sprintf('+%d days', $daysAhead))->setTime(23, 59, 59);

        $loans = $this->loanRepository->findDueBetween($now, $rangeEnd);
        if (empty($loans)) {
            $output->writeln('<info>No loans due within the selected window.</info>');
            return Command::SUCCESS;
        }

        $dispatched = 0;
        foreach ($loans as $loan) {
            $message = new LoanDueReminderMessage(
                $loan->getId(),
                $loan->getUser()->getId(),
                $loan->getDueAt()->format(DATE_ATOM)
            );

            if ($dryRun) {
                $output->writeln(sprintf('- [DRY] Loan #%d due %s for user #%d', $loan->getId(), $loan->getDueAt()->format('Y-m-d'), $loan->getUser()->getId()));
            } else {
                $this->bus->dispatch($message);
            }

            $dispatched++;
        }

        $verb = $dryRun ? 'would be dispatched' : 'dispatched';
        $output->writeln(sprintf('<info>%d reminder(s) %s.</info>', $dispatched, $verb));

        return Command::SUCCESS;
    }
}
