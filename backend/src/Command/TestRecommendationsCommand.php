<?php
declare(strict_types=1);
namespace App\Command;

use App\Repository\BookRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-recommendations',
    description: 'Run a quick semantic recommendation check for a given book ID.'
)]
class TestRecommendationsCommand extends Command
{
    public function __construct(private BookRepository $books)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('bookId', InputArgument::REQUIRED, 'Book ID to test recommendations for');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $bookId = (int) $input->getArgument('bookId');

        if ($bookId <= 0) {
            $io->error('Book ID must be a positive integer.');
            return Command::FAILURE;
        }

        $book = $this->books->find($bookId);
        if (!$book) {
            $io->error('Book not found.');
            return Command::FAILURE;
        }

        $io->title('Semantic Recommendations Test');
        $io->text(sprintf('Book: %s (ID: %d)', $book->getTitle(), $bookId));

        $results = $this->books->findRelatedBooksWithDistance($book, 5);
        if ($results === []) {
            $io->warning('No recommendations found (missing embedding or no similar books).');
            return Command::SUCCESS;
        }

        $rows = array_map(static function (array $item): array {
            $related = $item['book'];
            return [
                $related->getId(),
                $related->getTitle(),
                number_format($item['distance'], 4),
            ];
        }, $results);

        $io->table(['ID', 'Title', 'Distance'], $rows);

        return Command::SUCCESS;
    }
}
