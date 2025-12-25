<?php
namespace App\Application\Command\Announcement;

class CreateAnnouncementCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly string $title,
        public readonly string $content,
        public readonly ?string $location,
        public readonly ?string $type,
        public readonly ?bool $isPinned,
        public readonly ?bool $showOnHomepage,
        /** @var string[]|null */
        public readonly ?array $targetAudience,
        public readonly ?string $expiresAt,
        public readonly ?string $eventAt
    ) {
    }
}
