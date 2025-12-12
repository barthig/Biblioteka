<?php
namespace App\Application\Query\Announcement;

use App\Entity\User;

class GetAnnouncementQuery
{
    public function __construct(
        public readonly int $id,
        public readonly ?User $user,
        public readonly bool $isLibrarian
    ) {
    }
}
