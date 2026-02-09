<?php
declare(strict_types=1);
namespace App\Command;

use App\Service\Maintenance\PatronAnonymizer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'maintenance:anonymize-patrons', description: 'Anonimizuje dane czytelników nieaktywnych od wskazanego czasu')]
class AnonymizeInactivePatronsCommand extends Command
{
    public function __construct(private PatronAnonymizer $anonymizer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('inactive-days', null, InputOption::VALUE_REQUIRED, 'Minimalna liczba dni braku aktywności', 730)
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Maksymalna liczba kont w jednym przebiegu', 200)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Symulacja bez zapisywania zmian');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = max(1, (int) $input->getOption('inactive-days'));
        $limit = max(1, (int) $input->getOption('limit'));
        $dryRun = (bool) $input->getOption('dry-run');

        $cutoff = (new \DateTimeImmutable())->modify(sprintf('-%d days', $days));
        $stats = $this->anonymizer->anonymize($cutoff, $limit, $dryRun);

        $io->table(
            ['Kandydaci', 'Zanonimizowani', 'Aktywni / z zaległościami', 'Zablokowani', 'Już zanonimizowani', 'Tryb'],
            [[
                $stats['candidates'],
                $stats['anonymized'],
                $stats['skippedActive'],
                $stats['skippedBlocked'],
                $stats['skippedAnonymized'],
                $dryRun ? 'symulacja' : 'zapis',
            ]]
        );

        if ($stats['userIds'] !== []) {
            $io->writeln('Id użytkowników objętych operacją: ' . implode(', ', $stats['userIds']));
        }

        if ($dryRun) {
            $io->warning('Włączony tryb dry-run. Żadne dane nie zostały zmienione.');
        } else {
            $io->success('Proces anonimizacji zakończony.');
        }

        return Command::SUCCESS;
    }
}
