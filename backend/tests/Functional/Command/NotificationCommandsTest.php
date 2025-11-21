<?php
namespace App\Tests\Functional\Command;

use App\Entity\BookCopy;
use App\Entity\Reservation;
use App\Repository\NotificationLogRepository;
use App\Tests\Functional\ApiTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class NotificationCommandsTest extends ApiTestCase
{
    private function executeCommand(string $name, array $input = []): CommandTester
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $command = $application->find($name);

        $tester = new CommandTester($command);
        $tester->execute($input, ['interactive' => false]);

        return $tester;
    }

    public function testLoanDueRemindersCommandDispatchesMessages(): void
    {
        $user = $this->createUser('due@example.com');
        $book = $this->createBook('Due soon book');
        $this->createLoan($user, $book, new \DateTimeImmutable('+2 days'));

        $tester = $this->executeCommand('notifications:dispatch-due-reminders', ['--days' => 3]);

        $this->assertStringContainsString('reminder', $tester->getDisplay());

        $logs = self::getContainer()->get(NotificationLogRepository::class)->findAll();
        $this->assertCount(1, $logs);
        $this->assertSame('loan_due', $logs[0]->getType());
    }

    public function testReservationReadyCommandDispatchesMessages(): void
    {
        $user = $this->createUser('reservation@example.com');
        $book = $this->createBook('Reservation ready book');
        $copy = $book->getInventory()->first();
        if (!$copy) {
            $copy = $book->getInventory()->toArray()[0];
        }
        $copy->setStatus(BookCopy::STATUS_RESERVED);

        $reservation = (new Reservation())
            ->setBook($book)
            ->setUser($user)
            ->assignBookCopy($copy)
            ->setExpiresAt(new \DateTimeImmutable('+2 days'));

        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        $tester = $this->executeCommand('notifications:dispatch-reservation-ready');

        $this->assertStringContainsString('reservation notification', $tester->getDisplay());

        $logs = self::getContainer()->get(NotificationLogRepository::class)->findAll();
        $this->assertCount(1, $logs);
        $this->assertSame('reservation_ready', $logs[0]->getType());
    }

    public function testOverdueCommandDryRunDoesNotDispatch(): void
    {
        $user = $this->createUser('overdue@example.com');
        $book = $this->createBook('Overdue book');
        $this->createLoan($user, $book, new \DateTimeImmutable('-3 days'));

        $tester = $this->executeCommand('notifications:dispatch-overdue-warnings', [
            '--threshold' => 1,
            '--dry-run' => true,
        ]);

        $this->assertStringContainsString('[DRY]', $tester->getDisplay());

        $logs = self::getContainer()->get(NotificationLogRepository::class)->findAll();
        $this->assertCount(0, $logs);
    }
}
