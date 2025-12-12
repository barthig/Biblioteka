<?php
namespace App\Application\Command\Announcement;

class ArchiveAnnouncementCommand
{
    public function __construct(public readonly int $id)
    {
    }
}
