<?php
namespace App\Tests\Functional;

use App\Entity\User;

class UserManagementControllerTest extends ApiTestCase
{
    public function testCreateUserRequiresLibrarian(): void
    {
        $user = $this->createUser('user@example.com');
        $client = $this->createAuthenticatedClient($user);

        $this->jsonRequest($client, 'POST', '/api/users', [
            'email' => 'new@example.com',
            'name' => 'New User',
            'roles' => ['ROLE_USER'],
            'password' => 'StrongPass1',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateUserAsLibrarian(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->jsonRequest($client, 'POST', '/api/users', [
            'email' => 'new@example.com',
            'name' => 'New User',
            'roles' => ['ROLE_USER'],
            'password' => 'StrongPass1',
        ]);

        $this->assertResponseStatusCodeSame(201);

        $repo = $this->entityManager->getRepository(User::class);
        $created = $repo->findOneBy(['email' => 'new@example.com']);
        self::assertNotNull($created);
        /** @var \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get('security.user_password_hasher');
        self::assertTrue($hasher->isPasswordValid($created, 'StrongPass1'));
    }

    public function testUpdateUserRolesRequiresAdmin(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $user = $this->createUser('user@example.com', ['ROLE_USER'], 'StrongPass1', 'Original Name');
        $client = $this->createAuthenticatedClient($librarian);

        $this->jsonRequest($client, 'PUT', '/api/users/' . $user->getId(), [
            'name' => 'Updated Name',
            'roles' => ['ROLE_USER', 'ROLE_MEMBER'],
        ]);

        $this->assertResponseStatusCodeSame(403);

        $reload = $this->entityManager->getRepository(User::class)->find($user->getId());
        self::assertSame(['ROLE_USER'], $reload->getRoles());
    }

    public function testUpdateUserAsAdminCanChangeRoles(): void
    {
        $admin = $this->createUser('admin@example.com', ['ROLE_ADMIN']);
        $user = $this->createUser('user@example.com', ['ROLE_USER'], 'StrongPass1', 'Original Name');
        $client = $this->createAuthenticatedClient($admin);

        $this->jsonRequest($client, 'PUT', '/api/users/' . $user->getId(), [
            'name' => 'Updated Name',
            'roles' => ['ROLE_USER', 'ROLE_MEMBER'],
        ]);

        $this->assertResponseStatusCodeSame(200);

        $updated = $this->entityManager->getRepository(User::class)->find($user->getId());
        self::assertSame('Updated Name', $updated->getName());
        self::assertSame(['ROLE_USER', 'ROLE_MEMBER'], $updated->getRoles());
    }

    public function testUpdateUserNotFoundReturns404(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->jsonRequest($client, 'PUT', '/api/users/999', [
            'name' => 'Irrelevant',
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteUser(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $user = $this->createUser('user@example.com');

        $client = $this->createAuthenticatedClient($librarian);
        $this->sendRequest($client, 'DELETE', '/api/users/' . $user->getId());

        $this->assertResponseStatusCodeSame(204);

        $deleted = $this->entityManager->getRepository(User::class)->find($user->getId());
        self::assertNull($deleted);
    }
}
