<?php
namespace App\Application\Command\Announcement;

class DeleteAnnouncementCommand
{
    public function __construct(public readonly int $id)
    {
    }
}
