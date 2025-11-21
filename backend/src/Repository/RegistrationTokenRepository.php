<?php
namespace App\Repository;

use App\Entity\RegistrationToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RegistrationTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegistrationToken::class);
    }

    public function findActiveByToken(string $token): ?RegistrationToken
    {
        return $this->createQueryBuilder('rt')
            ->innerJoin('rt.user', 'u')->addSelect('u')
            ->where('rt.token = :token')
            ->andWhere('rt.usedAt IS NULL')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
