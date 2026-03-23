<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Reservation;
use App\Entity\User;
use App\Repository\NotificationLogRepository;
use App\Repository\UserRepository;
use App\Service\Notification\NotificationSender;
use App\Service\User\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class NotificationServiceTest extends TestCase
{
    public function testReservationPreparedCreatesInAppNotificationAndFlushes(): void
    {
        $sender = $this->createMock(NotificationSender::class);
        $logger = $this->createMock(LoggerInterface::class);
        $userRepository = $this->createMock(UserRepository::class);
        $notificationLogs = $this->createMock(NotificationLogRepository::class);
        $notificationLogs->expects($this->once())->method('existsForFingerprint')->willReturn(false);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $user = new User();
        $user->setEmail('reader@example.com')->setName('Reader');
        $author = (new Author())->setName('Author');
        $book = (new Book())->setTitle('Reservation Book')->setAuthor($author);
        $reservation = (new Reservation())
            ->setUser($user)
            ->setBook($book)
            ->setExpiresAt(new \DateTimeImmutable('+2 days'));

        $service = new NotificationService($sender, $logger, $userRepository, $notificationLogs, $entityManager);
        $service->notifyReservationPrepared($reservation);
    }
}
