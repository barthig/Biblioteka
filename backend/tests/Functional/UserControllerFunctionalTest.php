<?php
namespace App\Tests\Functional;

use App\Entity\User;

class UserControllerFunctionalTest extends ApiTestCase
{
    public function testListUsersReturnsData(): void
    {
        $this->createUser('first@example.com');
        $this->createUser('second@example.com');

        $client = $this->createApiClient();
        $this->sendRequest($client, 'GET', '/api/users');

        $this->assertResponseStatusCodeSame(200);
        $data = $this->getJsonResponse($client);
        $this->assertCount(2, $data);
    }

    public function testGetUserById(): void
    {
        $user = $this->createUser('first@example.com');

        $client = $this->createApiClient();
        $this->sendRequest($client, 'GET', '/api/users/' . $user->getId());

        $this->assertResponseStatusCodeSame(200);
        $data = $this->getJsonResponse($client);
        $this->assertSame($user->getEmail(), $data['email']);
    }

    public function testGetUserReturns404WhenMissing(): void
    {
        $client = $this->createApiClient();
        $this->sendRequest($client, 'GET', '/api/users/999');

        $this->assertResponseStatusCodeSame(404);
    }
}
