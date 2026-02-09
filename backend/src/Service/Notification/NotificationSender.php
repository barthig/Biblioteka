<?php
declare(strict_types=1);
namespace App\Service\Notification;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationSender
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $fromAddress
    ) {
    }

    /**
     * @return array{status: string, error?: string}
     */
    public function sendEmail(User $user, NotificationContent $content): array
    {
        if (!$user->getEmail()) {
            return ['status' => 'skipped', 'error' => 'missing_email'];
        }

        $email = (new Email())
            ->from($this->fromAddress)
            ->to($user->getEmail())
            ->subject($content->getSubject())
            ->text($content->getTextBody());

        if ($content->getHtmlBody()) {
            $email->html($content->getHtmlBody());
        }

        try {
            $this->mailer->send($email);
            return ['status' => 'sent'];
        } catch (\Throwable $exception) {
            $this->logger->error('Email notification failed', [
                'userId' => $user->getId(),
                'error' => $exception->getMessage(),
            ]);

            return ['status' => 'failed', 'error' => $exception->getMessage()];
        }
    }

    /**
     * @return array{status: string, error?: string}
     */
    public function sendSms(User $user, NotificationContent $content): array
    {
        $phone = $user->getPhoneNumber();
        if (!$phone) {
            return ['status' => 'skipped', 'error' => 'missing_phone'];
        }

        // Placeholder implementation – integrate with real SMS provider later.
        $this->logger->info('SMS notification dispatched (simulated)', [
            'userId' => $user->getId(),
            'phone' => $phone,
            'body' => mb_substr($content->getTextBody(), 0, 280),
        ]);

        return ['status' => 'sent'];
    }
}
