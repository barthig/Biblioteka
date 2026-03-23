<?php
declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\TestNotificationDispatchMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
final class TestNotificationDispatchHandler
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly string $fromAddress
    ) {
    }

    public function __invoke(TestNotificationDispatchMessage $message): void
    {
        if ($message->getChannel() === 'sms') {
            $this->logger->info('Test SMS notification dispatched (simulated)', [
                'target' => $message->getTarget(),
                'requestedByUserId' => $message->getRequestedByUserId(),
            ]);
            return;
        }

        $email = (new Email())
            ->from($this->fromAddress)
            ->to($message->getTarget())
            ->subject('Biblioteka test notification')
            ->text($message->getMessage());

        try {
            $this->mailer->send($email);
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to send async test notification', [
                'target' => $message->getTarget(),
                'requestedByUserId' => $message->getRequestedByUserId(),
                'error' => $exception->getMessage(),
            ]);
            throw $exception;
        }
    }
}
