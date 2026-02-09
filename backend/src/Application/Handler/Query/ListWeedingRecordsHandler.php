<?php
declare(strict_types=1);
namespace App\Application\Handler\Query;

use App\Application\Query\Weeding\ListWeedingRecordsQuery;
use App\Repository\WeedingRecordRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
class ListWeedingRecordsHandler
{
    public function __construct(private readonly WeedingRecordRepository $repository)
    {
    }

    public function __invoke(ListWeedingRecordsQuery $query): array
    {
        $limit = max(1, min(500, $query->limit));
        return $this->repository->findRecent($limit);
    }
}
