<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\User;
use App\Message\LoanBorrowedMessage;
use App\Message\LoanReturnedMessage;
use App\MessageHandler\NotificationMessageHandler;
use App\Repository\LoanRepository;
use App\Repository\NotificationLogRepository;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use App\Service\Notification\NotificationContent;
use App\Service\Notification\NotificationContentBuilder;
use App\Service\Notification\NotificationSender;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class NotificationMessageHandlerTest extends TestCase
{
    public function testBorrowedMessageDeliversNotificationAndLogsFingerprint(): void
    {
        $user = $this->getMockBuilder(User::class)->onlyMethods(['getId'])->getMock();
        $user->method('getId')->willReturn(3);
        $user->setName('Reader');
        $user->setEmail('reader@example.com');
        $user->setPhoneNumber('123456789');

        $author = (new Author())->setName('Author');
        $book = (new Book())->setTitle('Messenger Patterns')->setAuthor($author);
        $loan = (new Loan())
            ->setUser($user)
            ->setBook($book)
            ->setDueAt(new \DateTimeImmutable('2026-04-01 10:00:00'));

        $users = $this->createMock(UserRepository::class);
        $users->method('find')->with(3)->willReturn($user);

        $loans = $this->createMock(LoanRepository::class);
        $loans->method('find')->with(17)->willReturn($loan);

        $reservations = $this->createMock(ReservationRepository::class);
        $logs = $this->createMock(NotificationLogRepository::class);
        $logs->expects($this->exactly(2))->method('existsForFingerprint')->willReturn(false);

        $builder = new NotificationContentBuilder();
        $sender = $this->createMock(NotificationSender::class);
        $sender->expects($this->once())->method('sendEmail')->with($user, $this->isInstanceOf(NotificationContent::class))->willReturn(['status' => 'sent']);
        $sender->expects($this->once())->method('sendSms')->with($user, $this->isInstanceOf(NotificationContent::class))->willReturn(['status' => 'sent']);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly(2))->method('persist');
        $em->expects($this->once())->method('flush');

        $logger = $this->createMock(LoggerInterface::class);

        $handler = new NotificationMessageHandler($users, $loans, $reservations, $logs, $builder, $sender, $em, $logger);
        $handler(new LoanBorrowedMessage(17, 3, '2026-04-01T10:00:00+00:00'));
    }

    public function testReturnedMessageIsSkippedWhenFingerprintAlreadyExists(): void
    {
        $user = $this->getMockBuilder(User::class)->onlyMethods(['getId'])->getMock();
        $user->method('getId')->willReturn(6);
        $user->setName('Reader');
        $user->setEmail('reader@example.com');

        $author = (new Author())->setName('Author');
        $book = (new Book())->setTitle('CQRS in Practice')->setAuthor($author);
        $loan = (new Loan())
            ->setUser($user)
            ->setBook($book)
            ->setDueAt(new \DateTimeImmutable('2026-03-20 10:00:00'))
            ->setReturnedAt(new \DateTimeImmutable('2026-03-21 10:00:00'));

        $users = $this->createMock(UserRepository::class);
        $users->method('find')->with(6)->willReturn($user);

        $loans = $this->createMock(LoanRepository::class);
        $loans->method('find')->with(31)->willReturn($loan);

        $reservations = $this->createMock(ReservationRepository::class);
        $logs = $this->createMock(NotificationLogRepository::class);
        $logs->expects($this->once())->method('existsForFingerprint')->willReturn(true);

        $builder = new NotificationContentBuilder();
        $sender = $this->createMock(NotificationSender::class);
        $sender->expects($this->never())->method('sendEmail');
        $sender->expects($this->never())->method('sendSms');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info');

        $handler = new NotificationMessageHandler($users, $loans, $reservations, $logs, $builder, $sender, $em, $logger);
        $handler(new LoanReturnedMessage(31, 6, '2026-03-21T10:00:00+00:00', true));
    }
}
