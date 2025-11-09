<?php
namespace App\Controller;

use App\Entity\Reservation;
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

        $bookCount = (int) $entityManager
            ->createQuery('SELECT COUNT(b.id) FROM App\\Entity\\Book b')
            ->getSingleScalarResult();

        $userCount = (int) $entityManager
            ->createQuery('SELECT COUNT(u.id) FROM App\\Entity\\User u')
            ->getSingleScalarResult();

        $activeLoans = (int) $entityManager
            ->createQuery('SELECT COUNT(l.id) FROM App\\Entity\\Loan l WHERE l.returnedAt IS NULL')
            ->getSingleScalarResult();

        $queueCount = (int) $entityManager
            ->createQuery('SELECT COUNT(r.id) FROM App\\Entity\\Reservation r WHERE r.status = :status')
            ->setParameter('status', \App\Entity\Reservation::STATUS_ACTIVE)
            ->getSingleScalarResult();

        return $this->json([
            'booksCount' => $bookCount,
            'usersCount' => $userCount,
            'loansCount' => $activeLoans,
            'reservationsQueue' => $queueCount,
        ], 200);
    }
}
