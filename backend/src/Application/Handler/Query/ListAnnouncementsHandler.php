<?php
namespace App\Application\Handler\Query;

use App\Application\Query\Announcement\ListAnnouncementsQuery;
use App\Repository\AnnouncementRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ListAnnouncementsHandler
{
    public function __construct(private readonly AnnouncementRepository $repository)
    {
    }

    public function __invoke(ListAnnouncementsQuery $query): array
    {
        $offset = ($query->page - 1) * $query->limit;

        if ($query->isLibrarian) {
            if ($query->status) {
                $announcements = $this->repository->findByStatus($query->status);
            } else {
                $announcements = $this->repository->findAllWithCreator();
            }
            
            $total = count($announcements);
            $announcements = array_slice($announcements, $offset, $query->limit);

            return [
                'data' => $announcements,
                'meta' => [
                    'page' => $query->page,
                    'limit' => $query->limit,
                    'total' => $total,
                    'totalPages' => $total > 0 ? (int)ceil($total / $query->limit) : 0
                ]
            ];
        }

        if ($query->homepageOnly) {
            $announcements = $this->repository->findForHomepage($query->user, $query->limit);
            $total = count($announcements);
        } else {
            $announcements = $this->repository->findActiveForUser($query->user);
            $total = count($announcements);
            $announcements = array_slice($announcements, $offset, $query->limit);
        }

        return [
            'data' => array_values($announcements),
            'meta' => [
                'page' => $query->page,
                'limit' => $query->limit,
                'total' => $total,
                'totalPages' => $total > 0 ? (int)ceil($total / $query->limit) : 0
            ]
        ];
    }
}
