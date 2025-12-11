<?php
namespace App\Controller;

use App\Controller\Traits\ValidationTrait;
use App\Entity\Fine;
use App\Entity\Loan;
use App\Repository\FineRepository;
use App\Request\CreateFineRequest;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FineController extends AbstractController
{
    use ValidationTrait;
    public function list(Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(10, $request->query->getInt('limit', 20)));
        $offset = ($page - 1) * $limit;

        $em = $doctrine->getManager();
        $fineRepository = $em->getRepository(Fine::class);
        assert($fineRepository instanceof FineRepository);

        if ($security->hasRole($request, 'ROLE_LIBRARIAN')) {
            $qb = $fineRepository->createQueryBuilder('f')
                ->leftJoin('f.loan', 'l')->addSelect('l')
                ->leftJoin('l.user', 'u')->addSelect('u')
                ->leftJoin('l.book', 'b')->addSelect('b')
                ->orderBy('f.createdAt', 'DESC');

            $countQb = $fineRepository->createQueryBuilder('f')
                ->select('COUNT(f.id)');
            $total = (int) $countQb->getQuery()->getSingleScalarResult();

            $fines = $qb->setMaxResults($limit)->setFirstResult($offset)->getQuery()->getResult();
            
            return $this->json([
                'data' => $fines,
                'meta' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'totalPages' => $total > 0 ? (int)ceil($total / $limit) : 0
                ]
            ], 200, [], [
                'groups' => ['fine:read', 'loan:read'],
                'json_encode_options' => \JSON_PRESERVE_ZERO_FRACTION,
            ]);
        }

        $payload = $security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $userId = (int) $payload['sub'];
        $loans = $doctrine->getRepository(Loan::class)->findBy(['user' => $userId]);
        if (empty($loans)) {
            return $this->json([
                'data' => [],
                'meta' => [
                    'page' => 1,
                    'limit' => $limit,
                    'total' => 0,
                    'totalPages' => 0
                ]
            ], 200, []);
        }

        $loanIds = array_map(static fn ($loan) => $loan->getId(), $loans);
        $qb = $fineRepository->createQueryBuilder('f')
            ->leftJoin('f.loan', 'l')->addSelect('l')
            ->leftJoin('l.user', 'u')->addSelect('u')
            ->leftJoin('l.book', 'b')->addSelect('b')
            ->andWhere('f.loan IN (:loans)')
            ->setParameter('loans', $loanIds)
            ->orderBy('f.createdAt', 'DESC');

        $countQb = $fineRepository->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.loan IN (:loans)')
            ->setParameter('loans', $loanIds);
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $fines = $qb->setMaxResults($limit)->setFirstResult($offset)->getQuery()->getResult();

        return $this->json([
            'data' => $fines,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => $total > 0 ? (int)ceil($total / $limit) : 0
            ]
        ], 200, [], [
            'groups' => ['fine:read', 'loan:read'],
            'json_encode_options' => \JSON_PRESERVE_ZERO_FRACTION,
        ]);
    }

    public function create(Request $request, ManagerRegistry $doctrine, SecurityService $security, ValidatorInterface $validator): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        
        // Walidacja DTO
        $dto = $this->mapArrayToDto($data, new CreateFineRequest());
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        $loanId = $dto->loanId;
        $reason = $dto->reason;
        $amount = $dto->amount;
        $currency = $dto->currency;

        $loan = $doctrine->getRepository(Loan::class)->find((int) $loanId);
        if (!$loan) {
            return $this->json(['error' => 'Loan not found'], 404);
        }

        $fine = (new Fine())
            ->setLoan($loan)
            ->setAmount(number_format($amount, 2, '.', ''))
            ->setCurrency($currency)
            ->setReason($reason);

        $em = $doctrine->getManager();
        $em->persist($fine);
        $em->flush();

        return $this->json($fine, 201, [], [
            'groups' => ['fine:read', 'loan:read'],
            'json_encode_options' => \JSON_PRESERVE_ZERO_FRACTION,
        ]);
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

        return $this->json($fine, 200, [], [
            'groups' => ['fine:read', 'loan:read'],
            'json_encode_options' => \JSON_PRESERVE_ZERO_FRACTION,
        ]);
    }

    public function cancel(string $id, Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid fine id'], 400);
        }

        /** @var FineRepository $repo */
        $repo = $doctrine->getRepository(Fine::class);
        $fine = $repo->find((int) $id);
        if (!$fine) {
            return $this->json(['error' => 'Fine not found'], 404);
        }

        if ($fine->getPaidAt() !== null) {
            return $this->json(['error' => 'Cannot cancel a paid fine'], 400);
        }

        $em = $doctrine->getManager();
        $em->remove($fine);
        $em->flush();

        return new JsonResponse(null, 204);
    }
}
