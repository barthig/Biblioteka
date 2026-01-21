<?php
namespace App\Tests\Functional;

use App\Entity\StaffRole;

class StaffRoleControllerTest extends ApiTestCase
{
    public function testListRequiresAdmin(): void
    {
        $client = $this->createAuthenticatedClient($this->createUser('reader@example.com'));
        $this->sendRequest($client, 'GET', '/api/staff-roles');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateUpdateAndDeleteRole(): void
    {
        $admin = $this->createUser('admin@example.com', ['ROLE_ADMIN']);
        $client = $this->createAuthenticatedClient($admin);

        $this->jsonRequest($client, 'POST', '/api/staff-roles', [
            'name' => 'Reporter',
            'roleKey' => 'ROLE_REPORTER',
            'modules' => ['reports']
        ]);
        $this->assertResponseStatusCodeSame(201);

        $role = $this->entityManager->getRepository(StaffRole::class)->findOneBy(['roleKey' => 'ROLE_REPORTER']);
        $this->assertNotNull($role);

        $this->jsonRequest($client, 'PUT', '/api/staff-roles/' . $role->getId(), [
            'name' => 'Reporter Updated'
        ]);
        $this->assertResponseStatusCodeSame(200);

        $this->sendRequest($client, 'DELETE', '/api/staff-roles/' . $role->getId());
        $this->assertResponseStatusCodeSame(204);
    }
}
