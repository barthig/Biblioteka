<?php
namespace App\Controller;

use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class DashboardController extends AbstractController
{
    public function overview(ManagerRegistry $doctrine): JsonResponse
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $bookCount = (int)$entityManager
            ->createQuery('SELECT COUNT(b.id) FROM App\\Entity\\Book b')
            ->getSingleScalarResult();

        $userCount = (int)$entityManager
            ->createQuery('SELECT COUNT(u.id) FROM App\\Entity\\User u')
            ->getSingleScalarResult();

        $activeLoans = (int)$entityManager
            ->createQuery('SELECT COUNT(l.id) FROM App\\Entity\\Loan l WHERE l.returnedAt IS NULL')
            ->getSingleScalarResult();

        $overdueCount = (int)$entityManager
            ->createQuery('SELECT COUNT(l.id) FROM App\\Entity\\Loan l WHERE l.returnedAt IS NULL AND l.dueAt < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getSingleScalarResult();

        return $this->json([
            'books' => $bookCount,
            'users' => $userCount,
            'activeLoans' => $activeLoans,
            'overdueLoans' => $overdueCount,
        ], 200);
    }
}
