<?php
namespace App\Tests\Service;

use App\Entity\User;
use App\Service\Notification\NotificationContent;
use App\Service\Notification\NotificationSender;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationSenderTest extends TestCase
{
    public function testSendEmailSkippedWhenMissingEmail(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $sender = new NotificationSender($mailer, $logger, 'from@example.com');

        $user = (new User())->setEmail('');
        $content = new NotificationContent('Subject', 'Text', null, ['email']);

        $result = $sender->sendEmail($user, $content);
        $this->assertSame('skipped', $result['status']);
    }

    public function testSendEmailReturnsFailedOnException(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->method('send')->willThrowException(new \RuntimeException('fail'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');
        $sender = new NotificationSender($mailer, $logger, 'from@example.com');

        $user = (new User())->setEmail('user@example.com');
        $content = new NotificationContent('Subject', 'Text', null, ['email']);

        $result = $sender->sendEmail($user, $content);
        $this->assertSame('failed', $result['status']);
    }

    public function testSendSmsSkippedWhenMissingPhone(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $sender = new NotificationSender($mailer, $logger, 'from@example.com');

        $user = (new User())->setEmail('user@example.com');
        $content = new NotificationContent('Subject', 'Text', null, ['sms']);

        $result = $sender->sendSms($user, $content);
        $this->assertSame('skipped', $result['status']);
    }

    public function testSendEmailUsesMailer(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('send')->with($this->isInstanceOf(Email::class));
        $logger = $this->createMock(LoggerInterface::class);
        $sender = new NotificationSender($mailer, $logger, 'from@example.com');

        $user = (new User())->setEmail('user@example.com');
        $content = new NotificationContent('Subject', 'Text', '<p>Html</p>', ['email']);

        $result = $sender->sendEmail($user, $content);
        $this->assertSame('sent', $result['status']);
    }
}
