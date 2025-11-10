<?php
namespace App\Tests\Functional;

class AcquisitionBudgetControllerTest extends ApiTestCase
{
    public function testListRequiresLibrarian(): void
    {
        $user = $this->createUser('member-budget@example.com');
        $client = $this->createAuthenticatedClient($user);

        $this->sendRequest($client, 'GET', '/api/admin/acquisitions/budgets');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateBudgetRequiresNumericAmount(): void
    {
        $librarian = $this->createUser('budget-librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->jsonRequest($client, 'POST', '/api/admin/acquisitions/budgets', [
            'name' => 'Invalid Budget',
            'fiscalYear' => '2025',
            'allocatedAmount' => 'not-a-number',
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testAddExpenseAndSummary(): void
    {
        $librarian = $this->createUser('budget-flow@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->jsonRequest($client, 'POST', '/api/admin/acquisitions/budgets', [
            'name' => 'Collection 2025',
            'fiscalYear' => '2025',
            'allocatedAmount' => '1000',
            'currency' => 'PLN',
        ]);
        $this->assertResponseStatusCodeSame(201);
        $budgetData = $this->getJsonResponse($client);
        $budgetId = $budgetData['id'];

        $this->jsonRequest($client, 'POST', '/api/admin/acquisitions/budgets/' . $budgetId . '/expenses', [
            'amount' => '125.75',
            'description' => 'New releases purchase',
            'type' => 'MISC',
        ]);
        $this->assertResponseStatusCodeSame(201);
        $expense = $this->getJsonResponse($client);
        $this->assertSame('125.75', $expense['amount']);
        $this->assertSame('MISC', $expense['type']);

        $this->sendRequest($client, 'GET', '/api/admin/acquisitions/budgets/' . $budgetId . '/summary');
        $this->assertResponseStatusCodeSame(200);
        $summary = $this->getJsonResponse($client);
        $this->assertSame(1000.0, $summary['allocatedAmount']);
        $this->assertSame(125.75, $summary['spentAmount']);
        $this->assertSame(874.25, $summary['remainingAmount']);
        $this->assertSame('PLN', $summary['currency']);
    }

    public function testUpdateBudgetAdjustsFields(): void
    {
        $librarian = $this->createUser('budget-update@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->jsonRequest($client, 'POST', '/api/admin/acquisitions/budgets', [
            'name' => 'Initial Budget',
            'fiscalYear' => '2024',
            'allocatedAmount' => '500.00',
            'currency' => 'USD',
        ]);
        $this->assertResponseStatusCodeSame(201);
        $created = $this->getJsonResponse($client);

        $this->jsonRequest($client, 'PUT', '/api/admin/acquisitions/budgets/' . $created['id'], [
            'name' => 'Updated Budget',
            'fiscalYear' => '2025',
            'allocatedAmount' => '750.40',
            'currency' => 'EUR',
        ]);

        $this->assertResponseStatusCodeSame(200);
        $updated = $this->getJsonResponse($client);
        $this->assertSame('Updated Budget', $updated['name']);
        $this->assertSame('2025', $updated['fiscalYear']);
        $this->assertSame('750.40', $updated['allocatedAmount']);
        $this->assertSame('EUR', $updated['currency']);
    }
}
