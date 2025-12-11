<?php
namespace App\Tests\Functional;

use App\Entity\RegistrationToken;
use App\Entity\User;

class UserJourneyTest extends ApiTestCase
{
    public function testRegistrationLoginBorrowAndRoleManagementFlow(): void
    {
        $client = $this->createClientWithoutSecret();

        $payload = [
            'email' => 'journey@example.com',
            'name' => 'Journey User',
            'password' => 'StrongPass1',
            'privacyConsent' => true,
        ];

        $this->jsonRequest($client, 'POST', '/api/auth/register', $payload);
        $this->assertResponseStatusCodeSame(201);

        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'journey@example.com']);
        self::assertNotNull($user, 'User should be persisted after registration');

        $token = $this->entityManager->getRepository(RegistrationToken::class)->findOneBy(['user' => $user]);
        self::assertNotNull($token, 'Verification token should be issued');

        $this->sendRequest($client, 'GET', '/api/auth/verify/' . $token->getToken());
        $this->assertResponseStatusCodeSame(200);

        $this->entityManager->clear();
        $user = $userRepository->find($user->getId());
        self::assertTrue($user->isVerified(), 'User should be verified after token confirmation');

        $jwt = $this->loginAndGetToken('journey@example.com', 'StrongPass1');
        self::assertNotEmpty($jwt, 'Verified user should be able to log in');

        $borrowClient = $this->createApiClient($jwt);
        $book = $this->createBook('Integration Journey Book');
        $this->jsonRequest($borrowClient, 'POST', '/api/loans', [
            'userId' => $user->getId(),
            'bookId' => $book->getId(),
            'days' => 7,
        ]);
        $this->assertResponseStatusCodeSame(201, 'Borrower should be allowed to create own loan');

        $this->jsonRequest($borrowClient, 'PUT', '/api/users/' . $user->getId(), [
            'roles' => ['ROLE_ADMIN'],
            'name' => 'Attempted Self Promotion',
        ]);
        $this->assertResponseStatusCodeSame(403, 'Non-admin users cannot change roles');

        $admin = $this->createUser('admin-journey@example.com', ['ROLE_ADMIN']);
        $adminClient = $this->createAuthenticatedClient($admin);
        $this->jsonRequest($adminClient, 'PUT', '/api/users/' . $user->getId(), [
            'roles' => ['ROLE_USER', 'ROLE_LIBRARIAN'],
            'name' => 'Upgraded Journey User',
        ]);
        $this->assertResponseStatusCodeSame(200);

        $this->entityManager->clear();
        $updatedUser = $userRepository->find($user->getId());
        self::assertSame(['ROLE_USER', 'ROLE_LIBRARIAN'], $updatedUser->getRoles());
        self::assertSame('Upgraded Journey User', $updatedUser->getName());
    }
}