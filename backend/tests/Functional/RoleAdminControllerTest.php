<?php
namespace App\Tests\Functional;

use App\Entity\StaffRole;

class RoleAdminControllerTest extends ApiTestCase
{
    public function testListRequiresAdmin(): void
    {
        $client = $this->createAuthenticatedClient($this->createUser('reader@example.com'));
        $this->sendRequest($client, 'GET', '/api/admin/roles');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateUpdateAndAssignRole(): void
    {
        $admin = $this->createUser('admin@example.com', ['ROLE_ADMIN']);
        $target = $this->createUser('user@example.com');
        $client = $this->createAuthenticatedClient($admin);

        $this->jsonRequest($client, 'POST', '/api/admin/roles', [
            'name' => 'Reporter',
            'roleKey' => 'ROLE_REPORTER',
            'modules' => ['reports']
        ]);
        $this->assertResponseStatusCodeSame(201);

        $role = $this->entityManager->getRepository(StaffRole::class)->findOneBy(['roleKey' => 'ROLE_REPORTER']);
        $this->assertNotNull($role);

        $this->jsonRequest($client, 'PUT', '/api/admin/roles/' . $role->getRoleKey(), [
            'modules' => ['reports', 'audit']
        ]);
        $this->assertResponseStatusCodeSame(200);

        $this->jsonRequest($client, 'POST', '/api/admin/roles/' . $role->getRoleKey() . '/assign', [
            'userId' => $target->getId()
        ]);
        $this->assertResponseStatusCodeSame(200);
    }
}
