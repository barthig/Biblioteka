<?php
namespace App\Application\Command\Announcement;

class UpdateAnnouncementCommand
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $title,
        public readonly ?string $content,
        public readonly ?string $location,
        public readonly ?string $type,
        public readonly ?bool $isPinned,
        public readonly ?bool $showOnHomepage,
        /** @var string[]|null */
        public readonly ?array $targetAudience,
        public readonly mixed $expiresAt,
        public readonly mixed $eventAt
    ) {
    }
}
