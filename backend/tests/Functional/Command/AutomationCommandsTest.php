<?php
namespace App\Tests\Functional\Command;

use App\Entity\BookCopy;
use App\Entity\Fine;
use App\Entity\Reservation;
use App\Entity\User;
use App\Repository\FineRepository;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use App\Tests\Functional\ApiTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class AutomationCommandsTest extends ApiTestCase
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

    public function testAssessOverdueFinesCommandCreatesFine(): void
    {
        $user = $this->createUser('overdue@example.com');
        $book = $this->createBook('Overdue title');
        $loan = $this->createLoan($user, $book, new \DateTimeImmutable('-2 days'));

        $this->runCommand('fines:assess-overdue', [
            '--daily-rate' => '2.00',
            '--currency' => 'PLN',
            '--grace-days' => 0,
        ]);

        /** @var FineRepository $repo */
        $repo = $this->entityManager->getRepository(Fine::class);
        $fine = $repo->findActiveOverdueFine($loan);
        self::assertNotNull($fine);
        self::assertSame('4.00', $fine->getAmount());
        self::assertSame('PLN', $fine->getCurrency());
    }

    public function testExpireReadyReservationsCommandMovesCopyToNextReader(): void
    {
        $book = $this->createBook('Queue book', null, 1, null, 1);
        $copy = $book->getInventory()->first();
        self::assertInstanceOf(BookCopy::class, $copy);

        $firstUser = $this->createUser('first@example.com');
        $secondUser = $this->createUser('second@example.com');

        $firstReservation = (new Reservation())
            ->setBook($book)
            ->setUser($firstUser)
            ->assignBookCopy($copy)
            ->setExpiresAt(new \DateTimeImmutable('-1 day'));
        $copy->setStatus(BookCopy::STATUS_RESERVED);

        $secondReservation = (new Reservation())
            ->setBook($book)
            ->setUser($secondUser)
            ->setExpiresAt(new \DateTimeImmutable('+2 days'));

        $this->entityManager->persist($firstReservation);
        $this->entityManager->persist($secondReservation);
        $this->entityManager->persist($copy);
        $this->entityManager->persist($book);
        $this->entityManager->flush();

        $this->runCommand('reservations:expire-ready', ['--pickup-hours' => 24]);

        $this->entityManager->clear();
        $reservationRepository = $this->entityManager->getRepository(Reservation::class);
        $copyRepository = $this->entityManager->getRepository(BookCopy::class);

        $firstFresh = $reservationRepository->find($firstReservation->getId());
        $secondFresh = $reservationRepository->find($secondReservation->getId());
        $copyFresh = $copyRepository->find($copy->getId());

        self::assertSame(Reservation::STATUS_EXPIRED, $firstFresh->getStatus());
        self::assertNull($firstFresh->getBookCopy());

        self::assertSame($copyFresh->getId(), $secondFresh->getBookCopy()?->getId());
        self::assertTrue($secondFresh->getExpiresAt() > new \DateTimeImmutable());
        self::assertSame(BookCopy::STATUS_RESERVED, $copyFresh->getStatus());
    }

    public function testBlockDelinquentAccountsCommandBlocksUsers(): void
    {
        $user = $this->createUser('debtor@example.com');
        $book = $this->createBook('Blocking book');
        $loan = $this->createLoan($user, $book, new \DateTimeImmutable('-40 days'));
        $this->createFineForLoan($loan, '60.00', false, 'Przetrzymanie');

        $this->runCommand('users:block-delinquent', [
            '--fine-limit' => '50',
            '--overdue-days' => '30',
        ]);

        /** @var UserRepository $repo */
        $repo = $this->entityManager->getRepository(User::class);
        $fresh = $repo->find($user->getId());
        self::assertTrue($fresh->isBlocked());
        self::assertNotNull($fresh->getBlockedReason());
    }
}
