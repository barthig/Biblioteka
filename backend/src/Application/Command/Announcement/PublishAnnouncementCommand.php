<?php
namespace App\Application\Command\Announcement;

class PublishAnnouncementCommand
{
    public function __construct(public readonly int $id)
    {
    }
}
