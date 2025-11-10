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
            return $this->json($fines, 200, [], [
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
            return $this->json([], 200, []);
        }

        $loanIds = array_map(static fn ($loan) => $loan->getId(), $loans);
        $fines = $repo->createQueryBuilder('f')
            ->andWhere('f.loan IN (:loans)')
            ->setParameter('loans', $loanIds)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->json($fines, 200, [], [
            'groups' => ['fine:read', 'loan:read'],
            'json_encode_options' => \JSON_PRESERVE_ZERO_FRACTION,
        ]);
    }

    public function create(Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $loanId = $data['loanId'] ?? null;
        if (!$loanId || !ctype_digit((string) $loanId)) {
            return $this->json(['error' => 'Missing or invalid loanId'], 400);
        }

        $reason = isset($data['reason']) ? trim((string) $data['reason']) : '';
        if ($reason === '') {
            return $this->json(['error' => 'Reason is required'], 400);
        }

        $amountValue = $data['amount'] ?? null;
        if (!is_numeric($amountValue)) {
            return $this->json(['error' => 'Invalid amount'], 400);
        }
        $amount = (float) $amountValue;
        if ($amount <= 0) {
            return $this->json(['error' => 'Amount must be greater than zero'], 400);
        }

        $currency = isset($data['currency']) ? strtoupper(trim((string) $data['currency'])) : 'PLN';
        if (strlen($currency) !== 3) {
            return $this->json(['error' => 'Currency should be a 3-letter code'], 400);
        }

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
