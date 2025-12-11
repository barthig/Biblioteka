<?php

namespace App\Command;

use App\Service\RefreshTokenService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cleanup:refresh-tokens',
    description: 'Remove expired refresh tokens from database'
)]
class CleanupRefreshTokensCommand extends Command
{
    public function __construct(
        private RefreshTokenService $refreshTokenService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Cleaning up expired refresh tokens...');

        $deletedCount = $this->refreshTokenService->cleanupExpiredTokens();

        $io->success(sprintf('Successfully deleted %d expired refresh tokens.', $deletedCount));

        return Command::SUCCESS;
    }
}
