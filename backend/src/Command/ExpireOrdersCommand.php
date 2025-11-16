<?php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:orders:expire', description: 'Legacy placeholder kept after removing reader orders.')]
final class ExpireOrdersCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<comment>Orders feature has been removed; nothing to expire.</comment>');
        return Command::SUCCESS;
    }
}
