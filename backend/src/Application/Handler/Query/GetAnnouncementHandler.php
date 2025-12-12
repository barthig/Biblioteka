<?php
namespace App\Application\Handler\Query;

use App\Application\Query\Announcement\GetAnnouncementQuery;
use App\Entity\Announcement;
use App\Repository\AnnouncementRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetAnnouncementHandler
{
    public function __construct(private readonly AnnouncementRepository $repository)
    {
    }

    public function __invoke(GetAnnouncementQuery $query): Announcement
    {
        $announcement = $this->repository->find($query->id);
        
        if (!$announcement) {
            throw new \RuntimeException('Announcement not found');
        }

        if (!$query->isLibrarian && !$announcement->isVisibleForUser($query->user)) {
            throw new \RuntimeException('Announcement not found');
        }

        return $announcement;
    }
}
