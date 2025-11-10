<?php
namespace App\Controller;

use App\Entity\AcquisitionBudget;
use App\Entity\AcquisitionExpense;
use App\Entity\AcquisitionOrder;
use App\Entity\Supplier;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AcquisitionOrderController extends AbstractController
{
    public function list(Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

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

        $orders = $qb->getQuery()->getResult();
        return $this->json($orders, 200, [], ['groups' => ['acquisition:read', 'supplier:read', 'budget:read']]);
    }

    public function create(Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $supplierId = $data['supplierId'] ?? null;
        if (!$supplierId || !ctype_digit((string) $supplierId)) {
            return $this->json(['error' => 'Missing supplierId'], 400);
        }

        if (empty($data['title'])) {
            return $this->json(['error' => 'Title is required'], 400);
        }

        if (!isset($data['totalAmount']) || !is_numeric($data['totalAmount'])) {
            return $this->json(['error' => 'Invalid total amount'], 400);
        }

        $currency = isset($data['currency']) ? strtoupper(trim((string) $data['currency'])) : 'PLN';
        if (strlen($currency) !== 3) {
            return $this->json(['error' => 'Currency must be a 3-letter code'], 400);
        }

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
