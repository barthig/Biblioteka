<?php

namespace App\Tests\Integration;

use App\Entity\AcquisitionBudget;
use App\Entity\AcquisitionOrder;
use App\Entity\Supplier;
use App\Tests\Functional\ApiTestCase;

class AcquisitionIntegrationTest extends ApiTestCase
{
    public function testCompleteAcquisitionProcess(): void
    {
        $librarian = $this->createUser('integration-librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->jsonRequest($client, 'POST', '/api/admin/acquisitions/budgets', [
            'name' => 'Integration Budget 2025',
            'fiscalYear' => '2025',
            'allocatedAmount' => '50000.00',
            'currency' => 'PLN',
        ]);
        $this->assertResponseStatusCodeSame(201);
        $budgetPayload = $this->getJsonResponse($client);
        $budgetId = $budgetPayload['id'] ?? null;
        if ($budgetId === null) {
            $budget = $this->entityManager->getRepository(AcquisitionBudget::class)
                ->findOneBy(['name' => 'Integration Budget 2025']);
            $this->assertNotNull($budget);
            $budgetId = $budget->getId();
        }

        $this->jsonRequest($client, 'POST', '/api/admin/acquisitions/suppliers', [
            'name' => 'Integration Publisher',
            'contactEmail' => 'publisher@example.com',
            'contactPhone' => '+48123456789',
            'addressLine' => 'ul. Testowa 1',
            'city' => 'Warszawa',
            'country' => 'PL',
            'active' => true,
        ]);
        $this->assertResponseStatusCodeSame(201);
        $supplierPayload = $this->getJsonResponse($client);
        $supplierId = $supplierPayload['id'] ?? null;
        if ($supplierId === null) {
            $supplier = $this->entityManager->getRepository(Supplier::class)
                ->findOneBy(['name' => 'Integration Publisher']);
            $this->assertNotNull($supplier);
            $supplierId = $supplier->getId();
        }

        $this->jsonRequest($client, 'POST', '/api/admin/acquisitions/orders', [
            'supplierId' => $supplierId,
            'budgetId' => $budgetId,
            'title' => 'Integration Order',
            'totalAmount' => '249.95',
            'currency' => 'PLN',
            'items' => [
                [
                    'title' => 'Test Book 1',
                    'isbn' => '978-83-1234-567-8',
                    'quantity' => 5,
                    'unitPrice' => 49.99,
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $orderPayload = $this->getJsonResponse($client);
        $orderId = $orderPayload['id'] ?? null;
        if ($orderId === null) {
            $order = $this->entityManager->getRepository(AcquisitionOrder::class)
                ->findOneBy(['title' => 'Integration Order']);
            $this->assertNotNull($order);
            $orderId = $order->getId();
        }

        $this->sendRequest($client, 'GET', '/api/admin/acquisitions/budgets/' . $budgetId . '/summary');
        $this->assertResponseStatusCodeSame(200);
        $initialSummary = $this->getJsonResponse($client);
        $this->assertArrayHasKey('remainingAmount', $initialSummary);
        $this->assertSame(0.0, (float) $initialSummary['spentAmount']);

        $this->jsonRequest($client, 'POST', '/api/admin/acquisitions/orders/' . $orderId . '/receive', [
            'receivedAt' => '2025-02-15',
            'expenseAmount' => '249.95',
            'expenseDescription' => 'Invoice 2025/02',
        ]);
        $this->assertResponseStatusCodeSame(200);

        $this->sendRequest($client, 'GET', '/api/admin/acquisitions/budgets/' . $budgetId . '/summary');
        $this->assertResponseStatusCodeSame(200);
        $updatedSummary = $this->getJsonResponse($client);
        $this->assertSame(249.95, (float) $updatedSummary['spentAmount']);
        $this->assertSame(49750.05, (float) $updatedSummary['remainingAmount']);

        $this->entityManager->clear();
        $storedOrder = $this->entityManager->getRepository(AcquisitionOrder::class)->find($orderId);
        $this->assertNotNull($storedOrder);
        $this->assertSame(AcquisitionOrder::STATUS_RECEIVED, $storedOrder->getStatus());
    }
}

