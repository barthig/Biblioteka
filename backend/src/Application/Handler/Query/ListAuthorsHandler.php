<?php
declare(strict_types=1);
namespace App\Application\Handler\Query;

use App\Application\Query\Author\ListAuthorsQuery;
use App\Repository\AuthorRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
class ListAuthorsHandler
{
    public function __construct(
        private readonly AuthorRepository $authorRepository
    ) {
    }

    public function __invoke(ListAuthorsQuery $query): array
    {
        $offset = ($query->page - 1) * $query->limit;

        if ($query->search) {
            // Simple LIKE search
            $qb = $this->authorRepository->createQueryBuilder('a')
                ->where('a.name LIKE :search')
                ->setParameter('search', '%' . $query->search . '%')
                ->orderBy('a.name', 'ASC')
                ->setMaxResults($query->limit)
                ->setFirstResult($offset);
            
            return $qb->getQuery()->getResult();
        }

        return $this->authorRepository->findBy([], ['name' => 'ASC'], $query->limit, $offset);
    }
}
