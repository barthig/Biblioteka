<?php
namespace App\Command;

use App\Service\BackupService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'maintenance:create-backup', description: 'Wykonuje kopię zapasową wpisaną w tabeli backup_record')]
class CreateBackupCommand extends Command
{
    public function __construct(private BackupService $backupService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('initiator', 'i', InputOption::VALUE_OPTIONAL, 'Informacja kto uruchamia kopię')
            ->addOption('note', 't', InputOption::VALUE_OPTIONAL, 'Dodatkowa adnotacja zapisywana w pliku JSON');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $initiator = $input->getOption('initiator');
        $note = $input->getOption('note');

        $record = $this->backupService->createBackup(
            $initiator !== null ? (string) $initiator : null,
            $note !== null ? (string) $note : null
        );

        $io->success(sprintf('Utworzono kopię "%s" (%d B)', $record->getFileName(), $record->getFileSize()));
        $io->writeln('Lokalizacja: ' . $record->getFilePath());

        return Command::SUCCESS;
    }
}
