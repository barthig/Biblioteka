<?php
namespace App\Tests\Functional;

class NotificationControllerTest extends ApiTestCase
{
    public function testListNotificationsRequiresLibrarian(): void
    {
        $user = $this->createUser('user@example.com');
        $client = $this->createAuthenticatedClient($user);

        $this->sendRequest($client, 'GET', '/api/notifications');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testListNotificationsReturnsData(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->sendRequest($client, 'GET', '/api/notifications');

        $this->assertResponseStatusCodeSame(200);
        $data = $this->getJsonResponse($client);
        $this->assertNotEmpty($data);
    }

    public function testListNotificationsServiceDown(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->sendRequest($client, 'GET', '/api/notifications?serviceDown=1');

        $this->assertResponseStatusCodeSame(503);
    }

    public function testTriggerNotificationRequiresLibrarian(): void
    {
        $user = $this->createUser('user@example.com');
        $client = $this->createAuthenticatedClient($user);

        $this->jsonRequest($client, 'POST', '/api/notifications/test', [
            'channel' => 'email',
            'target' => 'user@example.com',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testTriggerNotificationValidatesPayload(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->jsonRequest($client, 'POST', '/api/notifications/test', [
            'channel' => 'invalid',
            'target' => 'user@example.com',
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testTriggerNotificationMissingFields(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->jsonRequest($client, 'POST', '/api/notifications/test', []);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testTriggerNotificationQueueDown(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->jsonRequest($client, 'POST', '/api/notifications/test', [
            'channel' => 'email',
            'target' => 'user@example.com',
        ], ['HTTP_X_QUEUE_STATUS' => 'down']);

        $this->assertResponseStatusCodeSame(503);
    }

    public function testTriggerNotificationSuccess(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->jsonRequest($client, 'POST', '/api/notifications/test', [
            'channel' => 'email',
            'target' => 'user@example.com',
            'message' => 'Hi there',
        ]);

        $this->assertResponseStatusCodeSame(202);
        $data = $this->getJsonResponse($client);
        $this->assertSame('queued', $data['status']);
        $this->assertSame('email', $data['channel']);
    }
}
