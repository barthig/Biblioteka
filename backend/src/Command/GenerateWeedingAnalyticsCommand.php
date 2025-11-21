<?php
namespace App\Command;

use App\Service\Maintenance\WeedingAnalyticsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'maintenance:weeding-analyze', description: 'Generuje listę pozycji kwalifikujących się do wycofania')]
class GenerateWeedingAnalyticsCommand extends Command
{
    public function __construct(private WeedingAnalyticsService $analytics)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('cutoff-months', null, InputOption::VALUE_REQUIRED, 'Ile miesięcy braku wypożyczeń kwalifikuje tytuł', 18)
            ->addOption('min-loans', null, InputOption::VALUE_REQUIRED, 'Ile wypożyczeń traktować jako minimum (<= oznacza kandydata)', 0)
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Maksymalna liczba rekordów w raporcie', 25)
            ->addOption('format', 'F', InputOption::VALUE_REQUIRED, 'Format wyjścia: text lub json', 'text');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $months = max(1, (int) $input->getOption('cutoff-months'));
        $minLoans = max(0, (int) $input->getOption('min-loans'));
        $limit = max(1, (int) $input->getOption('limit'));
        $format = strtolower((string) $input->getOption('format'));

        if (!in_array($format, ['text', 'json'], true)) {
            $io->error('Obsługiwane formaty to text oraz json.');
            return Command::INVALID;
        }

        $cutoff = (new \DateTimeImmutable())->modify(sprintf('-%d months', $months));
        $candidates = $this->analytics->summarize($cutoff, $minLoans, $limit);

        if ($format === 'json') {
            $payload = [
                'generatedAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
                'cutoffMonths' => $months,
                'minLoans' => $minLoans,
                'limit' => $limit,
                'items' => $candidates,
            ];
            $output->writeln(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return Command::SUCCESS;
        }

        if ($candidates === []) {
            $io->success('Brak kandydatów do wycofania w zadanych kryteriach.');
            return Command::SUCCESS;
        }

        $rows = array_map(static function (array $row): array {
            return [
                $row['bookId'],
                $row['title'],
                $row['totalLoans'],
                $row['monthsSinceLastLoan'] ?? 'n/d',
                $row['activeReservations'],
                sprintf('%d/%d', $row['availableCopies'], $row['totalCopies']),
                $row['turnover'],
            ];
        }, $candidates);

        $io->table(
            ['ID', 'Tytuł', 'Wypożyczenia', 'Miesięcy bez wypo.', 'Aktywne rezerwacje', 'Dostępne/łącznie', 'Rotacja'],
            $rows
        );
        $io->success(sprintf('Łącznie kandydatów: %d', count($candidates)));

        return Command::SUCCESS;
    }
}
