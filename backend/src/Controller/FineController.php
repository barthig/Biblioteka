<?php
namespace App\Controller;

use App\Entity\Fine;
use App\Entity\Loan;
use App\Repository\FineRepository;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FineController extends AbstractController
{
    public function list(Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        /** @var FineRepository $repo */
        $repo = $doctrine->getRepository(Fine::class);

        if ($security->hasRole($request, 'ROLE_LIBRARIAN')) {
            $fines = $repo->findAll();
            return $this->json($fines, 200, [], ['groups' => ['fine:read', 'loan:read']]);
        }

        $payload = $security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $userId = (int) $payload['sub'];
        $loans = $doctrine->getRepository(Loan::class)->findBy(['user' => $userId]);
        if (empty($loans)) {
            return $this->json([], 200, []);
        }

        $loanIds = array_map(static fn ($loan) => $loan->getId(), $loans);
        $fines = $repo->createQueryBuilder('f')
            ->andWhere('f.loan IN (:loans)')
            ->setParameter('loans', $loanIds)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->json($fines, 200, [], ['groups' => ['fine:read', 'loan:read']]);
    }

    public function pay(string $id, Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid fine id'], 400);
        }

        /** @var FineRepository $repo */
        $repo = $doctrine->getRepository(Fine::class);
        $fine = $repo->find((int) $id);
        if (!$fine) {
            return $this->json(['error' => 'Fine not found'], 404);
        }

        $payload = $security->getJwtPayload($request);
        $isLibrarian = $security->hasRole($request, 'ROLE_LIBRARIAN');
        $isOwner = $payload && isset($payload['sub']) && $payload['sub'] == $fine->getLoan()->getUser()->getId();

        if (!($isLibrarian || $isOwner)) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        if ($fine->getPaidAt() !== null) {
            return $this->json(['error' => 'Fine already paid'], 400);
        }

        $fine->markAsPaid();
        $em = $doctrine->getManager();
        $em->persist($fine);
        $em->flush();

        return $this->json($fine, 200, [], ['groups' => ['fine:read', 'loan:read']]);
    }
}
