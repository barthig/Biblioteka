<?php
declare(strict_types=1);
namespace App\Application\Handler\Query;

use App\Application\Query\Announcement\GetAnnouncementQuery;
use App\Entity\Announcement;
use App\Exception\NotFoundException;
use App\Repository\AnnouncementRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
class GetAnnouncementHandler
{
    public function __construct(private readonly AnnouncementRepository $repository)
    {
    }

    public function __invoke(GetAnnouncementQuery $query): Announcement
    {
        $announcement = $this->repository->find($query->id);
        
        if (!$announcement) {
            throw NotFoundException::forEntity('Announcement', $query->id);
        }

        if (!$query->isLibrarian && !$announcement->isVisibleForUser($query->user)) {
            throw NotFoundException::forEntity('Announcement', $query->id);
        }

        return $announcement;
    }
}
