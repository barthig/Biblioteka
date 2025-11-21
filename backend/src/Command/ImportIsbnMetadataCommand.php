<?php
namespace App\Command;

use App\Service\Maintenance\IsbnImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'maintenance:import-isbn', description: 'Importuje metadane książek na podstawie listy ISBN')]
class ImportIsbnMetadataCommand extends Command
{
    public function __construct(private IsbnImportService $importer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('source', 's', InputOption::VALUE_REQUIRED, 'Ścieżka do pliku CSV/JSON z wpisami ISBN', 'var/import/isbn.csv')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'Format wejściowy: csv albo json', 'csv')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Maksymalna liczba przetwarzanych rekordów')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Symulacja bez zapisywania zmian')
            ->addOption('default-author', null, InputOption::VALUE_OPTIONAL, 'Domyślny autor, gdy brak w danych', 'Autor nieznany')
            ->addOption('default-category', null, InputOption::VALUE_OPTIONAL, 'Domyślna kategoria, gdy brak w danych', 'Zbiory ogólne');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $source = (string) $input->getOption('source');
        $format = strtolower((string) $input->getOption('format'));
        $limitOption = $input->getOption('limit');
        $limit = $limitOption !== null ? max(1, (int) $limitOption) : null;

        if (!in_array($format, ['csv', 'json'], true)) {
            $io->error('Obsługiwane formaty to csv oraz json.');
            return Command::INVALID;
        }

        if (!is_file($source)) {
            $io->error(sprintf('Plik źródłowy "%s" nie istnieje.', $source));
            return Command::INVALID;
        }

        $records = $format === 'csv'
            ? $this->parseCsv($source, $limit)
            : $this->parseJson($source, $limit);

        if ($records === []) {
            $io->warning('Nie znaleziono żadnych rekordów do importu.');
            return Command::SUCCESS;
        }

        $dryRun = (bool) $input->getOption('dry-run');
        $stats = $this->importer->import(
            $records,
            $dryRun,
            (string) $input->getOption('default-author'),
            (string) $input->getOption('default-category')
        );

        $io->section('Podsumowanie importu');
        $io->table(
            ['Przetworzono', 'Nowe pozycje', 'Zaktualizowane', 'Pominięte', 'Tryb'],
            [[
                $stats['processed'],
                $stats['created'],
                $stats['updated'],
                $stats['skipped'],
                $dryRun ? 'symulacja' : 'zapis do bazy',
            ]]
        );

        if ($stats['errors'] !== []) {
            $io->warning(sprintf('Wystąpiły problemy (%d). Pokazuję pierwsze 5 wpisów.', count($stats['errors'])));
            foreach (array_slice($stats['errors'], 0, 5) as $error) {
                $io->writeln('- ' . $error);
            }
        } else {
            $io->success('Import zakończony bez błędów.');
        }

        return Command::SUCCESS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseCsv(string $path, ?int $limit): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        if ($lines === []) {
            return [];
        }

        $delimiter = ',';
        if (substr_count($lines[0], ';') > substr_count($lines[0], ',')) {
            $delimiter = ';';
        }

        $records = [];
        $headers = null;
        foreach ($lines as $line) {
            $row = str_getcsv($line, $delimiter);
            if ($row === null) {
                continue;
            }

            if ($headers === null) {
                $headers = array_map('trim', $row);
                continue;
            }

            $record = [];
            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }
                $record[$header] = $row[$index] ?? null;
            }

            if ($record !== []) {
                $records[] = $record;
            }

            if ($limit !== null && count($records) >= $limit) {
                break;
            }
        }

        return $records;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseJson(string $path, ?int $limit): array
    {
        $contents = file_get_contents($path);
        if ($contents === false) {
            return [];
        }

        try {
            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        if (!is_array($data)) {
            return [];
        }

        if ($limit !== null) {
            $data = array_slice($data, 0, $limit);
        }

        return array_map(static function ($item): array {
            return is_array($item) ? $item : [];
        }, $data);
    }
}
