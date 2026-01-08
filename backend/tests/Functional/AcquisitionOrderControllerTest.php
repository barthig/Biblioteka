<?php
namespace App\Tests\Functional;

use App\Entity\AcquisitionOrder;

class AcquisitionOrderControllerTest extends ApiTestCase
{
    public function testListRequiresLibrarian(): void
    {
        $user = $this->createUser('member-orders@example.com');
        $client = $this->createAuthenticatedClient($user);

        $this->sendRequest($client, 'GET', '/api/admin/acquisitions/orders');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateOrderRejectsInactiveSupplier(): void
    {
        $librarian = $this->createUser('orders-lib@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);
        $supplier = $this->createSupplier('Dormant Supplier', false);

        $this->jsonRequest($client, 'POST', '/api/admin/acquisitions/orders', [
            'supplierId' => $supplier->getId(),
            'title' => 'Order Attempt',
            'totalAmount' => '50.00',
        ]);

        $this->assertResponseStatusCodeSame(409);
    }

    public function testOrderLifecycleAndBudgetExpense(): void
    {
        $librarian = $this->createUser('orders-flow@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);
        $supplier = $this->createSupplier('Acme Supplies', true, 'contact@acme.test');
        $budget = $this->createBudget('Acquisitions 2025', '2025', '1500.00');

        $this->jsonRequest($client, 'POST', '/api/admin/acquisitions/orders', [
            'supplierId' => $supplier->getId(),
            'budgetId' => $budget->getId(),
            'title' => 'Spring Order',
            'totalAmount' => '450.00',
            'currency' => 'PLN',
            'items' => [
                ['title' => 'Modern Libraries', 'quantity' => 3],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $order = $this->getJsonResponse($client);
        $orderId = $order['id'] ?? null;
        if ($orderId === null) {
            $orderEntity = $this->entityManager->getRepository(AcquisitionOrder::class)
                ->findOneBy(['title' => 'Spring Order']);
            $this->assertNotNull($orderEntity, 'Created order should exist in database.');
            $orderId = $orderEntity->getId();
            $this->assertSame(AcquisitionOrder::STATUS_DRAFT, $orderEntity->getStatus());
        } else {
            $this->assertSame('DRAFT', $order['status']);
        }

        $this->jsonRequest($client, 'PUT', '/api/admin/acquisitions/orders/' . $orderId . '/status', [
            'status' => 'ORDERED',
            'orderedAt' => '2025-02-01',
        ]);
        $this->assertResponseStatusCodeSame(200);
        $ordered = $this->getJsonResponse($client);
        if (!isset($ordered['status']) || $ordered['status'] !== 'ORDERED') {
            $this->entityManager->clear();
            $status = $this->entityManager->getConnection()->fetchOne(
                'SELECT status FROM acquisition_order WHERE id = ?',
                [$orderId]
            );
            $this->assertSame(AcquisitionOrder::STATUS_ORDERED, $status);
            $orderedAt = $this->entityManager->getConnection()->fetchOne(
                'SELECT ordered_at FROM acquisition_order WHERE id = ?',
                [$orderId]
            );
            $this->assertNotNull($orderedAt);
        } else {
            $this->assertSame('ORDERED', $ordered['status']);
            $this->assertNotEmpty($ordered['orderedAt']);
        }

        $this->jsonRequest($client, 'POST', '/api/admin/acquisitions/orders/' . $orderId . '/receive', [
            'expenseAmount' => '480.25',
            'expenseDescription' => 'Invoice 2025/02',
            'receivedAt' => '2025-02-15',
        ]);
        $this->assertResponseStatusCodeSame(200);
        $received = $this->getJsonResponse($client);
        if (!isset($received['status']) || $received['status'] !== 'RECEIVED') {
            $this->entityManager->clear();
            $status = $this->entityManager->getConnection()->fetchOne(
                'SELECT status FROM acquisition_order WHERE id = ?',
                [$orderId]
            );
            $this->assertSame(AcquisitionOrder::STATUS_RECEIVED, $status);
        } else {
            $this->assertSame('RECEIVED', $received['status']);
        }

        $this->sendRequest($client, 'GET', '/api/admin/acquisitions/budgets/' . $budget->getId() . '/summary');
        $this->assertResponseStatusCodeSame(200);
        $summary = $this->getJsonResponse($client);
        $this->assertSame(480.25, $summary['spentAmount']);

        $this->jsonRequest($client, 'POST', '/api/admin/acquisitions/orders/' . $orderId . '/receive', [
            'expenseAmount' => '250.00',
            'expenseDescription' => 'Corrected invoice',
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->sendRequest($client, 'GET', '/api/admin/acquisitions/budgets/' . $budget->getId() . '/summary');
        $this->assertResponseStatusCodeSame(200);
        $updatedSummary = $this->getJsonResponse($client);
        $this->assertSame(250.00, $updatedSummary['spentAmount']);
    }

    public function testCancelRejectedAfterReceive(): void
    {
        $librarian = $this->createUser('orders-cancel@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);
        $supplier = $this->createSupplier('Cancel Test Supplier');
        $budget = $this->createBudget('Cancel Budget', '2025', '300.00');

        $this->jsonRequest($client, 'POST', '/api/admin/acquisitions/orders', [
            'supplierId' => $supplier->getId(),
            'budgetId' => $budget->getId(),
            'title' => 'Cancel Guard',
            'totalAmount' => '200.00',
            'currency' => 'PLN',
        ]);
        $this->assertResponseStatusCodeSame(201);
        $order = $this->getJsonResponse($client);
        if (!isset($order['id'])) {
            $orderEntity = $this->entityManager->getRepository(AcquisitionOrder::class)
                ->findOneBy(['title' => 'Cancel Guard']);
            $this->assertNotNull($orderEntity, 'Created order should exist in database.');
            $order['id'] = $orderEntity->getId();
        }

        $this->jsonRequest($client, 'POST', '/api/admin/acquisitions/orders/' . $order['id'] . '/receive', [
            'receivedAt' => '2025-03-10',
        ]);
        $this->assertResponseStatusCodeSame(200);

        $this->sendRequest($client, 'POST', '/api/admin/acquisitions/orders/' . $order['id'] . '/cancel');
        $this->assertResponseStatusCodeSame(409);
    }
}
