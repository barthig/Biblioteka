<?php

namespace App\Tests\Functional;

class UserAvatarControllerTest extends ApiTestCase
{
    private const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aN3cAAAAASUVORK5CYII=';

    public function testUploadAvatarRequiresAuthentication(): void
    {
        $client = $this->createClientWithoutSecret();

        $this->jsonRequest($client, 'POST', '/api/me/avatar', [
            'mimeType' => 'image/png',
            'content' => self::PNG_BASE64,
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUploadAndFetchAvatar(): void
    {
        $user = $this->createUser('avatar@example.com');
        $client = $this->createAuthenticatedClientWithoutApiSecret($user);

        $this->jsonRequest($client, 'POST', '/api/me/avatar', [
            'filename' => 'avatar.png',
            'mimeType' => 'image/png',
            'content' => self::PNG_BASE64,
        ]);

        $this->assertResponseStatusCodeSame(200);
        $payload = $this->getJsonResponse($client);
        $this->assertSame('/api/users/' . $user->getId() . '/avatar', $payload['avatarUrl'] ?? null);

        $this->sendRequest($client, 'GET', '/api/users/' . $user->getId() . '/avatar');
        $this->assertResponseStatusCodeSame(200);
        $this->assertStringContainsString('image/png', (string) $client->getResponse()->headers->get('content-type'));
    }
}

