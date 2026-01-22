<?php
namespace App\Tests\Functional;

use App\Entity\User;

class AdminUserControllerTest extends ApiTestCase
{
    public function testUpdateRequiresAdminRole(): void
    {
        $user = $this->createUser('user@example.com', ['ROLE_USER']);
        $target = $this->createUser('target@example.com', ['ROLE_USER'], 'StrongPass1', 'Target User');
        $client = $this->createAuthenticatedClient($user);

        $this->jsonRequest($client, 'PUT', '/api/admin/users/' . $target->getId(), [
            'name' => 'Updated Name',
        ]);

        $this->assertResponseStatusCodeSame(403);
        $reloaded = $this->entityManager->getRepository(User::class)->find($target->getId());
        self::assertSame('Target User', $reloaded->getName());
    }

    public function testUpdateUserAsAdmin(): void
    {
        $admin = $this->createUser('admin@example.com', ['ROLE_ADMIN']);
        $target = $this->createUser('target@example.com', ['ROLE_USER'], 'StrongPass1', 'Target User');
        $client = $this->createAuthenticatedClient($admin);

        $this->jsonRequest($client, 'PUT', '/api/admin/users/' . $target->getId(), [
            'name' => 'Updated Name',
            'roles' => ['ROLE_USER', 'ROLE_LIBRARIAN'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $reloaded = $this->entityManager->getRepository(User::class)->find($target->getId());
        self::assertSame('Updated Name', $reloaded->getName());
        self::assertSame(['ROLE_USER', 'ROLE_LIBRARIAN'], $reloaded->getRoles());
    }

    public function testDeleteRejectsSelfDeletion(): void
    {
        $admin = $this->createUser('admin@example.com', ['ROLE_ADMIN']);
        $client = $this->createAuthenticatedClient($admin);

        $this->sendRequest($client, 'DELETE', '/api/admin/users/' . $admin->getId());

        $this->assertResponseStatusCodeSame(400);
        $stillThere = $this->entityManager->getRepository(User::class)->find($admin->getId());
        self::assertNotNull($stillThere);
    }

    public function testDeleteUserAsAdmin(): void
    {
        $admin = $this->createUser('admin@example.com', ['ROLE_ADMIN']);
        $target = $this->createUser('target@example.com', ['ROLE_USER']);
        $client = $this->createAuthenticatedClient($admin);

        $this->sendRequest($client, 'DELETE', '/api/admin/users/' . $target->getId());

        $this->assertResponseStatusCodeSame(204);
        $deleted = $this->entityManager->getRepository(User::class)->find($target->getId());
        self::assertNull($deleted);
    }
}
