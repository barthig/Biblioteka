<?php
namespace App\Repository;

use App\Entity\StaffRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StaffRoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StaffRole::class);
    }

    public function findOneByRoleKey(string $roleKey): ?StaffRole
    {
        $normalized = strtoupper(trim($roleKey));
        if (!str_starts_with($normalized, 'ROLE_')) {
            $normalized = 'ROLE_' . $normalized;
        }

        return $this->findOneBy(['roleKey' => $normalized]);
    }
}
