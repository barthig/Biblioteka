<?php
namespace App\Controller;

use App\Entity\AcquisitionBudget;
use App\Entity\AcquisitionExpense;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AcquisitionBudgetController extends AbstractController
{
    public function list(Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $year = $request->query->get('year');
        $criteria = [];
        if ($year) {
            $criteria['fiscalYear'] = $year;
        }

        $budgets = $doctrine->getRepository(AcquisitionBudget::class)->findBy($criteria, ['fiscalYear' => 'DESC']);
        return $this->json($budgets, 200, [], ['groups' => ['budget:read']]);
    }

    public function create(Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        if (empty($data['name']) || empty($data['fiscalYear'])) {
            return $this->json(['error' => 'Name and fiscalYear are required'], 400);
        }
        if (!isset($data['allocatedAmount']) || !is_numeric($data['allocatedAmount'])) {
            return $this->json(['error' => 'Allocated amount must be numeric'], 400);
        }

        $budget = (new AcquisitionBudget())
            ->setName((string) $data['name'])
            ->setFiscalYear((string) $data['fiscalYear'])
            ->setCurrency(isset($data['currency']) ? (string) $data['currency'] : 'PLN')
            ->setAllocatedAmount((string) $data['allocatedAmount']);

        if (isset($data['spentAmount']) && is_numeric($data['spentAmount'])) {
            $budget->setSpentAmount((string) $data['spentAmount']);
        }

        $em = $doctrine->getManager();
        $em->persist($budget);
        $em->flush();

        return $this->json($budget, 201, [], ['groups' => ['budget:read']]);
    }

    public function update(string $id, Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid budget id'], 400);
        }

        $budget = $doctrine->getRepository(AcquisitionBudget::class)->find((int) $id);
        if (!$budget) {
            return $this->json(['error' => 'Budget not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        if (isset($data['name'])) {
            $budget->setName((string) $data['name']);
        }
        if (isset($data['fiscalYear'])) {
            $budget->setFiscalYear((string) $data['fiscalYear']);
        }
        if (isset($data['allocatedAmount']) && is_numeric($data['allocatedAmount'])) {
            $budget->setAllocatedAmount((string) $data['allocatedAmount']);
        }
        if (isset($data['currency'])) {
            $budget->setCurrency((string) $data['currency']);
        }

        $em = $doctrine->getManager();
        $em->persist($budget);
        $em->flush();

        return $this->json($budget, 200, [], ['groups' => ['budget:read']]);
    }

    public function addExpense(string $id, Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid budget id'], 400);
        }

        $budget = $doctrine->getRepository(AcquisitionBudget::class)->find((int) $id);
        if (!$budget) {
            return $this->json(['error' => 'Budget not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        if (!isset($data['amount']) || !is_numeric($data['amount'])) {
            return $this->json(['error' => 'Amount must be numeric'], 400);
        }
        if (empty($data['description'])) {
            return $this->json(['error' => 'Description is required'], 400);
        }

        $expense = (new AcquisitionExpense())
            ->setBudget($budget)
            ->setAmount((string) $data['amount'])
            ->setCurrency($budget->getCurrency())
            ->setDescription((string) $data['description']);

        try {
            $expense->setType(isset($data['type']) ? (string) $data['type'] : AcquisitionExpense::TYPE_MISC);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Invalid expense type'], 400);
        }

        if (isset($data['postedAt']) && strtotime((string) $data['postedAt'])) {
            $expense->setPostedAt(new \DateTimeImmutable((string) $data['postedAt']));
        }

        $budget->registerExpense($expense->getAmount());

        $em = $doctrine->getManager();
        $em->persist($expense);
        $em->persist($budget);
        $em->flush();

        return $this->json($expense, 201, [], ['groups' => ['budget:read', 'acquisition:read']]);
    }

    public function summary(string $id, Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid budget id'], 400);
        }

        $budget = $doctrine->getRepository(AcquisitionBudget::class)->find((int) $id);
        if (!$budget) {
            return $this->json(['error' => 'Budget not found'], 404);
        }

        $allocated = round((float) $budget->getAllocatedAmount(), 2);
        $spent = round((float) $budget->getSpentAmount(), 2);
        $remaining = round((float) $budget->remainingAmount(), 2);
        $payload = [
            'id' => $budget->getId(),
            'name' => $budget->getName(),
            'fiscalYear' => $budget->getFiscalYear(),
            'allocatedAmount' => $allocated,
            'spentAmount' => $spent,
            'remainingAmount' => $remaining,
            'currency' => $budget->getCurrency(),
        ];

        return $this->json($payload, 200);
    }
}
