<?php
declare(strict_types=1);
namespace App\Application\Command\Announcement;

class ArchiveAnnouncementCommand
{
    public function __construct(public readonly int $id)
    {
    }
}
