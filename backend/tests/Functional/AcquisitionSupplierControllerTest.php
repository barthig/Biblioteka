<?php
namespace App\Tests\Functional;

use App\Entity\Supplier;

class AcquisitionSupplierControllerTest extends ApiTestCase
{
    public function testListRequiresLibrarian(): void
    {
        $user = $this->createUser('member@example.com');
        $client = $this->createAuthenticatedClient($user);

        $this->sendRequest($client, 'GET', '/api/admin/acquisitions/suppliers');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testLibrarianCanCreateSupplier(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $payload = [
            'name' => 'Books & Co',
            'contactEmail' => 'sales@example.com',
            'active' => true,
        ];

        $this->jsonRequest($client, 'POST', '/api/admin/acquisitions/suppliers', $payload);

        $this->assertResponseStatusCodeSame(201);
        $data = $this->getJsonResponse($client);
        $this->assertSame('Books & Co', $data['name']);
        $this->assertTrue($data['active']);
        $this->assertSame('sales@example.com', $data['contactEmail']);
    }

    public function testListFiltersByActiveFlag(): void
    {
        $librarian = $this->createUser('filter@example.com', ['ROLE_LIBRARIAN']);
        $activeSupplier = $this->createSupplier('Gamma Stationery', true);
        $inactiveSupplier = $this->createSupplier('Delta Supplies', false);

        $client = $this->createAuthenticatedClient($librarian);
        $this->sendRequest($client, 'GET', '/api/admin/acquisitions/suppliers?active=true');

        $this->assertResponseStatusCodeSame(200);
        $list = $this->getJsonResponse($client);
        $this->assertNotEmpty($list);
        $ids = array_column($list, 'id');
        $this->assertContains($activeSupplier->getId(), $ids);
        $this->assertNotContains($inactiveSupplier->getId(), $ids);

        $this->sendRequest($client, 'GET', '/api/admin/acquisitions/suppliers?active=false');
        $this->assertResponseStatusCodeSame(200);
        $disabled = $this->getJsonResponse($client);
        $this->assertNotEmpty($disabled);
        $inactiveIds = array_column($disabled, 'id');
        $this->assertContains($inactiveSupplier->getId(), $inactiveIds);
    }

    public function testUpdateRejectsInvalidActiveFlag(): void
    {
        $librarian = $this->createUser('lib-update@example.com', ['ROLE_LIBRARIAN']);
        $supplier = $this->createSupplier('Alpha Books');
        $client = $this->createAuthenticatedClient($librarian);

        $this->jsonRequest(
            $client,
            'PUT',
            '/api/admin/acquisitions/suppliers/' . $supplier->getId(),
            ['active' => 'maybe']
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testDeactivateMarksSupplierInactive(): void
    {
        $librarian = $this->createUser('lib-deactivate@example.com', ['ROLE_LIBRARIAN']);
        $supplier = $this->createSupplier('Beta Books');
        $client = $this->createAuthenticatedClient($librarian);

        $this->sendRequest($client, 'DELETE', '/api/admin/acquisitions/suppliers/' . $supplier->getId());

        $this->assertResponseStatusCodeSame(204);
        $reloaded = $this->entityManager->getRepository(Supplier::class)->find($supplier->getId());
        $this->assertNotNull($reloaded);
        $this->assertFalse($reloaded->isActive());
    }
}
