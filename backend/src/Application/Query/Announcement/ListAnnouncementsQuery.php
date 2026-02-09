<?php
declare(strict_types=1);
namespace App\Application\Query\Announcement;

use App\Entity\User;

class ListAnnouncementsQuery
{
    public function __construct(
        public readonly ?User $user,
        public readonly bool $isLibrarian,
        public readonly ?string $status,
        public readonly bool $homepageOnly,
        public readonly int $page,
        public readonly int $limit
    ) {
    }
}
