<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Test integracyjny: Proces akwizycji książek
 */
class AcquisitionIntegrationTest extends WebTestCase
{
    private $client;
    private $librarianToken;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        
        // Login jako bibliotekarz
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_SECRET' => $_ENV['API_SECRET']
        ], json_encode([
            'email' => 'librarian@example.com',
            'password' => 'librarian123'
        ]));
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->librarianToken = $data['token'];
    }

    public function testCompleteAcquisitionProcess(): void
    {
        // 1. Utworzenie budżetu
        $this->client->request('POST', '/api/acquisitions/budgets', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->librarianToken,
            'HTTP_X_API_SECRET' => $_ENV['API_SECRET']
        ], json_encode([
            'year' => 2025,
            'totalAmount' => 50000.00,
            'category' => 'books'
        ]));
        
        $this->assertResponseStatusCodeSame(201);
        $budget = json_decode($this->client->getResponse()->getContent(), true);
        $budgetId = $budget['id'];

        // 2. Dodanie dostawcy
        $this->client->request('POST', '/api/acquisitions/suppliers', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->librarianToken,
            'HTTP_X_API_SECRET' => $_ENV['API_SECRET']
        ], json_encode([
            'name' => 'Test Publisher',
            'email' => 'publisher@example.com',
            'phone' => '+48123456789',
            'address' => 'Warszawa, ul. Testowa 1'
        ]));
        
        $this->assertResponseStatusCodeSame(201);
        $supplier = json_decode($this->client->getResponse()->getContent(), true);
        $supplierId = $supplier['id'];

        // 3. Utworzenie zamówienia
        $this->client->request('POST', '/api/acquisitions/orders', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->librarianToken,
            'HTTP_X_API_SECRET' => $_ENV['API_SECRET']
        ], json_encode([
            'supplierId' => $supplierId,
            'budgetId' => $budgetId,
            'items' => [
                [
                    'title' => 'Test Book 1',
                    'isbn' => '978-83-1234-567-8',
                    'quantity' => 5,
                    'unitPrice' => 49.99
                ]
            ]
        ]));
        
        $this->assertResponseStatusCodeSame(201);
        $order = json_decode($this->client->getResponse()->getContent(), true);
        $orderId = $order['id'];

        // 4. Sprawdzenie budżetu - powinien się zmniejszyć
        $this->client->request('GET', "/api/acquisitions/budgets/{$budgetId}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->librarianToken,
            'HTTP_X_API_SECRET' => $_ENV['API_SECRET']
        ]);
        
        $this->assertResponseIsSuccessful();
        $updatedBudget = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertLessThan($budget['totalAmount'], $updatedBudget['remainingAmount']);

        // 5. Oznaczenie zamówienia jako dostarczone
        $this->client->request('PUT', "/api/acquisitions/orders/{$orderId}/status", [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->librarianToken,
            'HTTP_X_API_SECRET' => $_ENV['API_SECRET']
        ], json_encode([
            'status' => 'delivered'
        ]));
        
        $this->assertResponseIsSuccessful();

        // 6. Raport akwizycji
        $this->client->request('GET', '/api/acquisitions/reports?year=2025', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->librarianToken,
            'HTTP_X_API_SECRET' => $_ENV['API_SECRET']
        ]);
        
        $this->assertResponseIsSuccessful();
        $report = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('totalSpent', $report);
        $this->assertArrayHasKey('totalOrders', $report);
    }
}
