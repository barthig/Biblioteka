<?php
namespace App\Tests\Service;

use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\User;
use App\Repository\NotificationLogRepository;
use App\Repository\UserRepository;
use App\Service\Notification\NotificationSender;
use App\Service\User\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class NotificationServiceTest extends TestCase
{
    private function createService(
        NotificationSender $sender,
        LoggerInterface $logger
    ): NotificationService {
        $userRepository = $this->createMock(UserRepository::class);
        $notificationLogs = $this->createMock(NotificationLogRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        return new NotificationService($sender, $logger, $userRepository, $notificationLogs, $entityManager);
    }

    public function testSendsEmailOnlyWhenPhoneMissing(): void
    {
        $sender = $this->createMock(NotificationSender::class);
        $logger = $this->createMock(LoggerInterface::class);

        $user = new User();
        $user->setName('Reader')->setEmail('reader@example.com');
        $book = (new Book())->setTitle('Book')->setAuthor(new \App\Entity\Author());

        $loan = $this->createMock(Loan::class);
        $loan->method('getUser')->willReturn($user);
        $loan->method('getBook')->willReturn($book);
        $loan->method('getDueAt')->willReturn(new \DateTimeImmutable('+1 day'));
        $loan->method('getId')->willReturn(1);

        $sender->expects($this->once())->method('sendEmail')->willReturn(['status' => 'sent']);
        $sender->expects($this->never())->method('sendSms');

        $service = $this->createService($sender, $logger);
        $service->notifyLoanCreated($loan);
    }

    public function testSendsEmailAndSmsWhenPhoneAvailable(): void
    {
        $sender = $this->createMock(NotificationSender::class);
        $logger = $this->createMock(LoggerInterface::class);

        $user = new User();
        $user->setName('Reader')->setEmail('reader@example.com')->setPhoneNumber('123');
        $book = (new Book())->setTitle('Book')->setAuthor(new \App\Entity\Author());

        $loan = $this->createMock(Loan::class);
        $loan->method('getUser')->willReturn($user);
        $loan->method('getBook')->willReturn($book);
        $loan->method('getDueAt')->willReturn(new \DateTimeImmutable('+1 day'));
        $loan->method('getId')->willReturn(1);

        $sender->expects($this->once())->method('sendEmail')->willReturn(['status' => 'sent']);
        $sender->expects($this->once())->method('sendSms')->willReturn(['status' => 'sent']);

        $service = $this->createService($sender, $logger);
        $service->notifyLoanCreated($loan);
    }
}
