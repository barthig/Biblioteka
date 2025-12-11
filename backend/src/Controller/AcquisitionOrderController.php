<?php
namespace App\Controller;

use App\Controller\Traits\ValidationTrait;
use App\Entity\AcquisitionBudget;
use App\Entity\AcquisitionExpense;
use App\Entity\AcquisitionOrder;
use App\Entity\Supplier;
use App\Request\CreateAcquisitionOrderRequest;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AcquisitionOrderController extends AbstractController
{
    use ValidationTrait;
    public function list(Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(10, $request->query->getInt('limit', 20)));
        $offset = ($page - 1) * $limit;

        /** @var \App\Repository\AcquisitionOrderRepository $repo */
        $repo = $doctrine->getRepository(AcquisitionOrder::class);
        $qb = $repo->createQueryBuilder('o')
            ->leftJoin('o.supplier', 's')->addSelect('s')
            ->leftJoin('o.budget', 'b')->addSelect('b')
            ->orderBy('o.createdAt', 'DESC');

        if ($request->query->has('status')) {
            $status = strtoupper((string) $request->query->get('status'));
            if ($status !== '') {
                $qb->andWhere('o.status = :status')->setParameter('status', $status);
            }
        }

        if ($request->query->has('supplierId') && ctype_digit((string) $request->query->get('supplierId'))) {
            $qb->andWhere('o.supplier = :supplierId')->setParameter('supplierId', (int) $request->query->get('supplierId'));
        }

        if ($request->query->has('budgetId') && ctype_digit((string) $request->query->get('budgetId'))) {
            $qb->andWhere('o.budget = :budgetId')->setParameter('budgetId', (int) $request->query->get('budgetId'));
        }

        $countQb = clone $qb;
        $countQb->select('COUNT(o.id)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $orders = $qb->setMaxResults($limit)->setFirstResult($offset)->getQuery()->getResult();
        
        return $this->json([
            'data' => $orders,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => $total > 0 ? (int)ceil($total / $limit) : 0
            ]
        ], 200, [], ['groups' => ['acquisition:read', 'supplier:read', 'budget:read']]);
    }

    public function create(Request $request, ManagerRegistry $doctrine, SecurityService $security, ValidatorInterface $validator): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        
        // Walidacja DTO
        $dto = $this->mapArrayToDto($data, new CreateAcquisitionOrderRequest());
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }
        
        $supplierId = $dto->supplierId;
        $currency = $dto->currency;

        $supplier = $doctrine->getRepository(Supplier::class)->find((int) $supplierId);
        if (!$supplier) {
            return $this->json(['error' => 'Supplier not found'], 404);
        }
        if (!$supplier->isActive()) {
            return $this->json(['error' => 'Supplier is inactive'], 409);
        }

        $budget = null;
        if (!empty($data['budgetId'])) {
            if (!ctype_digit((string) $data['budgetId'])) {
                return $this->json(['error' => 'Invalid budgetId'], 400);
            }
            $budget = $doctrine->getRepository(AcquisitionBudget::class)->find((int) $data['budgetId']);
            if (!$budget) {
                return $this->json(['error' => 'Budget not found'], 404);
            }
            if ($budget->getCurrency() !== $currency) {
                return $this->json(['error' => 'Budget currency mismatch'], 409);
            }
        }

        $order = new AcquisitionOrder();
        $order->setSupplier($supplier)
            ->setBudget($budget)
            ->setTitle((string) $data['title'])
            ->setDescription($data['description'] ?? null)
            ->setReferenceNumber($data['referenceNumber'] ?? null)
            ->setItems(isset($data['items']) && is_array($data['items']) ? $data['items'] : null)
            ->setCurrency($currency)
            ->setTotalAmount((string) $data['totalAmount']);

        if (!empty($data['expectedAt']) && strtotime((string) $data['expectedAt'])) {
            $order->setExpectedAt(new \DateTimeImmutable((string) $data['expectedAt']));
        }

        if (!empty($data['status'])) {
            $status = strtoupper((string) $data['status']);
            try {
                if ($status === AcquisitionOrder::STATUS_ORDERED) {
                    $order->markOrdered();
                } elseif ($status === AcquisitionOrder::STATUS_SUBMITTED) {
                    $order->markSubmitted();
                } elseif ($status === AcquisitionOrder::STATUS_RECEIVED) {
                    $order->markReceived();
                } else {
                    $order->setStatus($status);
                }
            } catch (\InvalidArgumentException $e) {
                return $this->json(['error' => 'Invalid status provided'], 400);
            }
        }

        $em = $doctrine->getManager();
        $em->persist($order);
        $em->flush();

        return $this->json($order, 201, [], ['groups' => ['acquisition:read', 'supplier:read', 'budget:read']]);
    }

    public function updateStatus(string $id, Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid order id'], 400);
        }

        $order = $doctrine->getRepository(AcquisitionOrder::class)->find((int) $id);
        if (!$order) {
            return $this->json(['error' => 'Order not found'], 404);
        }
        if ($order->getStatus() === AcquisitionOrder::STATUS_CANCELLED) {
            return $this->json(['error' => 'Cancelled orders cannot be received'], 409);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        if (empty($data['status'])) {
            return $this->json(['error' => 'Status is required'], 400);
        }

        $status = strtoupper((string) $data['status']);
        switch ($status) {
            case AcquisitionOrder::STATUS_SUBMITTED:
                $order->markSubmitted();
                break;
            case AcquisitionOrder::STATUS_ORDERED:
                $orderedAt = !empty($data['orderedAt']) && strtotime((string) $data['orderedAt'])
                    ? new \DateTimeImmutable((string) $data['orderedAt'])
                    : null;
                $order->markOrdered($orderedAt);
                break;
            case AcquisitionOrder::STATUS_RECEIVED:
                $receivedAt = !empty($data['receivedAt']) && strtotime((string) $data['receivedAt'])
                    ? new \DateTimeImmutable((string) $data['receivedAt'])
                    : null;
                $order->markReceived($receivedAt);
                break;
            case AcquisitionOrder::STATUS_CANCELLED:
                $order->cancel();
                break;
            case AcquisitionOrder::STATUS_DRAFT:
                $order->setStatus(AcquisitionOrder::STATUS_DRAFT);
                break;
            default:
                return $this->json(['error' => 'Unsupported status transition'], 400);
        }

        if (isset($data['expectedAt']) && strtotime((string) $data['expectedAt'])) {
            $order->setExpectedAt(new \DateTimeImmutable((string) $data['expectedAt']));
        }

        if (isset($data['totalAmount']) && is_numeric($data['totalAmount'])) {
            $order->setTotalAmount((string) $data['totalAmount']);
        }
        if (isset($data['items']) && is_array($data['items'])) {
            $order->setItems($data['items']);
        }

        $em = $doctrine->getManager();
        $em->persist($order);
        $em->flush();

        return $this->json($order, 200, [], ['groups' => ['acquisition:read', 'supplier:read', 'budget:read']]);
    }

    public function receive(string $id, Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid order id'], 400);
        }

        $order = $doctrine->getRepository(AcquisitionOrder::class)->find((int) $id);
        if (!$order) {
            return $this->json(['error' => 'Order not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $receivedAt = !empty($data['receivedAt']) && strtotime((string) $data['receivedAt'])
            ? new \DateTimeImmutable((string) $data['receivedAt'])
            : new \DateTimeImmutable();
        $order->markReceived($receivedAt);

        if (isset($data['totalAmount']) && is_numeric($data['totalAmount'])) {
            $order->setTotalAmount((string) $data['totalAmount']);
        }
        if (isset($data['items']) && is_array($data['items'])) {
            $order->setItems($data['items']);
        }

        $em = $doctrine->getManager();
        $em->persist($order);

        if ($order->getBudget()) {
            $expenseRepo = $doctrine->getRepository(AcquisitionExpense::class);
            $existingExpense = $expenseRepo->findOneBy(['order' => $order]);

            if ($existingExpense) {
                $order->getBudget()->registerExpense('-' . $existingExpense->getAmount());
                $em->remove($existingExpense);
            }

            $expenseAmount = $order->getTotalAmount();
            if (isset($data['expenseAmount']) && is_numeric($data['expenseAmount'])) {
                $expenseAmount = (string) $data['expenseAmount'];
            }

            $expense = (new AcquisitionExpense())
                ->setBudget($order->getBudget())
                ->setOrder($order)
                ->setAmount($expenseAmount)
                ->setCurrency($order->getCurrency())
                ->setDescription($data['expenseDescription'] ?? 'Zakup książek - realizacja zamówienia #' . $order->getId())
                ->setType(AcquisitionExpense::TYPE_ORDER);

            $order->getBudget()->registerExpense($expense->getAmount());

            $em->persist($expense);
            $em->persist($order->getBudget());
        }

        $em->flush();

        return $this->json($order, 200, [], ['groups' => ['acquisition:read', 'supplier:read', 'budget:read']]);
    }

    public function cancel(string $id, Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid order id'], 400);
        }

        $order = $doctrine->getRepository(AcquisitionOrder::class)->find((int) $id);
        if (!$order) {
            return $this->json(['error' => 'Order not found'], 404);
        }

        if ($order->getStatus() === AcquisitionOrder::STATUS_RECEIVED) {
            return $this->json(['error' => 'Order already received'], 409);
        }

        $order->cancel();
        $em = $doctrine->getManager();
        $em->persist($order);
        $em->flush();

        return new JsonResponse(null, 204);
    }
}
