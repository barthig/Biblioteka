<?php
declare(strict_types=1);
namespace App\Command;

use App\Entity\Fine;
use App\Repository\FineRepository;
use App\Repository\LoanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'fines:assess-overdue', description: 'Automatically apply overdue fines for loans that are past due date.')]
class AssessOverdueFinesCommand extends Command
{
    public function __construct(
        private LoanRepository $loans,
        private FineRepository $fines,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('daily-rate', null, InputOption::VALUE_REQUIRED, 'Fine amount per overdue day (e.g. 1.50)', '1.50')
            ->addOption('currency', null, InputOption::VALUE_REQUIRED, 'Three-letter currency code', 'PLN')
            ->addOption('grace-days', null, InputOption::VALUE_REQUIRED, 'How many days after due date fines start accruing', '0')
            ->addOption('max-results', null, InputOption::VALUE_REQUIRED, 'Limit processed loans per run (0 = no limit)', '0')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only print summary without persisting changes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dailyRate = (float) $input->getOption('daily-rate');
        if ($dailyRate <= 0) {
            $output->writeln('<error>daily-rate must be greater than zero.</error>');
            return Command::INVALID;
        }

        $currency = strtoupper((string) $input->getOption('currency'));
        if (strlen($currency) !== 3) {
            $output->writeln('<error>currency must be a 3-letter ISO code.</error>');
            return Command::INVALID;
        }

        $graceDays = max(0, (int) $input->getOption('grace-days'));
        $maxResults = max(0, (int) $input->getOption('max-results'));
        $dryRun = (bool) $input->getOption('dry-run');

        $now = new \DateTimeImmutable();
        $since = $graceDays > 0 ? $now->modify(sprintf('-%d days', $graceDays)) : $now;
        $loans = $this->loans->findOverdueSince($since, $maxResults > 0 ? $maxResults : null);

        if (empty($loans)) {
            $output->writeln('<info>No overdue loans matched the criteria.</info>');
            return Command::SUCCESS;
        }

        $created = 0;
        $updated = 0;

        foreach ($loans as $loan) {
            $dueAt = \DateTimeImmutable::createFromInterface($loan->getDueAt());
            $secondsLate = $now->getTimestamp() - $dueAt->getTimestamp();
            if ($secondsLate <= 0) {
                continue;
            }

            $daysLate = max(1, (int) floor($secondsLate / 86400));
            $chargeableDays = max(0, $daysLate - $graceDays);
            if ($chargeableDays === 0) {
                continue;
            }

            $targetAmount = number_format($chargeableDays * $dailyRate, 2, '.', '');
            $existing = $this->fines->findActiveOverdueFine($loan);

            if ($dryRun) {
                $label = $existing ? '[UPDATE]' : '[CREATE]';
                $output->writeln(sprintf(
                    '%s Loan #%d for user #%d -> %s %s (%d days late)',
                    $label,
                    $loan->getId(),
                    $loan->getUser()->getId(),
                    $targetAmount,
                    $currency,
                    $chargeableDays
                ));
                if ($existing) {
                    $updated++;
                } else {
                    $created++;
                }
                continue;
            }

            if ($existing) {
                if ($existing->getAmount() !== $targetAmount) {
                    $existing->setAmount($targetAmount);
                    $existing->setCurrency($currency);
                    $existing->setReason(sprintf('Przetrzymanie książki (%d dni)', $chargeableDays));
                    $this->entityManager->persist($existing);
                    $updated++;
                }
                continue;
            }

            $fine = (new Fine())
                ->setLoan($loan)
                ->setAmount($targetAmount)
                ->setCurrency($currency)
                ->setReason(sprintf('Przetrzymanie książki (%d dni)', $chargeableDays));

            $this->entityManager->persist($fine);
            $created++;
        }

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        $output->writeln(sprintf(
            '<info>Processed %d loan(s): %d fine(s) created, %d updated.</info>',
            count($loans),
            $created,
            $updated
        ));

        return Command::SUCCESS;
    }
}
