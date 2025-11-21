<?php
namespace App\Command;

use App\Repository\FineRepository;
use App\Repository\LoanRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'users:block-delinquent', description: 'Automatically block accounts that exceed fine or overdue thresholds.')]
class BlockDelinquentAccountsCommand extends Command
{
    public function __construct(
        private UserRepository $users,
        private FineRepository $fines,
        private LoanRepository $loans,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('fine-limit', null, InputOption::VALUE_REQUIRED, 'Outstanding fine limit required to trigger a block', '50')
            ->addOption('overdue-days', null, InputOption::VALUE_REQUIRED, 'Maximum allowed overdue age before blocking', '30')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Preview the accounts that would be blocked');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fineLimit = max(0.0, (float) $input->getOption('fine-limit'));
        $overdueDays = max(1, (int) $input->getOption('overdue-days'));
        $dryRun = (bool) $input->getOption('dry-run');

        $cutoff = (new \DateTimeImmutable())->modify(sprintf('-%d days', $overdueDays));
        $blocked = 0;

        foreach ($this->users->findAll() as $user) {
            if (in_array('ROLE_LIBRARIAN', $user->getRoles(), true)) {
                continue;
            }

            if ($user->isBlocked()) {
                continue;
            }

            $outstanding = $this->fines->sumOutstandingByUser($user);
            $hasLongOverdue = $this->loans->hasOverdueLongerThan($user, $cutoff);

            if ($outstanding < $fineLimit && !$hasLongOverdue) {
                continue;
            }

            $reasonParts = [];
            if ($outstanding >= $fineLimit) {
                $reasonParts[] = sprintf('kara %.2f PLN', $outstanding);
            }
            if ($hasLongOverdue) {
                $reasonParts[] = sprintf('przetrzymanie > %d dni', $overdueDays);
            }
            $reason = 'Automatyczna blokada: ' . implode(', ', $reasonParts);

            if ($dryRun) {
                $output->writeln(sprintf('[DRY] Would block user #%d (%s): %s', $user->getId(), $user->getEmail(), $reason));
                ++$blocked;
                continue;
            }

            $user->block($reason);
            $this->entityManager->persist($user);
            ++$blocked;
        }

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        if ($blocked === 0) {
            $output->writeln('<info>No accounts matched the blocking criteria.</info>');
        } else {
            $output->writeln(sprintf('<info>%d account(s) %s.</info>', $blocked, $dryRun ? 'would be blocked' : 'blocked'));
        }

        return Command::SUCCESS;
    }
}
