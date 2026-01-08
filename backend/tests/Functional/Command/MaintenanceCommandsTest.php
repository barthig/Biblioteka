<?php
namespace App\Tests\Functional\Command;

use App\Entity\BackupRecord;
use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\User;
use App\Repository\BackupRecordRepository;
use App\Repository\BookRepository;
use App\Repository\UserRepository;
use App\Tests\Functional\ApiTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class MaintenanceCommandsTest extends ApiTestCase
{
    private function runCommand(string $name, array $input = []): CommandTester
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $command = $application->find($name);

        $tester = new CommandTester($command);
        $tester->execute($input, ['interactive' => false]);

        return $tester;
    }

    public function testImportIsbnCommandCreatesBooks(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'isbn');
        self::assertNotFalse($tempFile);

        $csv = implode("\n", [
            'isbn,title,author,publisher,year,description',
            '9788324234234,Nowa książka,Autor Testowy,Neo Press,2024,Opis testowy',
        ]);
        file_put_contents($tempFile, $csv);

        try {
            $this->runCommand('maintenance:import-isbn', [
                '--source' => $tempFile,
                '--format' => 'csv',
                '--default-category' => 'Nowości',
            ]);
        } finally {
            @unlink($tempFile);
        }

        /** @var BookRepository $books */
        $books = $this->entityManager->getRepository(Book::class);
        $book = $books->findOneBy(['isbn' => '9788324234234']);
        self::assertNotNull($book);
        self::assertSame('Nowa książka', $book->getTitle());
        self::assertSame('Neo Press', $book->getPublisher());
        self::assertSame(2024, $book->getPublicationYear());
    }

    public function testAnonymizeInactivePatronsCommandScrubsSensitiveFields(): void
    {
        $user = $this->createUser('legacy@example.com');
        $this->travelUserBackInTime($user, new \DateTimeImmutable('-900 days'));

        $this->runCommand('maintenance:anonymize-patrons', [
            '--inactive-days' => 365,
            '--limit' => 10,
        ]);

        $this->entityManager->clear();

        /** @var UserRepository $users */
        $users = $this->entityManager->getRepository(User::class);
        $fresh = $users->find($user->getId());
        self::assertNotNull($fresh);
        self::assertStringContainsString('@example.invalid', $fresh->getEmail());
        self::assertSame('Anonimowy Czytelnik', $fresh->getName());
        self::assertNull($fresh->getPhoneNumber());
        self::assertFalse($fresh->isBlocked());
    }

    public function testWeedingAnalyticsCommandHighlightsStaleTitle(): void
    {
        $staleUser = $this->createUser('stale@example.com');
        $activeUser = $this->createUser('active@example.com');

        $staleBook = $this->createBook('Stara pozycja');
        $recentBook = $this->createBook('Nowa pozycja');

        $staleLoan = $this->createLoan($staleUser, $staleBook);
        $recentLoan = $this->createLoan($activeUser, $recentBook);

        $this->shiftLoanBorrowedAt($staleLoan, new \DateTimeImmutable('-800 days'));
        $this->shiftLoanBorrowedAt($recentLoan, new \DateTimeImmutable('-10 days'));

        $tester = $this->runCommand('maintenance:weeding-analyze', [
            '--cutoff-months' => 12,
            '--min-loans' => 0,
            '--format' => 'json',
        ]);

        $payload = json_decode(trim($tester->getDisplay()), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertArrayHasKey('items', $payload);

        $titles = array_map(static fn (array $row) => $row['title'], $payload['items']);
        self::assertContains('Stara pozycja', $titles);
        self::assertNotContains('Nowa pozycja', $titles);
    }

    public function testCreateBackupCommandPersistsRecord(): void
    {
        /** @var BackupRecordRepository $repo */
        $repo = $this->entityManager->getRepository(BackupRecord::class);
        self::assertCount(0, $repo->findAll());

        $this->runCommand('maintenance:create-backup', [
            '--initiator' => 'phpunit',
            '--note' => 'Test snapshot',
        ]);

        $this->entityManager->clear();

        $records = $repo->findAll();
        self::assertCount(1, $records);
        self::assertSame('phpunit', $records[0]->getInitiatedBy());
        self::assertContains($records[0]->getStatus(), ['completed', 'failed']);
    }

    private function shiftLoanBorrowedAt(Loan $loan, \DateTimeImmutable $moment): void
    {
        $property = new \ReflectionProperty(Loan::class, 'borrowedAt');
        $property->setAccessible(true);
        $property->setValue($loan, $moment);
        $this->entityManager->persist($loan);
        $this->entityManager->flush();
    }

    private function travelUserBackInTime(User $user, \DateTimeImmutable $moment): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement(
            'UPDATE app_user SET created_at = :moment, updated_at = :moment WHERE id = :id',
            [
                'moment' => $moment->format('Y-m-d H:i:s'),
                'id' => $user->getId(),
            ]
        );
        $this->entityManager->clear();
    }
}
