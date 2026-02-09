<?php
declare(strict_types=1);
namespace App\Service\Notification;

class NotificationContent
{
    /** @param string[] $channels */
    public function __construct(
        private string $subject,
        private string $textBody,
        private ?string $htmlBody,
        private array $channels
    ) {
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getTextBody(): string
    {
        return $this->textBody;
    }

    public function getHtmlBody(): ?string
    {
        return $this->htmlBody;
    }

    /**
     * @return string[]
     */
    public function getChannels(): array
    {
        return $this->channels;
    }
}
