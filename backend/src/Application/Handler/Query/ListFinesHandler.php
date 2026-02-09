<?php
declare(strict_types=1);
namespace App\Application\Handler\Query;

use App\Application\Query\Fine\ListFinesQuery;
use App\Entity\Loan;
use App\Repository\FineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ListFinesHandler
{
    public function __construct(
        private readonly FineRepository $fineRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(ListFinesQuery $query): array
    {
        $offset = ($query->page - 1) * $query->limit;

        if ($query->isLibrarian) {
            $qb = $this->fineRepository->createQueryBuilder('f')
                ->leftJoin('f.loan', 'l')->addSelect('l')
                ->leftJoin('l.user', 'u')->addSelect('u')
                ->leftJoin('l.book', 'b')->addSelect('b')
                ->orderBy('f.createdAt', 'DESC');

            $countQb = $this->fineRepository->createQueryBuilder('f')
                ->select('COUNT(f.id)');
            $total = (int) $countQb->getQuery()->getSingleScalarResult();

            $fines = $qb->setMaxResults($query->limit)->setFirstResult($offset)->getQuery()->getResult();
            
            return [
                'data' => $fines,
                'meta' => [
                    'page' => $query->page,
                    'limit' => $query->limit,
                    'total' => $total,
                    'totalPages' => $total > 0 ? (int)ceil($total / $query->limit) : 0
                ]
            ];
        }

        if (!$query->userId) {
            return [
                'data' => [],
                'meta' => [
                    'page' => 1,
                    'limit' => $query->limit,
                    'total' => 0,
                    'totalPages' => 0
                ]
            ];
        }

        $loans = $this->entityManager->getRepository(Loan::class)->findBy(['user' => $query->userId]);
        if (empty($loans)) {
            return [
                'data' => [],
                'meta' => [
                    'page' => 1,
                    'limit' => $query->limit,
                    'total' => 0,
                    'totalPages' => 0
                ]
            ];
        }

        $loanIds = array_map(static fn ($loan) => $loan->getId(), $loans);
        $qb = $this->fineRepository->createQueryBuilder('f')
            ->leftJoin('f.loan', 'l')->addSelect('l')
            ->leftJoin('l.user', 'u')->addSelect('u')
            ->leftJoin('l.book', 'b')->addSelect('b')
            ->andWhere('f.loan IN (:loans)')
            ->setParameter('loans', $loanIds)
            ->orderBy('f.createdAt', 'DESC');

        $countQb = $this->fineRepository->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.loan IN (:loans)')
            ->setParameter('loans', $loanIds);
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $fines = $qb->setMaxResults($query->limit)->setFirstResult($offset)->getQuery()->getResult();

        return [
            'data' => $fines,
            'meta' => [
                'page' => $query->page,
                'limit' => $query->limit,
                'total' => $total,
                'totalPages' => $total > 0 ? (int)ceil($total / $query->limit) : 0
            ]
        ];
    }
}
